<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['customer', 'items.product']);
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }
        
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(20);
        $statuses = Order::getStatuses();
        $customers = Customer::orderBy('name')->get();
        
        return view('orders.index', compact('orders', 'statuses', 'customers'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $sizes = OrderItem::getSizes();
        
        return view('orders.create', compact('customers', 'products', 'sizes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'note' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.size' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'items.*.note' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $order = Order::create([
                'customer_id' => $validated['customer_id'],
                'status' => Order::STATUS_NEW,
                'note' => $validated['note'] ?? null,
            ]);

            foreach ($validated['items'] as $index => $itemData) {
                $itemImage = null;
                if ($request->hasFile("items.{$index}.image")) {
                    $itemImage = $request->file("items.{$index}.image")->store('order-items', 'public');
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $itemData['product_id'],
                    'size' => $itemData['size'],
                    'quantity' => $itemData['quantity'],
                    'price' => $itemData['price'],
                    'image' => $itemImage,
                    'note' => $itemData['note'] ?? null,
                ]);
            }

            $order->calculateTotal();
            
            DB::commit();

            return redirect()->route('orders.index')
                ->with('success', 'Tạo đơn hàng thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    public function show(Order $order)
    {
        $order->load(['customer', 'items.product']);
        return view('orders.show', compact('order'));
    }

    public function edit(Order $order)
    {
        $order->load(['customer', 'items.product']);
        $customers = Customer::orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $sizes = OrderItem::getSizes();
        $statuses = Order::getStatuses();
        
        return view('orders.edit', compact('order', 'customers', 'products', 'sizes', 'statuses'));
    }

    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'status' => 'required|in:' . implode(',', array_keys(Order::getStatuses())),
            'note' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:order_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.size' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'items.*.note' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $order->update([
                'customer_id' => $validated['customer_id'],
                'status' => $validated['status'],
                'note' => $validated['note'] ?? null,
            ]);

            $existingItemIds = $order->items->pluck('id')->toArray();
            $updatedItemIds = [];

            foreach ($validated['items'] as $index => $itemData) {
                $itemImage = null;
                if ($request->hasFile("items.{$index}.image")) {
                    $itemImage = $request->file("items.{$index}.image")->store('order-items', 'public');
                }

                if (!empty($itemData['id'])) {
                    $item = OrderItem::find($itemData['id']);
                    if ($item && $item->order_id === $order->id) {
                        $updateData = [
                            'product_id' => $itemData['product_id'],
                            'size' => $itemData['size'],
                            'quantity' => $itemData['quantity'],
                            'price' => $itemData['price'],
                            'note' => $itemData['note'] ?? null,
                        ];
                        if ($itemImage) {
                            if ($item->image) {
                                Storage::disk('public')->delete($item->image);
                            }
                            $updateData['image'] = $itemImage;
                        }
                        $item->update($updateData);
                        $updatedItemIds[] = $item->id;
                    }
                } else {
                    $item = OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $itemData['product_id'],
                        'size' => $itemData['size'],
                        'quantity' => $itemData['quantity'],
                        'price' => $itemData['price'],
                        'image' => $itemImage,
                        'note' => $itemData['note'] ?? null,
                    ]);
                    $updatedItemIds[] = $item->id;
                }
            }

            $itemsToDelete = array_diff($existingItemIds, $updatedItemIds);
            foreach ($itemsToDelete as $itemId) {
                $item = OrderItem::find($itemId);
                if ($item && $item->image) {
                    Storage::disk('public')->delete($item->image);
                }
            }
            OrderItem::whereIn('id', $itemsToDelete)->delete();

            $order->calculateTotal();
            
            DB::commit();

            return redirect()->route('orders.index')
                ->with('success', 'Cập nhật đơn hàng thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(Order $order)
    {
        foreach ($order->items as $item) {
            if ($item->image) {
                Storage::disk('public')->delete($item->image);
            }
        }
        
        $order->delete();
        
        return redirect()->route('orders.index')
            ->with('success', 'Xóa đơn hàng thành công!');
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', array_keys(Order::getStatuses())),
        ]);

        $order->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái thành công!',
            'status_label' => $order->status_label,
            'status_color' => $order->status_color,
        ]);
    }

    public function bulkUpdateStatus(Request $request)
    {
        $validated = $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'exists:orders,id',
            'status' => 'required|in:' . implode(',', array_keys(Order::getStatuses())),
        ]);

        Order::whereIn('id', $validated['order_ids'])
            ->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật ' . count($validated['order_ids']) . ' đơn hàng thành công!',
        ]);
    }
}
