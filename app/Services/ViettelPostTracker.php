<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * ViettelPostTracker - Tra cứu hành trình đơn vận chuyển Viettel Post
 *
 * === THỰC TẾ API VIETTEL POST ===
 * API chính thức (partner.viettelpost.vn) KHÔNG có endpoint tracking.
 * Chỉ có: đăng nhập, danh mục, tính cước, tạo đơn, webhook.
 *
 * Class này scrape trang tra cứu công khai.
 * Có thể bị thay đổi/chặn bất cứ lúc nào.
 */
class ViettelPostTracker
{
    const STATUS_CREATED      = 100;
    const STATUS_PICKING_UP   = 101;
    const STATUS_PICKED_UP    = 102;
    const STATUS_IN_TRANSIT   = 200;
    const STATUS_AT_WAREHOUSE = 201;
    const STATUS_OUT_DELIVERY = 300;
    const STATUS_DELIVERED    = 501;
    const STATUS_RETURNED     = 504;
    const STATUS_RETURNING    = 503;
    const STATUS_FAILED       = 500;
    const STATUS_CANCELLED    = -100;

    protected int $cacheTtl;
    protected int $timeout;

    public function __construct()
    {
        $this->cacheTtl = (int) config('services.viettelpost.cache_ttl', 15);
        $this->timeout  = (int) config('services.viettelpost.timeout', 30);
    }

    /**
     * Tra cứu chi tiết đơn vận chuyển
     */
    public function track(string $trackingNumber, bool $forceRefresh = false): array
    {
        $trackingNumber = $this->sanitize($trackingNumber);

        if (empty($trackingNumber)) {
            return $this->error('Mã vận đơn không hợp lệ');
        }

        $cacheKey = "vtp_tracking:{$trackingNumber}";

        if (!$forceRefresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $methods = [
            'trackViaPublicPage'  => 'Trang tra cứu công khai',
            'trackViaOldEndpoint' => 'Endpoint tra cứu cũ',
        ];

        foreach ($methods as $method => $label) {
            Log::info("VTP: [{$label}] đang thử cho đơn {$trackingNumber}");

            try {
                $result = $this->{$method}($trackingNumber);

                if ($result && $result['success']) {
                    Log::info("VTP: [{$label}] thành công", [
                        'tracking'    => $trackingNumber,
                        'checkpoints' => count($result['data']['checkpoints'] ?? []),
                    ]);
                    Cache::put($cacheKey, $result, now()->addMinutes($this->cacheTtl));
                    return $result;
                }

                Log::warning("VTP: [{$label}] không có kết quả hợp lệ");

            } catch (\Exception $e) {
                Log::error("VTP: [{$label}] lỗi: {$e->getMessage()}", [
                    'tracking' => $trackingNumber,
                    'trace'    => $e->getFile() . ':' . $e->getLine(),
                ]);
            }
        }

        return $this->error('Không thể tra cứu đơn hàng. Vui lòng thử lại sau.');
    }

    public function getStatus(string $trackingNumber): ?array
    {
        $result = $this->track($trackingNumber);
        if (!$result['success']) return null;

        $data = $result['data'];
        $last = !empty($data['checkpoints']) ? $data['checkpoints'][0] : null;

        return [
            'tracking_number' => $data['tracking_number'],
            'status'          => $data['status'] ?? 'Không xác định',
            'status_code'     => $data['status_code'] ?? 0,
            'location'        => $last['location'] ?? '',
            'message'         => $last['message'] ?? '',
            'updated_at'      => $last['time'] ?? null,
        ];
    }

    public function trackMultiple(array $numbers, bool $force = false): array
    {
        $results = [];
        foreach ($numbers as $n) {
            $results[$n] = $this->track($n, $force);
            usleep(500000);
        }
        return $results;
    }

    public function isDelivered(string $trackingNumber): bool
    {
        $s = $this->getStatus($trackingNumber);
        return $s && $s['status_code'] == self::STATUS_DELIVERED;
    }

    public function clearCache(string $trackingNumber): void
    {
        Cache::forget("vtp_tracking:" . $this->sanitize($trackingNumber));
    }

    /*
    |--------------------------------------------------------------------------
    | Tracking Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Scrape trang tra cứu công khai
     */
    protected function trackViaPublicPage(string $trackingNumber): ?array
    {
        // Trang tra cứu hiện tại có CAPTCHA, thử gọi trực tiếp
        $url = 'https://viettelpost.com.vn/tra-cuu-hanh-trinh-don/';

        $response = Http::withHeaders($this->headers($url))
            ->withOptions(['verify' => false])
            ->timeout($this->timeout)
            ->get($url);

        $this->logResponse('GET trang chính', $url, $response);

        if (!$response->successful()) return null;

        $html = $response->body();

        // Kiểm tra CAPTCHA
        if ($this->hasCaptcha($html)) {
            Log::warning("VTP: Trang có CAPTCHA - không thể tự động tra cứu");
            return null;
        }

        // Tìm endpoint AJAX trong HTML (JS code)
        $ajaxUrl = $this->findAjaxEndpoint($html);
        if ($ajaxUrl) {
            Log::info("VTP: Tìm thấy AJAX endpoint: {$ajaxUrl}");
            return $this->callAjaxEndpoint($ajaxUrl, $trackingNumber, $url);
        }

        // Thử POST trực tiếp
        $formAction = $this->findFormAction($html, $url);
        Log::debug("VTP: Form action: {$formAction}");

        $postResponse = Http::withHeaders(array_merge($this->headers($url), [
                'X-Requested-With' => 'XMLHttpRequest',
            ]))
            ->withOptions(['verify' => false])
            ->timeout($this->timeout)
            ->asForm()
            ->post($formAction, ['KEY' => $trackingNumber]);

        $this->logResponse('POST form', $formAction, $postResponse);

        if (!$postResponse->successful()) return null;

        return $this->parseResponse($postResponse->body(), $trackingNumber);
    }

    /**
     * Thử endpoint cũ (trước redesign)
     */
    protected function trackViaOldEndpoint(string $trackingNumber): ?array
    {
        $urls = [
            "https://viettelpost.com.vn/Tracking?KEY={$trackingNumber}",
            "https://www.viettelpost.com.vn/Tracking?KEY={$trackingNumber}",
        ];

        foreach ($urls as $url) {
            try {
                $response = Http::withHeaders($this->headers())
                    ->withOptions([
                        'verify'          => false,
                        'allow_redirects' => ['max' => 3],
                    ])
                    ->timeout($this->timeout)
                    ->get($url);

                $this->logResponse("GET endpoint cũ", $url, $response);

                if (!$response->successful()) continue;

                $result = $this->parseResponse($response->body(), $trackingNumber);
                if ($result && $result['success']) return $result;

            } catch (\Exception $e) {
                Log::warning("VTP: Endpoint cũ lỗi", ['url' => $url, 'error' => $e->getMessage()]);
            }
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | AJAX Endpoint Discovery
    |--------------------------------------------------------------------------
    */

    /**
     * Tìm AJAX endpoint trong JS code của trang
     */
    protected function findAjaxEndpoint(string $html): ?string
    {
        // Các pattern phổ biến mà VTP web app sử dụng
        $patterns = [
            '/(?:axios|fetch|\$\.ajax|\$\.(?:get|post))\s*\(\s*[\'"]([^"\']*(?:track|search|order|bill)[^"\']*)[\'"]/',
            '/url\s*:\s*[\'"]([^"\']*(?:track|search|order|bill)[^"\']*)[\'"]/',
            '/(?:api|endpoint)\s*[=:]\s*[\'"]([^"\']*(?:track|search|order)[^"\']*)[\'"]/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $m)) {
                $endpoint = $m[1];
                if (str_starts_with($endpoint, '/')) {
                    return 'https://viettelpost.com.vn' . $endpoint;
                }
                if (str_starts_with($endpoint, 'http')) {
                    return $endpoint;
                }
            }
        }

        return null;
    }

    /**
     * Gọi AJAX endpoint đã tìm được
     */
    protected function callAjaxEndpoint(string $url, string $trackingNumber, string $referer): ?array
    {
        $payloads = [
            ['KEY' => $trackingNumber],
            ['billcode' => $trackingNumber],
            ['ORDER_NUMBER' => $trackingNumber],
            ['tracking_number' => $trackingNumber],
        ];

        foreach ($payloads as $payload) {
            try {
                $response = Http::withHeaders(array_merge($this->headers($referer), [
                        'X-Requested-With' => 'XMLHttpRequest',
                        'Accept'           => 'application/json',
                    ]))
                    ->withOptions(['verify' => false])
                    ->timeout($this->timeout)
                    ->post($url, $payload);

                $this->logResponse("AJAX POST", $url, $response);

                if ($response->successful()) {
                    $result = $this->parseResponse($response->body(), $trackingNumber);
                    if ($result && $result['success']) return $result;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Response Parsing
    |--------------------------------------------------------------------------
    */

    protected function parseResponse(string $body, string $trackingNumber): ?array
    {
        // 1. Thử parse JSON
        $json = json_decode($body, true);
        if (is_array($json) && !empty($json)) {
            Log::debug("VTP: Response là JSON", ['keys' => array_keys($json)]);
            return $this->normalizeJson($json, $trackingNumber);
        }

        // 2. Kiểm tra HTML errors
        if ($this->hasCaptcha($body)) {
            Log::warning("VTP: Response yêu cầu CAPTCHA");
            return null;
        }

        if (str_contains($body, 'không có trên hệ thống') || str_contains($body, 'Mã phiếu gửi không')) {
            return $this->error("Mã vận đơn {$trackingNumber} không tồn tại");
        }

        // 3. Parse HTML
        Log::debug("VTP: Đang parse HTML...", ['length' => strlen($body)]);
        return $this->parseHtml($body, $trackingNumber);
    }

    protected function normalizeJson(array $raw, string $trackingNumber): ?array
    {
        try {
            $data = $raw['data'] ?? $raw['result'] ?? $raw;
            if (isset($data[0])) $data = $data[0];

            $checkpoints = $this->extractCheckpoints($data);
            usort($checkpoints, fn($a, $b) => strtotime($b['time'] ?? '0') - strtotime($a['time'] ?? '0'));

            $status = $this->resolveStatus($data, $checkpoints);

            return $this->success($trackingNumber, [
                'tracking_number' => $trackingNumber,
                'status'          => $status['label'],
                'status_code'     => $status['code'],
                'receiver'        => $data['RECEIVER_FULLNAME'] ?? $data['receiverFullname'] ?? '',
                'weight'          => $data['PRODUCT_WEIGHT'] ?? $data['productWeight'] ?? 0,
                'service'         => $data['SERVICE_NAME'] ?? $data['serviceName'] ?? '',
                'money_total'     => $data['MONEY_TOTAL'] ?? $data['moneyTotal'] ?? 0,
                'money_cod'       => $data['MONEY_COD'] ?? $data['moneyCod'] ?? 0,
                'checkpoints'     => $checkpoints,
                'source'          => 'json',
            ]);
        } catch (\Exception $e) {
            Log::error("VTP: normalizeJson lỗi", ['error' => $e->getMessage()]);
            return null;
        }
    }

    protected function parseHtml(string $html, string $trackingNumber): ?array
    {
        if (strlen($html) < 200) return null;

        $checkpoints = [];

        // Pattern 1: JSON trong <script>
        if (preg_match_all('/var\s+\w+\s*=\s*(\[{.+?}\])\s*;/s', $html, $m)) {
            foreach ($m[1] as $jsonStr) {
                $decoded = json_decode($jsonStr, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $item) {
                        $msg = $item['STATUS_NAME'] ?? $item['message'] ?? $item['content'] ?? '';
                        if ($msg) {
                            $checkpoints[] = [
                                'time'     => $this->parseTime($item['STATUS_DATE'] ?? $item['time'] ?? null),
                                'message'  => $msg,
                                'location' => $item['POST_OFFICE_NAME'] ?? $item['location'] ?? '',
                            ];
                        }
                    }
                }
            }
        }

        // Pattern 2: Bảng HTML
        if (empty($checkpoints) && preg_match_all('/<tr[^>]*>(.*?)<\/tr>/si', $html, $rows)) {
            foreach ($rows[1] as $row) {
                if (!preg_match_all('/<td[^>]*>(.*?)<\/td>/si', $row, $cells)) continue;
                $vals = array_map(fn($c) => trim(strip_tags($c)), $cells[1]);
                if (count($vals) < 2) continue;

                $time = $message = $location = null;
                foreach ($vals as $v) {
                    if (!$time && preg_match('/\d{2}[\/\-]\d{2}[\/\-]\d{4}/', $v)) $time = $v;
                    elseif (!$message && mb_strlen($v) > 5 && !is_numeric($v)) $message = $v;
                    elseif ($message && !$location && mb_strlen($v) > 2) $location = $v;
                }

                if ($message) {
                    $checkpoints[] = [
                        'time'     => $this->parseTime($time),
                        'message'  => $message,
                        'location' => $location ?? '',
                    ];
                }
            }
        }

        if (empty($checkpoints)) {
            Log::warning("VTP: Không parse được checkpoint từ HTML", [
                'html_sample' => substr(strip_tags($html), 0, 500),
            ]);
            return null;
        }

        usort($checkpoints, fn($a, $b) => strtotime($b['time'] ?? '0') - strtotime($a['time'] ?? '0'));

        return $this->success($trackingNumber, [
            'tracking_number' => $trackingNumber,
            'status'          => $checkpoints[0]['message'] ?? 'Đang cập nhật',
            'status_code'     => $this->mapStatus($checkpoints[0]['message'] ?? ''),
            'checkpoints'     => $checkpoints,
            'receiver'        => '',
            'weight'          => 0,
            'service'         => '',
            'money_total'     => 0,
            'money_cod'       => 0,
            'source'          => 'html',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Utilities
    |--------------------------------------------------------------------------
    */

    protected function extractCheckpoints(array $data): array
    {
        foreach (['TRACKING_LIST', 'tracking_list', 'trackingList', 'checkpoints', 'history', 'LIST_ITEM_TRACKING'] as $key) {
            if (!empty($data[$key]) && is_array($data[$key])) {
                $result = [];
                foreach ($data[$key] as $item) {
                    $msg = $item['STATUS_NAME'] ?? $item['statusName'] ?? $item['message'] ?? $item['NOTE'] ?? '';
                    if (!$msg) continue;
                    $result[] = [
                        'time'     => $this->parseTime($item['STATUS_DATE'] ?? $item['statusDate'] ?? $item['time'] ?? null),
                        'message'  => $msg,
                        'location' => $item['POST_OFFICE_NAME'] ?? $item['postOfficeName'] ?? $item['location'] ?? '',
                    ];
                }
                return $result;
            }
        }
        return [];
    }

    protected function resolveStatus(array $data, array $checkpoints): array
    {
        $code = $data['ORDER_STATUS'] ?? $data['orderStatus'] ?? null;
        if ($code !== null && is_numeric($code)) {
            return ['code' => (int) $code, 'label' => self::getStatusLabel((int) $code)];
        }
        if (!empty($checkpoints)) {
            $c = $this->mapStatus($checkpoints[0]['message'] ?? '');
            return ['code' => $c, 'label' => self::getStatusLabel($c)];
        }
        return ['code' => 0, 'label' => 'Không xác định'];
    }

    protected function hasCaptcha(string $html): bool
    {
        return str_contains($html, 'g-recaptcha')
            || str_contains($html, 'recaptcha')
            || str_contains($html, 'captcha')
            || str_contains($html, 'hcaptcha');
    }

    protected function findFormAction(string $html, string $fallback): string
    {
        if (preg_match('/<form[^>]*action="([^"]+)"[^>]*>/i', $html, $m)) {
            $a = $m[1];
            if (str_starts_with($a, '/')) return 'https://viettelpost.com.vn' . $a;
            if (str_starts_with($a, 'http')) return $a;
        }
        return $fallback;
    }

    protected function headers(?string $referer = null): array
    {
        $h = [
            'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
            'Accept'          => 'text/html,application/xhtml+xml,application/json,*/*',
            'Accept-Language' => 'vi-VN,vi;q=0.9,en;q=0.8',
        ];
        if ($referer) {
            $h['Referer'] = $referer;
            $h['Origin']  = parse_url($referer, PHP_URL_SCHEME) . '://' . parse_url($referer, PHP_URL_HOST);
        }
        return $h;
    }

    protected function logResponse(string $label, string $url, $response): void
    {
        Log::debug("VTP: [{$label}]", [
            'url'         => $url,
            'status'      => $response->status(),
            'body_length' => strlen($response->body()),
            'body_start'  => substr($response->body(), 0, 300),
            'content_type'=> $response->header('Content-Type'),
        ]);
    }

    protected function parseTime(?string $dt): ?string
    {
        if (!$dt) return null;
        foreach (['d/m/Y H:i:s', 'd/m/Y H:i', 'd-m-Y H:i:s', 'Y-m-d H:i:s', 'Y-m-d\TH:i:s'] as $f) {
            $p = \DateTime::createFromFormat($f, trim($dt));
            if ($p) return $p->format('Y-m-d H:i:s');
        }
        try { return Carbon::parse($dt)->format('Y-m-d H:i:s'); } catch (\Exception $e) { return $dt; }
    }

    protected function mapStatus(string $msg): int
    {
        $m = mb_strtolower($msg);
        $map = [
            self::STATUS_DELIVERED    => ['giao thành công', 'phát thành công', 'đã giao'],
            self::STATUS_OUT_DELIVERY => ['đang giao', 'đang phát', 'chuyển phát'],
            self::STATUS_IN_TRANSIT   => ['đang vận chuyển', 'luân chuyển', 'rời bưu cục'],
            self::STATUS_AT_WAREHOUSE => ['tại kho', 'đến bưu cục', 'nhập kho', 'đã đến'],
            self::STATUS_PICKED_UP    => ['đã lấy hàng', 'đã nhận hàng', 'tiếp nhận'],
            self::STATUS_PICKING_UP   => ['đang lấy', 'giao bưu cục đi nhận'],
            self::STATUS_RETURNED     => ['đã hoàn'],
            self::STATUS_RETURNING    => ['đang hoàn', 'chuyển hoàn'],
            self::STATUS_FAILED       => ['giao thất bại', 'không giao được'],
            self::STATUS_CANCELLED    => ['đã hủy'],
            self::STATUS_CREATED      => ['tạo đơn', 'khởi tạo'],
        ];
        foreach ($map as $code => $kws) {
            foreach ($kws as $kw) { if (str_contains($m, $kw)) return $code; }
        }
        return 0;
    }

    protected function sanitize(string $n): string
    {
        return preg_replace('/[^a-zA-Z0-9]/', '', trim($n));
    }

    protected function success(string $t, array $d): array
    {
        return ['success' => true, 'tracking_number' => $t, 'message' => 'Tra cứu thành công', 'data' => $d, 'retrieved_at' => now()->format('Y-m-d H:i:s')];
    }

    protected function error(string $m): array
    {
        return ['success' => false, 'message' => $m, 'data' => null];
    }

    public static function getStatusLabel(int $c): string
    {
        return [self::STATUS_CREATED => 'Đơn mới tạo', self::STATUS_PICKING_UP => 'Đang lấy hàng', self::STATUS_PICKED_UP => 'Đã lấy hàng', self::STATUS_IN_TRANSIT => 'Đang vận chuyển', self::STATUS_AT_WAREHOUSE => 'Tại kho trung chuyển', self::STATUS_OUT_DELIVERY => 'Đang giao hàng', self::STATUS_DELIVERED => 'Giao thành công', self::STATUS_RETURNED => 'Đã hoàn hàng', self::STATUS_RETURNING => 'Đang hoàn hàng', self::STATUS_FAILED => 'Giao thất bại', self::STATUS_CANCELLED => 'Đã hủy'][$c] ?? 'Không xác định';
    }

    public static function getStatusColors(): array
    {
        return [self::STATUS_CREATED => 'bg-gray-100 text-gray-800', self::STATUS_PICKING_UP => 'bg-yellow-100 text-yellow-800', self::STATUS_PICKED_UP => 'bg-blue-100 text-blue-800', self::STATUS_IN_TRANSIT => 'bg-indigo-100 text-indigo-800', self::STATUS_AT_WAREHOUSE => 'bg-purple-100 text-purple-800', self::STATUS_OUT_DELIVERY => 'bg-orange-100 text-orange-800', self::STATUS_DELIVERED => 'bg-green-100 text-green-800', self::STATUS_RETURNED => 'bg-red-100 text-red-800', self::STATUS_RETURNING => 'bg-pink-100 text-pink-800', self::STATUS_FAILED => 'bg-red-100 text-red-800', self::STATUS_CANCELLED => 'bg-gray-200 text-gray-600'];
    }
}
