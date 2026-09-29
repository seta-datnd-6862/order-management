<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryImportItem extends Model
{
    protected $fillable = [
        'inventory_import_id',
        'product_id',
        'size',
        'quantity',
        'note',
    ];

    public static function getSizes()
    {
        return [
            // Giày dép
            '20', '21', '22', '23', '24', '25', '26', '27', '28', '29',
            '30', '31', '32', '33', '34', '35', '36', '37', '38', '39',
            '40', '41', '42', '43',
            // Size chữ
            'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL',
            // Quần áo
            '66', '73', '80', '90', '100', '110', '120', '130', '140', '150', '160', '170', '180',
        ];
    }

    public function inventoryImport()
    {
        return $this->belongsTo(InventoryImport::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
