<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ChatbotController extends Controller
{
    public function searchCustomers(Request $request)
    {
        $q = $request->get('q', '');

        $customers = Customer::where('user_id', Auth::id())
            ->where(function ($query) use ($q) {
                $query->where('name', 'LIKE', "%{$q}%")
                      ->orWhere('phone', 'LIKE', "%{$q}%");
            })
            ->limit(6)
            ->get(['id', 'name', 'phone']);

        return response()->json($customers);
    }

    public function searchProducts(Request $request)
    {
        $q = $request->get('q', '');

        $products = Product::where('user_id', Auth::id())
            ->where('name', 'LIKE', "%{$q}%")
            ->limit(6)
            ->get(['id', 'name', 'default_price']);

        return response()->json($products->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'default_price' => $p->default_price,
                'product_code' => $p->product_code,
            ];
        }));
    }

    public function createCustomer(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

        $customer = Customer::create(array_merge($validated, [
            'user_id' => Auth::id(),
        ]));

        return response()->json([
            'success' => true,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
            ],
        ]);
    }

    public function createProduct(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'default_price' => 'nullable|numeric|min:0',
            'image_base64' => 'nullable|string',
        ]);

        $imagePath = null;
        if (!empty($validated['image_base64'])) {
            $imageData = $validated['image_base64'];
            if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $matches)) {
                $imageData = substr($imageData, strpos($imageData, ',') + 1);
                $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
                $imageData = base64_decode($imageData);
                $fileName = 'products/' . uniqid() . '.' . $extension;
                Storage::disk('public')->put($fileName, $imageData);
                $imagePath = $fileName;
            }
        }

        $product = Product::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'default_price' => $validated['default_price'] ?? null,
            'image' => $imagePath,
        ]);

        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'default_price' => $product->default_price,
                'product_code' => $product->product_code,
            ],
        ]);
    }

    public function createOrder(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'deposit_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'note' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.size' => 'required|in:' . implode(',', OrderItem::getSizes()),
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                $totalAmount += $item['price'] * $item['quantity'];
            }

            $order = Order::create([
                'user_id' => Auth::id(),
                'customer_id' => $validated['customer_id'],
                'status' => Order::STATUS_NEW,
                'total_amount' => $totalAmount,
                'deposit_amount' => $validated['deposit_amount'] ?? 0,
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'note' => $validated['note'] ?? null,
            ]);

            foreach ($validated['items'] as $itemData) {
                $order->items()->create([
                    'product_id' => $itemData['product_id'],
                    'size' => $itemData['size'],
                    'quantity' => $itemData['quantity'],
                    'price' => $itemData['price'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'order_url' => route('orders.show', $order),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
