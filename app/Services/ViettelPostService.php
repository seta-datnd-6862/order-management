<?php
// app/Services/ViettelPostService.php - WITH getPriceNlp

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;

class ViettelPostService
{
    protected $apiUrl;
    protected $token;

    public function __construct()
    {
        $this->apiUrl = config('viettelpost.api_url');
        $this->token = config('viettelpost.token');
    }

    /**
     * Ước tính phí vận chuyển (fallback)
     */
    private function getEstimatedFee(array $params): array
    {
        $serviceCode = $params['ORDER_SERVICE'];
        $weight = $params['PRODUCT_WEIGHT'];

        $baseFees = [
            'VCN' => 30000,
            'PHS' => 50000,
            'VCBO' => 25000,
        ];

        $baseFee = $baseFees[$serviceCode] ?? 30000;

        $weightFees = [
            'VCN' => 5000,
            'PHS' => 8000,
            'VCBO' => 4000,
        ];

        $weightFee = $weightFees[$serviceCode] ?? 5000;
        $extraFee = floor($weight / 1000) * $weightFee;

        $deliveryTimes = [
            'VCN' => 48,
            'PHS' => 24,
            'VCBO' => 72,
        ];

        $estimatedTime = $deliveryTimes[$serviceCode] ?? 48;

        return [
            'success' => true,
            'data' => [
                'MONEY_TOTAL' => $baseFee + $extraFee,
                'MONEY_TOTAL_FEE' => $baseFee + $extraFee,
                'KPI_HT' => $estimatedTime,
                'EXCHANGE_WEIGHT' => $weight,
            ],
            'is_estimate' => true,
            'message' => 'Phí ước tính (chưa tính chính xác)'
        ];
    }

    /**
     * Pull thông tin đơn hàng từ mã vận chuyển
     */
    public function getOrderByTrackingNumber(string $trackingNumber): ?array
    {
        try {
            $response = Http::timeout(10)->withHeaders([
                'Token' => $this->token,
            ])->get("{$this->apiUrl}/order/getOrderInfoByCode", [
                'ORDER_NUMBER' => $trackingNumber
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (!($data['error'] ?? true)) {
                    return $data['data'] ?? null;
                }
            }

            Log::error('ViettelPost: Failed to get order info', [
                'tracking_number' => $trackingNumber,
                'response' => $response->json()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('ViettelPost: Exception getting order info', [
                'tracking_number' => $trackingNumber,
                'message' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Tạo đơn hàng mới
     */
    public function createOrder(array $params): array
    {
        try {
            $response = Http::timeout(30)->withHeaders([
                'Token' => $this->token,
                'Content-Type' => 'application/json',
            ])->post("{$this->apiUrl}/order/createOrder", $params);

            if ($response->successful()) {
                $data = $response->json();
                
                if (!($data['error'] ?? true)) {
                    return [
                        'success' => true,
                        'data' => $data['data']
                    ];
                }
                
                return [
                    'success' => false,
                    'message' => $data['message'] ?? 'Lỗi không xác định'
                ];
            }

            Log::error('ViettelPost: Failed to create order', [
                'params' => $params,
                'response' => $response->json()
            ]);

            return [
                'success' => false,
                'message' => 'Không thể tạo đơn hàng'
            ];
        } catch (\Exception $e) {
            Log::error('ViettelPost: Exception creating order', [
                'params' => $params,
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Lấy token ngắn hạn (bước 1)
     */
    public function getShortTermToken(string $username, string $password): ?string
    {
        try {
            $response = Http::post("{$this->apiUrl}/user/Login", [
                'USERNAME' => $username,
                'PASSWORD' => $password,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!($data['error'] ?? true)) {
                    return $data['data']['token'] ?? null;
                }
            }

            Log::error('ViettelPost: Failed to get short-term token');
            return null;
        } catch (\Exception $e) {
            Log::error('ViettelPost: Exception getting short-term token', [
                'message' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Lấy token dài hạn (bước 2)
     */
    public function getLongTermToken(string $shortToken, string $username, string $password): ?string
    {
        try {
            $response = Http::withHeaders([
                'Token' => $shortToken,
            ])->post("{$this->apiUrl}/user/ownerconnect", [
                'USERNAME' => $username,
                'PASSWORD' => $password,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!($data['error'] ?? true)) {
                    return $data['data']['token'] ?? null;
                }
            }

            Log::error('ViettelPost: Failed to get long-term token');
            return null;
        } catch (\Exception $e) {
            Log::error('ViettelPost: Exception getting long-term token', [
                'message' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Cập nhật token vào .env file
     */
    public function updateToken(string $token): void
    {
        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);
        
        if (preg_match('/^VIETTELPOST_TOKEN=.*$/m', $envContent)) {
            $envContent = preg_replace(
                '/^VIETTELPOST_TOKEN=.*$/m',
                'VIETTELPOST_TOKEN=' . $token,
                $envContent
            );
        } else {
            $envContent .= "\nVIETTELPOST_TOKEN={$token}\n";
        }
        
        file_put_contents($envPath, $envContent);
        
        Artisan::call('config:clear');
    }

    /**
     * ULTIMATE: Lấy TẤT CẢ services và giá với địa chỉ TEXT
     * API này trả về ALL available services cho tuyến đường
     * 
     * @param array $params
     * @return array ['success' => bool, 'data' => array of services]
     */
    public function getAllServicesWithPrices(array $params): array
    {
        try {
            // Validate required params
            $requiredParams = ['SENDER_ADDRESS', 'RECEIVER_ADDRESS', 'PRODUCT_WEIGHT', 'PRODUCT_PRICE'];
            foreach ($requiredParams as $param) {
                if (!isset($params[$param])) {
                    return [
                        'success' => false,
                        'message' => "Missing required parameter: {$param}"
                    ];
                }
            }

            // Prepare API params
            $apiParams = [
                'SENDER_ADDRESS' => $params['SENDER_ADDRESS'],
                'RECEIVER_ADDRESS' => $params['RECEIVER_ADDRESS'],
                'PRODUCT_TYPE' => $params['PRODUCT_TYPE'] ?? 'HH',
                'PRODUCT_WEIGHT' => $params['PRODUCT_WEIGHT'],
                'PRODUCT_PRICE' => $params['PRODUCT_PRICE'],
                'MONEY_COLLECTION' => $params['MONEY_COLLECTION'] ?? 0,
                'PRODUCT_LENGTH' => $params['PRODUCT_LENGTH'] ?? 0,
                'PRODUCT_WIDTH' => $params['PRODUCT_WIDTH'] ?? 0,
                'PRODUCT_HEIGHT' => $params['PRODUCT_HEIGHT'] ?? 0,
                'TYPE' => $params['TYPE'] ?? 1,
            ];

            // Call API
            $response = Http::timeout(15)->withHeaders([
                'Token' => $this->token,
                'Content-Type' => 'application/json',
            ])->post("{$this->apiUrl}/order/getPriceAllNlp", $apiParams);

            if ($response->successful()) {
                $data = $response->json();
                
                // ACTUAL STRUCTURE: Response has SENDER_ADDRESS, RECEIVER_ADDRESS, RESULT
                // Not the standard {error, status, data} structure!
                
                if (isset($data['RESULT']) && is_array($data['RESULT'])) {
                    // Transform RESULT to standard format
                    $services = collect($data['RESULT'])->map(function($service) {
                        // Parse time string "24 giờ" -> 24
                        $timeHours = null;
                        if (isset($service['THOI_GIAN'])) {
                            preg_match('/(\d+)\s*giờ/i', $service['THOI_GIAN'], $matches);
                            $timeHours = isset($matches[1]) ? (int)$matches[1] : null;
                        }
                        
                        return [
                            // Map to standard field names
                            'SERVICE_CODE' => $service['MA_DV_CHINH'],
                            'SERVICE_NAME' => $service['TEN_DICHVU'],
                            'MONEY_TOTAL' => $service['GIA_CUOC'],
                            'KPI_HT' => $timeHours,
                            'THOI_GIAN_TEXT' => $service['THOI_GIAN'] ?? null,
                            'EXCHANGE_WEIGHT' => $service['EXCHANGE_WEIGHT'] ?? 0,
                            'EXTRA_SERVICE' => $service['EXTRA_SERVICE'] ?? [],
                            
                            // Keep original for reference
                            'MA_DV_CHINH' => $service['MA_DV_CHINH'],
                            'TEN_DICHVU' => $service['TEN_DICHVU'],
                            'GIA_CUOC' => $service['GIA_CUOC'],
                        ];
                    })->toArray();
                    
                    Log::info('ViettelPost: Got all services with prices', [
                        'count' => count($services),
                        'services' => collect($services)->pluck('SERVICE_CODE')->toArray(),
                        'sender' => $data['SENDER_ADDRESS']['ADDRESS'] ?? null,
                        'receiver' => $data['RECEIVER_ADDRESS']['ADDRESS'] ?? null,
                    ]);
                    
                    return [
                        'success' => true,
                        'data' => $services,
                        'sender_parsed' => $data['SENDER_ADDRESS'] ?? null,
                        'receiver_parsed' => $data['RECEIVER_ADDRESS'] ?? null,
                    ];
                }
                
                // No RESULT found
                Log::warning('ViettelPost: No RESULT in response', [
                    'params' => $apiParams,
                    'response' => $data
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy dịch vụ phù hợp với tuyến đường này'
                ];
            }

            // HTTP error
            Log::error('ViettelPost: HTTP error getting all services', [
                'params' => $apiParams,
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            return [
                'success' => false,
                'message' => 'Không thể kết nối API (HTTP ' . $response->status() . ')'
            ];

        } catch (\Exception $e) {
            Log::error('ViettelPost: Exception getting all services', [
                'params' => $params,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Lỗi hệ thống: ' . $e->getMessage()
            ];
        }
    }
}
