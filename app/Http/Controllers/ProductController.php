<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Exports\ProductsExport;
use App\Imports\ProductsImport;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::where('user_id', Auth::id());
        
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->filled('product_code')) {
            $productID = ltrim($request->product_code, 'KBC0');
            $query->where('id', $productID);
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

        Product::create(array_merge($validated, ['user_id' => Auth::id()]));

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
        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Xóa sản phẩm thành công!');
    }

    public function forceDelete($id)
    {
        $product = Product::withTrashed()->where('user_id', Auth::id())->findOrFail($id);

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->forceDelete();

        return redirect()->route('products.trashed')
            ->with('success', 'Đã xóa vĩnh viễn sản phẩm!');
    }

    public function restore($id)
    {
        $product = Product::withTrashed()->where('user_id', Auth::id())->findOrFail($id);
        $product->restore();

        return redirect()->route('products.trashed')
            ->with('success', 'Đã khôi phục sản phẩm!');
    }

    public function trashed(Request $request)
    {
        $query = Product::onlyTrashed()->where('user_id', Auth::id());

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $products = $query->orderBy('deleted_at', 'desc')->paginate(20);

        return view('products.trashed', compact('products'));
    }

    public function export()
    {
        $filename = 'san-pham-' . now()->format('d-m-Y') . '.xlsx';
        return Excel::download(new ProductsExport, $filename);
    }

    public function importView()
    {
        return view('products.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ], [
            'file.required' => 'Vui lòng chọn file Excel',
            'file.mimes'    => 'File phải có định dạng .xlsx hoặc .xls',
            'file.max'      => 'File không được vượt quá 10MB',
        ]);

        $import = new ProductsImport();
        Excel::import($import, $request->file('file'));

        $message = "Import thành công: {$import->importedCount} sản phẩm mới, {$import->updatedCount} sản phẩm cập nhật.";

        if (!empty($import->errors)) {
            return redirect()->route('products.index')
                ->with('success', $message)
                ->with('import_errors', $import->errors);
        }

        return redirect()->route('products.index')->with('success', $message);
    }
}
