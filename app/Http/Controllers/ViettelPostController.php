<?php
// app/Http/Controllers/ViettelPostController.php - WITH NLP

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ViettelOrder;
use App\Services\ViettelPostService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ViettelPostController extends Controller
{
    protected $viettelService;

    public function __construct(ViettelPostService $viettelService)
    {
        $this->viettelService = $viettelService;
    }

    /**
     * Danh sách đơn Viettel Post
     */
    public function index(Request $request)
    {
        $query = ViettelOrder::with('order')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                  ->orWhere('receiver_name', 'like', "%{$search}%")
                  ->orWhere('receiver_phone', 'like', "%{$search}%");
            });
        }

        $viettelOrders = $query->paginate(20);

        return view('viettel-posts.index', compact('viettelOrders'));
    }

    /**
     * Form import đơn từ mã vận chuyển có sẵn
     */
    public function importForm()
    {
        $orders = Order::with('customer')
            ->where('status', Order::STATUS_SHIPPING)
            ->whereNotNull('shipping_code')
            ->where('shipping_code', '!=', '')
            ->whereDoesntHave('viettelOrder')
            ->latest()
            ->limit(100)
            ->get();

        return view('viettel-posts.import', compact('orders'));
    }

    /**
     * Import đơn từ mã vận chuyển
     */
    public function import(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'tracking_number' => 'required|string|unique:viettel_orders,tracking_number',
        ], [
            'tracking_number.unique' => 'Mã vận đơn này đã tồn tại trong hệ thống',
        ]);

        DB::beginTransaction();
        try {
            $order = Order::with('customer')->findOrFail($request->order_id);

            if ($order->status !== Order::STATUS_SHIPPING) {
                return redirect()->back()
                    ->with('error', 'Chỉ có thể import đơn hàng đang ở trạng thái "Đang ship"')
                    ->withInput();
            }

            if (empty($order->shipping_code)) {
                return redirect()->back()
                    ->with('error', 'Đơn hàng này chưa có mã vận chuyển')
                    ->withInput();
            }

            if ($request->tracking_number !== $order->shipping_code) {
                return redirect()->back()
                    ->with('error', 'Mã vận chuyển không khớp với mã trong đơn hàng')
                    ->withInput();
            }

            $viettelData = $this->viettelService->getOrderByTrackingNumber($request->tracking_number);

            $viettelOrder = ViettelOrder::create([
                'order_id' => $order->id,
                'tracking_number' => $request->tracking_number,
                'service_code' => $viettelData['SERVICE_CODE'] ?? 'VCN',
                'status' => ViettelOrder::STATUS_CREATED,
                'receiver_name' => $viettelData['RECEIVER_NAME'] ?? $order->customer->name,
                'receiver_phone' => $viettelData['RECEIVER_PHONE'] ?? $order->customer->phone,
                'receiver_address' => $viettelData['RECEIVER_ADDRESS'] ?? $order->customer->address,
                'product_weight' => $viettelData['PRODUCT_WEIGHT'] ?? null,
                'money_collection' => $viettelData['MONEY_COLLECTION'] ?? $order->remaining_amount,
                'shipping_fee' => $viettelData['MONEY_TOTAL'] ?? 0,
                'estimated_delivery_time' => $viettelData['KPI_HT'] ?? null,
                'note' => $request->note,
                'api_response' => $viettelData,
            ]);

            DB::commit();

            return redirect()->route('viettel-posts.show', $viettelOrder)
                ->with('success', 'Đã import đơn Viettel Post thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Lỗi: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Form tạo đơn mới từ Order
     */
    public function createFromOrder(Order $order)
    {
        if ($order->hasViettelOrder()) {
            return redirect()->route('viettel-posts.show', $order->viettelOrder)
                ->with('info', 'Đơn hàng này đã có đơn vận chuyển Viettel Post');
        }

        return view('viettel-posts.create-from-order', compact('order'));
    }

    /**
     * Lưu đơn mới
     */
    public function storeFromOrder(Request $request, Order $order)
    {
        $request->validate([
            'receiver_name' => 'required|string|max:255',
            'receiver_phone' => 'required|string|max:20',
            'receiver_address' => 'required|string|max:500',
            'product_weight' => 'required|integer|min:1',
            'service_code' => 'required|in:SHT,SCN,STK,VMCH',
            'money_collection' => 'required|numeric|min:0',
            'payment_by' => 'required|in:sender,receiver',
        ]);

        DB::beginTransaction();
        try {
            $orderPayment = 1;
            
            if ($request->money_collection > 0) {
                if ($request->payment_by === 'receiver') {
                    $orderPayment = 2;
                } else {
                    $orderPayment = 3;
                }
            }

            $apiParams = [
                'ORDER_NUMBER' => 'ORD-' . $order->id . '-' . time(),
                'SENDER_FULLNAME' => config('viettelpost.sender_name', 'Cửa hàng'),
                'SENDER_PHONE' => config('viettelpost.sender_phone', '0123456789'),
                'SENDER_ADDRESS' => config('viettelpost.sender_address', 'Hà Nội'),
                'RECEIVER_FULLNAME' => $request->receiver_name,
                'RECEIVER_PHONE' => $request->receiver_phone,
                'RECEIVER_ADDRESS' => $request->receiver_address,
                'PRODUCT_NAME' => 'Đơn hàng #' . $order->id,
                'PRODUCT_WEIGHT' => $request->product_weight,
                'PRODUCT_PRICE' => $order->total_amount,
                'MONEY_COLLECTION' => $request->money_collection,
                'ORDER_SERVICE' => $request->service_code,
                'ORDER_PAYMENT' => $orderPayment,
            ];

            $result = $this->viettelService->createOrder($apiParams);

            if (!$result['success']) {
                DB::rollBack();
                return redirect()->back()
                    ->with('error', 'Không thể tạo đơn: ' . $result['message'])
                    ->withInput();
            }

            $apiData = $result['data'];

            $viettelOrder = ViettelOrder::create([
                'order_id' => $order->id,
                'tracking_number' => $apiData['ORDER_NUMBER'],
                'service_code' => $request->service_code,
                'status' => ViettelOrder::STATUS_CREATED,
                'receiver_name' => $request->receiver_name,
                'receiver_phone' => $request->receiver_phone,
                'receiver_address' => $request->receiver_address,
                'product_weight' => $request->product_weight,
                'money_collection' => $request->money_collection,
                'shipping_fee' => $apiData['MONEY_TOTAL'] ?? 0,
                'estimated_delivery_time' => $apiData['KPI_HT'] ?? null,
                'note' => $request->note,
                'api_response' => $apiData,
            ]);

            DB::commit();

            return redirect()->route('viettel-posts.show', $viettelOrder)
                ->with('success', 'Đã tạo đơn Viettel Post thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Create Viettel order error: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Lỗi: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Chi tiết đơn
     */
    public function show(ViettelOrder $viettelOrder)
    {
        $viettelOrder->load('order');
        
        return view('viettel-posts.show', compact('viettelOrder'));
    }

    /**
     * Cập nhật trạng thái (AJAX)
     */
    public function updateStatus(Request $request, ViettelOrder $viettelOrder)
    {
        $request->validate([
            'status' => 'required|in:' . implode(',', array_keys(ViettelOrder::getStatuses())),
        ]);

        $viettelOrder->update([
            'status' => $request->status
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật trạng thái',
            'status_label' => $viettelOrder->status_label,
            'status_color' => $viettelOrder->status_color,
        ]);
    }

    /**
     * Xóa đơn
     */
    public function destroy(ViettelOrder $viettelOrder)
    {
        $orderId = $viettelOrder->order_id;
        $viettelOrder->delete();

        return redirect()->route('orders.show', $orderId)
            ->with('success', 'Đã xóa đơn Viettel Post');
    }

    /**
     * AJAX: Lấy danh sách services và giá
     * Gọi khi user nhập địa chỉ và trọng lượng
     */
    public function getServicesWithPrices(Request $request)
    {
        try {
            $request->validate([
                'receiver_address' => 'required|string',
                'product_weight' => 'required|integer|min:1',
                'product_price' => 'required|numeric|min:0',
                'money_collection' => 'nullable|numeric|min:0',
            ]);

            $result = $this->viettelService->getAllServicesWithPrices([
                'SENDER_ADDRESS' => config('viettelpost.sender_address'),
                'RECEIVER_ADDRESS' => $request->receiver_address,
                'PRODUCT_WEIGHT' => $request->product_weight,
                'PRODUCT_PRICE' => $request->product_price,
                'MONEY_COLLECTION' => $request->money_collection ?? 0,
            ]);

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Get services with prices error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Không thể lấy danh sách dịch vụ: ' . $e->getMessage()
            ], 500);
        }
    }
}
