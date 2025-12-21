<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\InventoryImport;
use App\Models\InventoryImportItem;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();

        // Filter by product
        if ($request->filled('product_id')) {
            $query->where('id', $request->product_id);
        }

        // Filter by search
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $products = $query->orderBy('name')->get();

        // Calculate inventory for each product
        $inventory = [];
        $sizes = InventoryImportItem::getSizes();

        foreach ($products as $product) {
            $productInventory = [
                'product' => $product,
                'sizes' => [],
                'total_imported' => 0,
                'total_sold' => 0,
                'total_exported' => 0,
                'total_stock' => 0,
            ];

            foreach ($sizes as $size) {
                // Get total imported for this product and size
                $imported = InventoryImportItem::where('product_id', $product->id)
                    ->where('size', $size)
                    ->sum('quantity');

                // Get total sold for this product and size (STATUS_SHIPPING and STATUS_DELIVERED)
                $sold = OrderItem::where('product_id', $product->id)
                    ->where('size', $size)
                    ->whereHas('order', function ($q) {
                        $q->whereIn('status', [Order::STATUS_SHIPPING, Order::STATUS_DELIVERED]);
                    })
                    ->sum('quantity');

                // Get total manually exported for this product and size
                $exported = \App\Models\InventoryExportItem::where('product_id', $product->id)
                    ->where('size', $size)
                    ->sum('quantity');

                $stock = $imported - $sold - $exported;

                if ($imported > 0 || $sold > 0 || $exported > 0) {
                    $productInventory['sizes'][$size] = [
                        'imported' => $imported,
                        'sold' => $sold,
                        'exported' => $exported,
                        'stock' => $stock,
                    ];

                    $productInventory['total_imported'] += $imported;
                    $productInventory['total_sold'] += $sold;
                    $productInventory['total_exported'] += $exported;
                    $productInventory['total_stock'] += $stock;
                }
            }

            // Only include products that have inventory data
            if ($productInventory['total_imported'] > 0 || $productInventory['total_sold'] > 0) {
                $inventory[] = $productInventory;
            }
        }

        $allProducts = Product::orderBy('name')->get();

        return view('inventory.index', compact('inventory', 'allProducts'));
    }

    public function detail(Request $request, Product $product)
    {
        $sizes = InventoryImportItem::getSizes();
        $details = [];

        foreach ($sizes as $size) {
            // Get imports
            $imports = InventoryImportItem::with('inventoryImport')
                ->where('product_id', $product->id)
                ->where('size', $size)
                ->get();

            // Get sales
            $sales = OrderItem::with('order.customer')
                ->where('product_id', $product->id)
                ->where('size', $size)
                ->whereHas('order', function ($q) {
                    $q->whereIn('status', [Order::STATUS_SHIPPING, Order::STATUS_DELIVERED]);
                })
                ->get();

            // Get manual exports
            $exports = \App\Models\InventoryExportItem::with('inventoryExport')
                ->where('product_id', $product->id)
                ->where('size', $size)
                ->get();

            $totalImported = $imports->sum('quantity');
            $totalSold = $sales->sum('quantity');
            $totalExported = $exports->sum('quantity');

            if ($totalImported > 0 || $totalSold > 0 || $totalExported > 0) {
                $details[$size] = [
                    'imports' => $imports,
                    'sales' => $sales,
                    'exports' => $exports,
                    'total_imported' => $totalImported,
                    'total_sold' => $totalSold,
                    'total_exported' => $totalExported,
                    'stock' => $totalImported - $totalSold - $totalExported,
                ];
            }
        }

        return view('inventory.detail', compact('product', 'details'));
    }
}
