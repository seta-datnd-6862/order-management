<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

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
            'deposit_amount' => 'nullable|numeric|min:0',
            'note' => 'nullable|string',
            'shipping_code' => 'nullable|string|max:255',
            'shipping_image' => 'nullable|image|max:5120', // 5MB
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.size' => 'required|in:' . implode(',', OrderItem::getSizes()),
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.note' => 'nullable|string',
            'items.*.image' => 'nullable|image|max:5120',
        ]);

        DB::beginTransaction();
        try {
            // Tính tổng tiền
            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                $totalAmount += $item['price'] * $item['quantity'];
            }

            // Upload shipping image nếu có
            $shippingImagePath = null;
            if ($request->hasFile('shipping_image')) {
                $shippingImagePath = $request->file('shipping_image')->store('shipping_images', 'public');
            }

            // Tạo đơn hàng
            $order = Order::create([
                'customer_id' => $validated['customer_id'],
                'status' => Order::STATUS_NEW,
                'total_amount' => $totalAmount,
                'deposit_amount' => $validated['deposit_amount'] ?? 0,
                'note' => $validated['note'] ?? null,
                'shipping_code' => $validated['shipping_code'] ?? null,
                'shipping_image' => $shippingImagePath,
            ]);

            // Tạo các item
            foreach ($validated['items'] as $itemData) {
                $imagePath = null;
                if (isset($itemData['image']) && $itemData['image']) {
                    $imagePath = $itemData['image']->store('order_items', 'public');
                }

                $order->items()->create([
                    'product_id' => $itemData['product_id'],
                    'size' => $itemData['size'],
                    'quantity' => $itemData['quantity'],
                    'price' => $itemData['price'],
                    'note' => $itemData['note'] ?? null,
                    'image' => $imagePath,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('orders.show', $order)
                ->with('success', 'Tạo đơn hàng thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function show(Order $order)
    {
        $order->load(['customer', 'items.product']);

        return view('orders.show', [
            'order' => $order,
            'statuses' => Order::getStatuses(),
        ]);
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
            'deposit_amount' => 'nullable|numeric|min:0',
            'note' => 'nullable|string',
            'shipping_code' => 'nullable|string|max:255',
            'shipping_image' => 'nullable|image|max:5120',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:order_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.size' => 'required|in:' . implode(',', OrderItem::getSizes()),
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.note' => 'nullable|string',
            'items.*.image' => 'nullable|image|max:5120',
        ]);

        DB::beginTransaction();
        try {
            // Tính tổng tiền mới
            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                $totalAmount += $item['price'] * $item['quantity'];
            }

            // Upload shipping image mới nếu có
            $shippingImagePath = $order->shipping_image;
            if ($request->hasFile('shipping_image')) {
                // Xóa ảnh cũ
                if ($order->shipping_image) {
                    Storage::disk('public')->delete($order->shipping_image);
                }
                $shippingImagePath = $request->file('shipping_image')->store('shipping_images', 'public');
            }

            // Cập nhật đơn hàng
            $order->update([
                'customer_id' => $validated['customer_id'],
                'total_amount' => $totalAmount,
                'deposit_amount' => $validated['deposit_amount'] ?? 0,
                'note' => $validated['note'] ?? null,
                'shipping_code' => $validated['shipping_code'] ?? null,
                'shipping_image' => $shippingImagePath,
            ]);

            // Lấy danh sách ID items hiện tại
            $existingItemIds = [];

            // Cập nhật/tạo items
            foreach ($validated['items'] as $itemData) {
                $imagePath = null;
                
                // Xử lý upload ảnh mới
                if (isset($itemData['image']) && $itemData['image']) {
                    // Nếu item đã tồn tại, xóa ảnh cũ
                    if (isset($itemData['id'])) {
                        $existingItem = $order->items()->find($itemData['id']);
                        if ($existingItem && $existingItem->image) {
                            Storage::disk('public')->delete($existingItem->image);
                        }
                    }
                    $imagePath = $itemData['image']->store('order_items', 'public');
                }

                if (isset($itemData['id']) && $itemData['id']) {
                    // Cập nhật item có sẵn
                    $item = $order->items()->find($itemData['id']);
                    if ($item) {
                        $updateData = [
                            'product_id' => $itemData['product_id'],
                            'size' => $itemData['size'],
                            'quantity' => $itemData['quantity'],
                            'price' => $itemData['price'],
                            'note' => $itemData['note'] ?? null,
                        ];
                        
                        if ($imagePath) {
                            $updateData['image'] = $imagePath;
                        }
                        
                        $item->update($updateData);
                        $existingItemIds[] = $item->id;
                    }
                } else {
                    // Tạo item mới
                    $newItem = $order->items()->create([
                        'product_id' => $itemData['product_id'],
                        'size' => $itemData['size'],
                        'quantity' => $itemData['quantity'],
                        'price' => $itemData['price'],
                        'note' => $itemData['note'] ?? null,
                        'image' => $imagePath,
                    ]);
                    $existingItemIds[] = $newItem->id;
                }
            }

            // Xóa các items không còn trong danh sách
            $order->items()->whereNotIn('id', $existingItemIds)->each(function ($item) {
                if ($item->image) {
                    Storage::disk('public')->delete($item->image);
                }
                $item->delete();
            });

            DB::commit();

            return redirect()
                ->route('orders.show', $order)
                ->with('success', 'Cập nhật đơn hàng thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function destroy(Order $order)
    {
        DB::beginTransaction();
        try {
            // Xóa ảnh shipping
            if ($order->shipping_image) {
                Storage::disk('public')->delete($order->shipping_image);
            }

            // Xóa ảnh các items
            foreach ($order->items as $item) {
                if ($item->image) {
                    Storage::disk('public')->delete($item->image);
                }
            }

            // Xóa order (items sẽ tự động xóa do cascade)
            $order->delete();

            DB::commit();

            return redirect()
                ->route('orders.index')
                ->with('success', 'Xóa đơn hàng thành công!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
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
