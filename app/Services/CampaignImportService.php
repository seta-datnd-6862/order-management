<?php

namespace App\Services;

use App\Models\CampaignOrder;
use App\Models\Customer;
use App\Models\OrderCampaign;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Nhận JSON do AI bóc tách từ take note, tạo ra một đợt nhập (campaign) gồm
 * các đơn nháp đã được khớp sẵn khách hàng / sản phẩm.
 */
class CampaignImportService
{
    /** Giá dưới mức này gần như chắc chắn đang ghi theo đơn vị nghìn đồng. */
    private const MONEY_THOUSAND_THRESHOLD = 1000;

    public function __construct(private EntityMatcher $matcher)
    {
    }

    /**
     * Đọc JSON thô (cho phép có bọc ```json) thành mảng đã kiểm tra hợp lệ.
     *
     * @throws ValidationException
     */
    public function parse(string $rawJson): array
    {
        $cleaned = $this->stripCodeFence($rawJson);

        $payload = json_decode($cleaned, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($payload)) {
            throw ValidationException::withMessages([
                'raw_json' => 'JSON không hợp lệ: ' . json_last_error_msg() . '. Hãy copy lại toàn bộ JSON mà AI trả về.',
            ]);
        }

        // Cho phép AI trả về thẳng một mảng đơn hàng.
        if (!isset($payload['orders']) && array_is_list($payload)) {
            $payload = ['orders' => $payload];
        }

        $validator = Validator::make($payload, [
            'name' => 'nullable|string|max:255',
            'orders' => 'required|array|min:1',
            'orders.*.customer_name' => 'required|string|max:255',
            'orders.*.customer_note' => 'nullable|string|max:255',
            'orders.*.order_date' => 'nullable|string|max:40',
            'orders.*.is_marked_done' => 'nullable|boolean',
            'orders.*.deposit_amount' => 'nullable|numeric|min:0',
            'orders.*.discount_amount' => 'nullable|numeric|min:0',
            'orders.*.note' => 'nullable|string',
            'orders.*.warnings' => 'nullable|array',
            'orders.*.raw_line' => 'nullable|string',
            'orders.*.items' => 'required|array|min:1',
            'orders.*.items.*.product_name' => 'required|string|max:255',
            'orders.*.items.*.size' => 'nullable',
            'orders.*.items.*.quantity' => 'nullable|numeric|min:1',
            'orders.*.items.*.price' => 'nullable|numeric|min:0',
            'orders.*.items.*.note' => 'nullable|string',
            'orders.*.items.*.raw_text' => 'nullable|string',
        ], [], [
            'orders' => 'danh sách đơn',
            'orders.*.customer_name' => 'tên khách hàng',
            'orders.*.items' => 'danh sách sản phẩm',
            'orders.*.items.*.product_name' => 'tên sản phẩm',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages([
                'raw_json' => 'JSON sai cấu trúc: ' . implode(' | ', $validator->errors()->all()),
            ]);
        }

        return $payload;
    }

    /**
     * Xem trước JSON: trả về số đơn, số sản phẩm và các cảnh báo, không ghi DB.
     */
    public function preview(string $rawJson, int $userId): array
    {
        $payload = $this->parse($rawJson);

        $orders = $payload['orders'];
        $itemCount = 0;
        $warnings = [];

        foreach ($orders as $index => $order) {
            $itemCount += count($order['items'] ?? []);
            $warnings = array_merge($warnings, $this->collectWarnings($order, $index));
        }

        return [
            'name' => $payload['name'] ?? null,
            'order_count' => count($orders),
            'item_count' => $itemCount,
            'warnings' => $warnings,
        ];
    }

    /**
     * Tạo campaign + đơn nháp + dòng sản phẩm, đã khớp sẵn khách và sản phẩm.
     *
     * @throws ValidationException
     */
    public function import(string $rawJson, int $userId, ?string $name = null, ?string $sourceNote = null): OrderCampaign
    {
        $payload = $this->parse($rawJson);

        $customers = Customer::where('user_id', $userId)->get(['id', 'name']);
        $products = Product::where('user_id', $userId)->get(['id', 'name', 'default_price']);

        $importWarnings = [];

        return DB::transaction(function () use ($payload, $userId, $name, $sourceNote, $customers, $products, &$importWarnings) {
            $campaign = OrderCampaign::create([
                'user_id' => $userId,
                'name' => $name ?: ($payload['name'] ?? 'Đợt nhập ' . now()->format('d/m/Y H:i')),
                'source_note' => $sourceNote,
                'raw_json' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                'status' => OrderCampaign::STATUS_IN_PROGRESS,
            ]);

            foreach (array_values($payload['orders']) as $index => $orderData) {
                $warnings = $this->collectWarnings($orderData, $index);

                $deposit = $this->normalizeMoney($orderData['deposit_amount'] ?? 0, 'Tiền cọc', $warnings);
                $discount = $this->normalizeMoney($orderData['discount_amount'] ?? 0, 'Giảm giá', $warnings);

                $customerMatch = $this->matcher->match($orderData['customer_name'], $customers);

                $campaignOrder = $campaign->campaignOrders()->create([
                    'sequence' => $index + 1,
                    'order_date' => $this->parseDate($orderData['order_date'] ?? null),
                    'customer_name_raw' => trim($orderData['customer_name']),
                    'customer_note_raw' => $orderData['customer_note'] ?? null,
                    'customer_id' => $customerMatch['id'],
                    'customer_match' => $customerMatch['match'],
                    'customer_suggestions' => $customerMatch['suggestions'],
                    'deposit_amount' => $deposit,
                    'discount_amount' => $discount,
                    'note' => $orderData['note'] ?? null,
                    'is_marked_done' => (bool) ($orderData['is_marked_done'] ?? false),
                    'raw_line' => $orderData['raw_line'] ?? null,
                    'status' => CampaignOrder::STATUS_PENDING,
                ]);

                foreach (array_values($orderData['items']) as $itemIndex => $itemData) {
                    $productMatch = $this->matcher->match($itemData['product_name'], $products);
                    $sizeRaw = isset($itemData['size']) && $itemData['size'] !== null
                        ? (string) $itemData['size']
                        : null;
                    $size = $this->matcher->normalizeSize($sizeRaw);

                    if ($sizeRaw !== null && $size === null) {
                        $warnings[] = "Size \"{$sizeRaw}\" của \"{$itemData['product_name']}\" không có trong danh sách size, cần chọn lại.";
                    }

                    $price = $itemData['price'] ?? null;

                    if ($price !== null) {
                        $price = $this->normalizeMoney($price, "Giá \"{$itemData['product_name']}\"", $warnings);
                    }

                    // Không có giá trong note thì lấy giá mặc định của sản phẩm đã khớp.
                    if ($price === null && $productMatch['id']) {
                        $price = (float) ($products->firstWhere('id', $productMatch['id'])->default_price ?? 0);
                    }

                    $campaignOrder->items()->create([
                        'sequence' => $itemIndex + 1,
                        'product_name_raw' => trim($itemData['product_name']),
                        'product_id' => $productMatch['id'],
                        'product_match' => $productMatch['match'],
                        'product_suggestions' => $productMatch['suggestions'],
                        'size_raw' => $sizeRaw,
                        'size' => $size,
                        'quantity' => max(1, (int) ($itemData['quantity'] ?? 1)),
                        'price' => $price,
                        'note' => $itemData['note'] ?? null,
                        'raw_text' => $itemData['raw_text'] ?? null,
                    ]);
                }

                $campaignOrder->update(['warnings' => array_values(array_unique($warnings))]);

                $importWarnings = array_merge($importWarnings, $warnings);
            }

            if ($importWarnings) {
                $campaign->update(['import_warnings' => array_values(array_unique($importWarnings))]);
            }

            return $campaign;
        });
    }

    /**
     * Khớp lại khách / sản phẩm cho các đơn còn chờ (dùng sau khi vừa thêm dữ liệu mới).
     */
    public function rematch(OrderCampaign $campaign): int
    {
        $customers = Customer::where('user_id', $campaign->user_id)->get(['id', 'name']);
        $products = Product::where('user_id', $campaign->user_id)->get(['id', 'name', 'default_price']);

        $touched = 0;

        $pending = $campaign->campaignOrders()
            ->with('items')
            ->where('status', CampaignOrder::STATUS_PENDING)
            ->get();

        foreach ($pending as $campaignOrder) {
            if (!$campaignOrder->customer_id) {
                $match = $this->matcher->match($campaignOrder->customer_name_raw, $customers);

                if ($match['id'] || $match['suggestions'] !== ($campaignOrder->customer_suggestions ?? [])) {
                    $campaignOrder->update([
                        'customer_id' => $match['id'],
                        'customer_match' => $match['match'],
                        'customer_suggestions' => $match['suggestions'],
                    ]);
                    $touched++;
                }
            }

            foreach ($campaignOrder->items as $item) {
                if ($item->product_id) {
                    continue;
                }

                $match = $this->matcher->match($item->product_name_raw, $products);

                if ($match['id'] || $match['suggestions'] !== ($item->product_suggestions ?? [])) {
                    $item->update([
                        'product_id' => $match['id'],
                        'product_match' => $match['match'],
                        'product_suggestions' => $match['suggestions'],
                        'price' => $item->price ?: ($match['id']
                            ? (float) ($products->firstWhere('id', $match['id'])->default_price ?? 0)
                            : $item->price),
                    ]);
                    $touched++;
                }
            }
        }

        return $touched;
    }

    private function stripCodeFence(string $raw): string
    {
        $raw = trim($raw);
        $raw = preg_replace('/^```(?:json)?\s*/i', '', $raw);
        $raw = preg_replace('/\s*```$/', '', $raw);

        return trim($raw);
    }

    /**
     * Cảnh báo do AI gửi kèm + các thiếu sót nhìn thấy ngay trong JSON.
     */
    private function collectWarnings(array $orderData, int $index): array
    {
        $label = 'Đơn #' . ($index + 1) . ' (' . ($orderData['customer_name'] ?? '?') . ')';
        $warnings = [];

        foreach ($orderData['warnings'] ?? [] as $warning) {
            if (is_string($warning) && trim($warning) !== '') {
                $warnings[] = $label . ': ' . trim($warning);
            }
        }

        if (empty($orderData['order_date'])) {
            $warnings[] = $label . ': không đọc được ngày đặt.';
        }

        foreach ($orderData['items'] ?? [] as $item) {
            if (($item['size'] ?? null) === null || $item['size'] === '') {
                $warnings[] = $label . ': "' . ($item['product_name'] ?? '?') . '" chưa có size.';
            }
        }

        return $warnings;
    }

    /**
     * Số tiền ghi theo nghìn đồng (199) được quy đổi về VNĐ (199.000) kèm cảnh báo.
     */
    private function normalizeMoney($value, string $label, array &$warnings): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $amount = (float) $value;

        if ($amount > 0 && $amount < self::MONEY_THOUSAND_THRESHOLD) {
            $converted = $amount * 1000;
            $warnings[] = "{$label}: tự quy đổi " . rtrim(rtrim(number_format($amount, 2, ',', '.'), '0'), ',')
                . ' → ' . number_format($converted, 0, ',', '.') . 'đ, kiểm tra lại nếu sai.';

            return $converted;
        }

        return $amount;
    }

    private function parseDate(?string $value): ?string
    {
        if (!$value || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse(trim($value))->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
