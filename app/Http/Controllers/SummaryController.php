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
        $status = $request->get('status', Order::STATUS_PREPARING);
        $date = $request->get('date');

        $query = Order::where('status', $status);
        
        if ($date) {
            $query->whereDate('created_at', $date);
        }

        $orderIds = $query->pluck('id');

        $summary = OrderItem::whereIn('order_id', $orderIds)
            ->with('product')
            ->select(
                'product_id',
                'size',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('MIN(image) as sample_image')
            )
            ->groupBy('product_id', 'size')
            ->get()
            ->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'product_image' => $item->product->image_url,
                    'sample_image' => $item->sample_image ? asset('storage/' . $item->sample_image) : null,
                    'size' => $item->size,
                    'total_quantity' => $item->total_quantity,
                ];
            })
            ->sortBy([
                ['product_name', 'asc'],
                ['size', 'asc'],
            ])
            ->values();

        $totalItems = $summary->sum('total_quantity');
        $totalProducts = $summary->count();
        $totalOrders = $orderIds->count();

        $statuses = Order::getStatuses();

        return view('summary.index', compact(
            'summary', 
            'totalItems', 
            'totalProducts', 
            'totalOrders',
            'statuses',
            'status',
            'date'
        ));
    }

    public function moveToNextStatus(Request $request)
    {
        $currentStatus = $request->input('current_status');
        $date = $request->input('date');

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
        
        if ($date) {
            $query->whereDate('created_at', $date);
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
