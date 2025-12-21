<?php

namespace App\Http\Controllers;

use App\Models\InventoryExport;
use App\Models\InventoryExportItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryExportController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryExport::with(['items.product']);

        // Filter by export code
        if ($request->filled('export_code')) {
            $query->where('export_code', 'like', '%' . $request->export_code . '%');
        }

        // Filter by reason
        if ($request->filled('reason')) {
            $query->where('reason', $request->reason);
        }

        // Filter by product
        if ($request->filled('product_id')) {
            $query->whereHas('items', function ($q) use ($request) {
                $q->where('product_id', $request->product_id);
            });
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('export_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('export_date', '<=', $request->date_to);
        }

        $exports = $query->latest('export_date')->paginate(20);
        $reasons = InventoryExport::getReasons();
        $products = Product::orderBy('name')->get();

        return view('inventory.exports.index', compact('exports', 'reasons', 'products'));
    }

    public function create()
    {
        $products = Product::orderBy('name')->get();
        $reasons = InventoryExport::getReasons();
        $sizes = InventoryExportItem::getSizes();
        $exportCode = InventoryExport::generateExportCode();

        return view('inventory.exports.create', compact('products', 'reasons', 'sizes', 'exportCode'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'export_code' => 'required|unique:inventory_exports,export_code',
            'reason' => 'required|in:' . implode(',', array_keys(InventoryExport::getReasons())),
            'export_date' => 'required|date',
            'note' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.size' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.note' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $export = InventoryExport::create([
                'export_code' => $validated['export_code'],
                'reason' => $validated['reason'],
                'export_date' => $validated['export_date'],
                'note' => $validated['note'],
            ]);

            foreach ($validated['items'] as $item) {
                $export->items()->create([
                    'product_id' => $item['product_id'],
                    'size' => $item['size'],
                    'quantity' => $item['quantity'],
                    'note' => $item['note'] ?? null,
                ]);
            }

            DB::commit();
            return redirect()->route('inventory.exports.show', $export)
                ->with('success', 'Tạo đơn xuất kho thành công!');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function show(InventoryExport $export)
    {
        $export->load(['items.product']);
        return view('inventory.exports.show', compact('export'));
    }

    public function edit(InventoryExport $export)
    {
        $export->load(['items.product']);
        $products = Product::orderBy('name')->get();
        $reasons = InventoryExport::getReasons();
        $sizes = InventoryExportItem::getSizes();

        return view('inventory.exports.edit', compact('export', 'products', 'reasons', 'sizes'));
    }

    public function update(Request $request, InventoryExport $export)
    {
        $validated = $request->validate([
            'export_code' => 'required|unique:inventory_exports,export_code,' . $export->id,
            'reason' => 'required|in:' . implode(',', array_keys(InventoryExport::getReasons())),
            'export_date' => 'required|date',
            'note' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:inventory_export_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.size' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.note' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $export->update([
                'export_code' => $validated['export_code'],
                'reason' => $validated['reason'],
                'export_date' => $validated['export_date'],
                'note' => $validated['note'],
            ]);

            // Track existing item IDs
            $existingItemIds = collect($validated['items'])
                ->pluck('id')
                ->filter()
                ->toArray();

            // Delete items not in the request
            $export->items()->whereNotIn('id', $existingItemIds)->delete();

            // Update or create items
            foreach ($validated['items'] as $itemData) {
                if (!empty($itemData['id'])) {
                    // Update existing item
                    $item = $export->items()->find($itemData['id']);
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
                    $export->items()->create([
                        'product_id' => $itemData['product_id'],
                        'size' => $itemData['size'],
                        'quantity' => $itemData['quantity'],
                        'note' => $itemData['note'] ?? null,
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('inventory.exports.show', $export)
                ->with('success', 'Cập nhật đơn xuất kho thành công!');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()
                ->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function destroy(InventoryExport $export)
    {
        DB::beginTransaction();
        try {
            $export->items()->delete();
            $export->delete();

            DB::commit();
            return redirect()->route('inventory.exports.index')
                ->with('success', 'Xóa đơn xuất kho thành công!');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }
}
