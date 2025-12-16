<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();
        
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(20);
        
        return view('products.index', compact('products'));
    }

    public function create()
    {
        return view('products.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'image_base64' => 'nullable|string',
            'default_price' => 'nullable|numeric|min:0',
            'note' => 'nullable|string',
        ]);

        // Xử lý ảnh base64
        if ($request->filled('image_base64')) {
            $imageData = $request->image_base64;
            
            // Lấy phần data sau "data:image/xxx;base64,"
            if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $matches)) {
                $imageData = substr($imageData, strpos($imageData, ',') + 1);
                $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
                
                $imageData = base64_decode($imageData);
                $fileName = 'products/' . uniqid() . '.' . $extension;
                
                Storage::disk('public')->put($fileName, $imageData);
                $validated['image'] = $fileName;
            }
        }
        
        unset($validated['image_base64']);

        Product::create($validated);

        return redirect()->route('products.index')
            ->with('success', 'Thêm sản phẩm thành công!');
    }

    public function edit(Product $product)
    {
        return view('products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'image_base64' => 'nullable|string',
            'default_price' => 'nullable|numeric|min:0',
            'note' => 'nullable|string',
        ]);

        // Xử lý ảnh base64
        if ($request->filled('image_base64')) {
            $imageData = $request->image_base64;
            
            if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $matches)) {
                // Xóa ảnh cũ
                if ($product->image) {
                    Storage::disk('public')->delete($product->image);
                }
                
                $imageData = substr($imageData, strpos($imageData, ',') + 1);
                $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
                
                $imageData = base64_decode($imageData);
                $fileName = 'products/' . uniqid() . '.' . $extension;
                
                Storage::disk('public')->put($fileName, $imageData);
                $validated['image'] = $fileName;
            }
        }
        
        unset($validated['image_base64']);

        $product->update($validated);

        return redirect()->route('products.index')
            ->with('success', 'Cập nhật sản phẩm thành công!');
    }

    public function destroy(Product $product)
    {
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
        
        $product->delete();
        
        return redirect()->route('products.index')
            ->with('success', 'Xóa sản phẩm thành công!');
    }
}
