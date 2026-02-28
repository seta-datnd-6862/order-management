<?php

namespace App\Http\Controllers;

use App\Models\InventoryImport;
use App\Models\InventoryImportItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryImportController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryImport::with(['items.product'])->where('user_id', Auth::id());

        // Filter by import code
        if ($request->filled('import_code')) {
            $query->where('import_code', 'like', '%' . $request->import_code . '%');
        }

        // Filter by supplier
        if ($request->filled('supplier')) {
            $query->where('supplier', $request->supplier);
        }

        // Filter by product
        if ($request->filled('product_id')) {
            $query->whereHas('items', function ($q) use ($request) {
                $q->where('product_id', $request->product_id);
            });
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('import_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('import_date', '<=', $request->date_to);
        }

        $imports = $query->latest('import_date')->paginate(20);
        $suppliers = InventoryImport::getSuppliers();
        $products = Product::where('user_id', Auth::id())->orderBy('name')->get();

        return view('inventory.imports.index', compact('imports', 'suppliers', 'products'));
    }

    public function create()
    {
        $products = Product::where('user_id', Auth::id())->orderBy('name')->get();
        $suppliers = InventoryImport::getSuppliers();
        $sizes = InventoryImportItem::getSizes();

        return view('inventory.imports.create', compact('products', 'suppliers', 'sizes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'import_code' => 'required|unique:inventory_imports,import_code',
            'supplier' => 'required|in:' . implode(',', array_keys(InventoryImport::getSuppliers())),
            'import_date' => 'required|date',
            'note' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_note' => 'nullable|string',
            'items.*.sizes' => 'required|array|min:1',
            'items.*.sizes.*.size' => 'required|string',
            'items.*.sizes.*.quantity' => 'required|integer|min:1',
            'items.*.sizes.*.note' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $import = InventoryImport::create([
                'user_id' => Auth::id(),
                'import_code' => $validated['import_code'],
                'supplier' => $validated['supplier'],
                'import_date' => $validated['import_date'],
                'note' => $validated['note'],
            ]);

            // Process each product with its sizes
            foreach ($validated['items'] as $item) {
                foreach ($item['sizes'] as $sizeData) {
                    $import->items()->create([
                        'product_id' => $item['product_id'],
                        'size' => $sizeData['size'],
                        'quantity' => $sizeData['quantity'],
                        'note' => $sizeData['note'] ?? null,
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('inventory.imports.show', $import)
                ->with('success', 'Tạo đơn nhập kho thành công!');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function show(InventoryImport $import)
    {
        $import->load(['items.product']);
        return view('inventory.imports.show', compact('import'));
    }

    public function edit(InventoryImport $import)
    {
        $import->load(['items.product']);
        $products = Product::where('user_id', Auth::id())->orderBy('name')->get();
        $suppliers = InventoryImport::getSuppliers();
        $sizes = InventoryImportItem::getSizes();

        return view('inventory.imports.edit', compact('import', 'products', 'suppliers', 'sizes'));
    }

    public function update(Request $request, InventoryImport $import)
    {
        $validated = $request->validate([
            'import_code' => 'required|unique:inventory_imports,import_code,' . $import->id,
            'supplier' => 'required|in:' . implode(',', array_keys(InventoryImport::getSuppliers())),
            'import_date' => 'required|date',
            'note' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_note' => 'nullable|string',
            'items.*.sizes' => 'required|array|min:1',
            'items.*.sizes.*.id' => 'nullable|exists:inventory_import_items,id',
            'items.*.sizes.*.size' => 'required|string',
            'items.*.sizes.*.quantity' => 'required|integer|min:1',
            'items.*.sizes.*.note' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $import->update([
                'import_code' => $validated['import_code'],
                'supplier' => $validated['supplier'],
                'import_date' => $validated['import_date'],
                'note' => $validated['note'],
            ]);

            // Collect all size IDs from the request
            $existingSizeIds = [];
            foreach ($validated['items'] as $item) {
                foreach ($item['sizes'] as $sizeData) {
                    if (!empty($sizeData['id'])) {
                        $existingSizeIds[] = $sizeData['id'];
                    }
                }
            }

            // Delete items (sizes) not in the request
            $import->items()->whereNotIn('id', $existingSizeIds)->delete();

            // Update or create items
            foreach ($validated['items'] as $item) {
                foreach ($item['sizes'] as $sizeData) {
                    if (!empty($sizeData['id'])) {
                        // Update existing size
                        $sizeItem = $import->items()->find($sizeData['id']);
                        if ($sizeItem) {
                            $sizeItem->update([
                                'product_id' => $item['product_id'],
                                'size' => $sizeData['size'],
                                'quantity' => $sizeData['quantity'],
                                'note' => $sizeData['note'] ?? null,
                            ]);
                        }
                    } else {
                        // Create new size
                        $import->items()->create([
                            'product_id' => $item['product_id'],
                            'size' => $sizeData['size'],
                            'quantity' => $sizeData['quantity'],
                            'note' => $sizeData['note'] ?? null,
                        ]);
                    }
                }
            }

            DB::commit();
            return redirect()->route('inventory.imports.show', $import)
                ->with('success', 'Cập nhật đơn nhập kho thành công!');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function destroy(InventoryImport $import)
    {
        DB::beginTransaction();
        try {
            $import->items()->delete();
            $import->delete();

            DB::commit();
            return redirect()->route('inventory.imports.index')
                ->with('success', 'Xóa đơn nhập kho thành công!');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }
}
