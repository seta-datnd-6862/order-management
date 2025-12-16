<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryImport extends Model
{
    protected $fillable = [
        'import_code',
        'supplier',
        'import_date',
        'note',
    ];

    protected $casts = [
        'import_date' => 'date',
    ];

    const SUPPLIERS = [
        'taobao' => 'Taobao',
        '1688' => '1688',
        'tmall' => 'Tmall',
        'alibaba' => 'Alibaba',
        'other' => 'Khác',
    ];

    public static function getSuppliers()
    {
        return self::SUPPLIERS;
    }

    public static function generateImportCode()
    {
        $date = date('ymd');
        $lastImport = self::whereDate('created_at', today())->latest()->first();
        
        if ($lastImport) {
            $lastNumber = (int) substr($lastImport->import_code, -3);
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }
        
        return 'IMP' . $date . $newNumber;
    }

    public function items()
    {
        return $this->hasMany(InventoryImportItem::class);
    }

    public function getTotalQuantityAttribute()
    {
        return $this->items->sum('quantity');
    }

    public function getTotalProductsAttribute()
    {
        return $this->items->count();
    }

    public function getSupplierLabelAttribute()
    {
        return self::SUPPLIERS[$this->supplier] ?? $this->supplier;
    }
}
