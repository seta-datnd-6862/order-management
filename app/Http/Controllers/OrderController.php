<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\InventoryImportItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['customer', 'items.product'])
            ->where('user_id', Auth::id());

        // Filter by search (customer name)
        if ($request->filled('search')) {
            $query->whereHas('customer', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        // Filter by customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by product
        if ($request->filled('product_id')) {
            $query->whereHas('items', function ($q) use ($request) {
                $q->where('product_id', $request->product_id);
            });
        }

        $orders = $query->orderBy('created_at')->paginate(20);

        // Check inventory availability for ORDERED status
        if ($request->filled('status') && $request->status === Order::STATUS_ORDERED) {
            foreach ($orders as $order) {
                $order->inventory_status = $this->checkInventoryAvailability($order);
            }
            
            // Sort orders: full stock first, then partial, then none
            $sortedOrders = $orders->getCollection()->sortBy(function($order) {
                $priority = [
                    'full' => 1,
                    'partial' => 2,
                    'none' => 3,
                ];
                return $priority[$order->inventory_status] ?? 4;
            })->values();
            
            $orders->setCollection($sortedOrders);
        }

        $statuses = Order::getStatuses();
        $customers = Customer::where('user_id', Auth::id())->orderBy('name')->get();
        $products = Product::where('user_id', Auth::id())->orderBy('name')->get();

        return view('orders.index', compact('orders', 'statuses', 'customers', 'products'));
    }

    /**
     * Check if order items are available in inventory
     */
    private function checkInventoryAvailability(Order $order)
    {
        $allAvailable = true;
        $partiallyAvailable = false;

        foreach ($order->items as $item) {
            // Calculate available stock for this product and size
            $imported = InventoryImportItem::where('product_id', $item->product_id)
                ->where('size', $item->size)
                ->sum('quantity');

            $sold = OrderItem::where('product_id', $item->product_id)
                ->where('size', $item->size)
                ->whereHas('order', function ($q) {
                    $q->whereIn('status', [Order::STATUS_SHIPPING, Order::STATUS_DELIVERED]);
                })
                ->sum('quantity');

            // Get total manually exported
            $exported = \App\Models\InventoryExportItem::where('product_id', $item->product_id)
                ->where('size', $item->size)
                ->sum('quantity');

            $available = $imported - $sold - $exported;

            if ($available < $item->quantity) {
                $allAvailable = false;
            } else {
                $partiallyAvailable = true;
            }
        }

        if ($allAvailable && $order->items->count() > 0) {
            return 'full'; // Hàng đủ
        } elseif ($partiallyAvailable) {
            return 'partial'; // Hàng thiếu một phần
        } else {
            return 'none'; // Chưa có hàng
        }
    }

    public function create()
    {
        $customers = Customer::where('user_id', Auth::id())->orderBy('name')->get();
        $products = Product::where('user_id', Auth::id())->orderBy('name')->get();
        $sizes = OrderItem::getSizes();

        return view('orders.create', compact('customers', 'products', 'sizes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'deposit_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
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
                'user_id' => Auth::id(),
                'customer_id' => $validated['customer_id'],
                'status' => Order::STATUS_NEW,
                'total_amount' => $totalAmount,
                'deposit_amount' => $validated['deposit_amount'] ?? 0,
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'note' => $validated['note'] ?? null,
                'shipping_code' => $validated['shipping_code'] ?? null,
                'shipping_image' => $shippingImagePath,
                'created_at' => Carbon::parse($request->input('created_at', now())),
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
        
        // Check inventory availability for each item
        foreach ($order->items as $item) {
            $item->inventory_check = $this->checkItemInventoryAvailability($item);
        }
        
        // Overall order inventory status
        $order->inventory_status = $this->checkInventoryAvailability($order);

        return view('orders.show', compact('order'));
    }

    /**
     * Check inventory availability for a single order item
     */
    private function checkItemInventoryAvailability(OrderItem $item)
    {
        // Calculate available stock for this product and size
        $imported = InventoryImportItem::where('product_id', $item->product_id)
            ->where('size', $item->size)
            ->sum('quantity');

        $sold = OrderItem::where('product_id', $item->product_id)
            ->where('size', $item->size)
            ->whereHas('order', function ($q) use ($item) {
                $q->whereIn('status', [Order::STATUS_SHIPPING, Order::STATUS_DELIVERED])
                  ->where('id', '!=', $item->order_id); // Exclude current order
            })
            ->sum('quantity');

        // Get total manually exported
        $exported = \App\Models\InventoryExportItem::where('product_id', $item->product_id)
            ->where('size', $item->size)
            ->sum('quantity');

        $available = $imported - $sold - $exported;
        $needed = $item->quantity;

        return [
            'available' => $available,
            'needed' => $needed,
            'shortage' => max(0, $needed - $available),
            'status' => $available >= $needed ? 'sufficient' : 'insufficient',
        ];
    }

    public function edit(Order $order)
    {
        $order->load(['customer', 'items.product']);
        $customers = Customer::where('user_id', Auth::id())->orderBy('name')->get();
        $products = Product::where('user_id', Auth::id())->orderBy('name')->get();
        $sizes = OrderItem::getSizes();
        $statuses = Order::getStatuses();

        return view('orders.edit', compact('order', 'customers', 'products', 'sizes', 'statuses'));
    }

    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'deposit_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'status' => 'required|in:' . implode(',', array_keys(Order::getStatuses())),
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
                'status' => $validated['status'],
                'deposit_amount' => $validated['deposit_amount'] ?? 0,
                'discount_amount' => $validated['discount_amount'] ?? 0,
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
            ->where('user_id', Auth::id())
            ->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật ' . count($validated['order_ids']) . ' đơn hàng thành công!',
        ]);
    }

    public function split(Request $request, Order $order)
    {
        $request->validate([
            'items'                => 'required|array|min:1',
            'items.*.order_item_id' => 'required|exists:order_items,id',
            'items.*.quantity'     => 'required|integer|min:1',
            'status'               => 'required|in:' . implode(',', array_keys(Order::getStatuses())),
            'deposit_amount'       => 'nullable|numeric|min:0',
            'discount_amount'      => 'nullable|numeric|min:0',
            'note'                 => 'nullable|string|max:1000',
        ]);

        // Validate all items belong to this order and quantities are valid
        $splitItems = [];
        foreach ($request->items as $itemData) {
            $orderItem = $order->items()->find($itemData['order_item_id']);

            if (!$orderItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sản phẩm không thuộc đơn hàng này.',
                ], 422);
            }

            if ($itemData['quantity'] > $orderItem->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => "Số lượng tách của \"{$orderItem->product->name}\" vượt quá số lượng hiện có ({$orderItem->quantity}).",
                ], 422);
            }

            $splitItems[] = [
                'order_item'    => $orderItem,
                'split_quantity' => (int) $itemData['quantity'],
            ];
        }

        DB::beginTransaction();

        try {
            // 1. Create new order with same customer
            $newOrder = Order::create([
                'user_id'         => Auth::id(),
                'customer_id'     => $order->customer_id,
                'status'          => $request->status,
                'deposit_amount'  => $request->deposit_amount ?? 0,
                'discount_amount' => $request->discount_amount ?? 0,
                'note'            => $request->note ?? '',
                'total_amount'    => 0, // Will be calculated below
            ]);

            $newTotalAmount = 0;

            // 2. Process each item
            foreach ($splitItems as $data) {
                $originalItem = $data['order_item'];
                $splitQty     = $data['split_quantity'];

                // Create item in new order (copy image path, don't move the file)
                $newOrder->items()->create([
                    'product_id' => $originalItem->product_id,
                    'size'       => $originalItem->size,
                    'quantity'   => $splitQty,
                    'price'      => $originalItem->price,
                    'note'       => $originalItem->note,
                    'image'      => $originalItem->image, // Share same image path
                ]);

                $newTotalAmount += $originalItem->price * $splitQty;

                // Update or delete original item
                if ($splitQty >= $originalItem->quantity) {
                    // Full quantity moved → delete original item (keep image file since new order references it)
                    $originalItem->delete();
                } else {
                    // Partial quantity → reduce original
                    $originalItem->update([
                        'quantity' => $originalItem->quantity - $splitQty,
                    ]);
                }
            }

            // 3. Update totals
            $newOrder->update(['total_amount' => $newTotalAmount]);

            // Recalculate original order total
            $originalNewTotal = $order->items()->sum(DB::raw('price * quantity'));
            $order->update(['total_amount' => $originalNewTotal]);

            DB::commit();

            return response()->json([
                'success'      => true,
                'message'      => "Đã tách thành công sang đơn hàng #{$newOrder->id}",
                'new_order_id' => $newOrder->id,
                'redirect_url' => route('orders.show', $newOrder),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage(),
            ], 500);
        }
    }
}
