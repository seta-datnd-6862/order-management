<?php

namespace App\Http\Controllers;

use App\Models\InventoryImport;
use App\Models\InventoryImportItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryImportController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryImport::with(['items.product']);

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
        $products = Product::orderBy('name')->get();

        return view('inventory.imports.index', compact('imports', 'suppliers', 'products'));
    }

    public function create()
    {
        $products = Product::orderBy('name')->get();
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
            'items.*.size' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.note' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $import = InventoryImport::create([
                'import_code' => $validated['import_code'],
                'supplier' => $validated['supplier'],
                'import_date' => $validated['import_date'],
                'note' => $validated['note'],
            ]);

            foreach ($validated['items'] as $item) {
                $import->items()->create([
                    'product_id' => $item['product_id'],
                    'size' => $item['size'],
                    'quantity' => $item['quantity'],
                    'note' => $item['note'] ?? null,
                ]);
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
        $products = Product::orderBy('name')->get();
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
            'items.*.id' => 'nullable|exists:inventory_import_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.size' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.note' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $import->update([
                'import_code' => $validated['import_code'],
                'supplier' => $validated['supplier'],
                'import_date' => $validated['import_date'],
                'note' => $validated['note'],
            ]);

            // Track existing item IDs from request
            $existingItemIds = collect($validated['items'])
                ->pluck('id')
                ->filter()
                ->toArray();

            // Delete items not in the request
            $import->items()->whereNotIn('id', $existingItemIds)->delete();

            // Update or create items
            foreach ($validated['items'] as $itemData) {
                if (!empty($itemData['id'])) {
                    // Update existing item
                    $item = $import->items()->find($itemData['id']);
                    if ($item) {
                        $item->update([
                            'product_id' => $itemData['product_id'],
                            'size' => $itemData['size'],
                            'quantity' => $itemData['quantity'],
                            'note' => $itemData['note'] ?? null,
                        ]);
                    }
                } else {
                    // Create new item
                    $import->items()->create([
                        'product_id' => $itemData['product_id'],
                        'size' => $itemData['size'],
                        'quantity' => $itemData['quantity'],
                        'note' => $itemData['note'] ?? null,
                    ]);
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
