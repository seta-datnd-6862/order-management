<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SummaryController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        $query = Order::query();
        
        if ($status) {
            $query->where('status', $status);
        }
        
        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }
        
        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        $orderIds = $query->pluck('id');
        
        // Tính toán tổng hợp tài chính
        $orders = Order::whereIn('id', $orderIds)->get();
        $financialStats = [
            'total_amount' => $orders->sum('total_amount'),
            'deposit_amount' => $orders->sum('deposit_amount'),
            'discount_amount' => $orders->sum('discount_amount'),
            'remaining_amount' => $orders->sum('remaining_amount'),
        ];

        // Lấy tất cả items và group by product_id
        $items = OrderItem::whereIn('order_id', $orderIds)
            ->with('product')
            ->select(
                'product_id',
                'size',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('MIN(image) as sample_image')
            )
            ->groupBy('product_id', 'size')
            ->get();

        // Group lại theo product_id
        $summary = $items->groupBy('product_id')->map(function ($productItems) {
            $firstItem = $productItems->first();
            
            return [
                'product_id' => $firstItem->product_id,
                'product_name' => $firstItem->product->name,
                'product_image' => $firstItem->product->image_url,
                'sizes' => $productItems->map(function ($item) {
                    return [
                        'size' => $item->size,
                        'quantity' => $item->total_quantity,
                        'sample_image' => $item->sample_image ? asset('storage/' . $item->sample_image) : null,
                    ];
                })->sortBy('size')->values()->toArray(),
                'total_quantity' => $productItems->sum('total_quantity'),
            ];
        })->sortBy('product_name')->values();

        $totalItems = $summary->sum('total_quantity');
        $totalProducts = $summary->count();
        $totalOrders = $orderIds->count();

        $statuses = Order::getStatuses();

        return view('summary.index', compact(
            'summary', 
            'totalItems', 
            'totalProducts', 
            'totalOrders',
            'financialStats',
            'statuses',
            'status',
            'fromDate',
            'toDate'
        ));
    }

    public function moveToNextStatus(Request $request)
    {
        $currentStatus = $request->input('current_status');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $statusFlow = [
            Order::STATUS_NEW => Order::STATUS_PREPARING,
            Order::STATUS_PREPARING => Order::STATUS_ORDERED,
            Order::STATUS_ORDERED => Order::STATUS_SHIPPING,
            Order::STATUS_SHIPPING => Order::STATUS_DELIVERED,
        ];

        if (!isset($statusFlow[$currentStatus])) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể chuyển trạng thái tiếp theo cho trạng thái này.'
            ]);
        }

        $nextStatus = $statusFlow[$currentStatus];

        $query = Order::where('status', $currentStatus);
        
        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }
        
        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        $count = $query->count();
        $query->update(['status' => $nextStatus]);

        $statuses = Order::getStatuses();

        return response()->json([
            'success' => true,
            'message' => "Đã chuyển {$count} đơn hàng sang trạng thái \"{$statuses[$nextStatus]}\"."
        ]);
    }
}
