<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryExportItem extends Model
{
    protected $fillable = [
        'inventory_export_id',
        'product_id',
        'size',
        'quantity',
        'note',
    ];

    public static function getSizes()
    {
        return [
            '20', '25', '26', 'XS',
            'S', 'M', 'L', 'XL', 'XXL', 'XXXL', '66',
            '73', '80', '90', '100', '110', '120', '130', '140', '150', '160', '170', '180',
        ];
    }

    public function inventoryExport()
    {
        return $this->belongsTo(InventoryExport::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
