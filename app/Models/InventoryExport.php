<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryExport extends Model
{
    protected $fillable = [
        'export_code',
        'reason',
        'export_date',
        'note',
    ];

    protected $casts = [
        'export_date' => 'date',
    ];

    const REASONS = [
        'defective' => 'Hàng lỗi',
        'gift' => 'Tặng khách',
        'sample' => 'Mẫu / Demo',
        'loss' => 'Hao hụt / Mất mát',
        'return_supplier' => 'Trả nhà cung cấp',
        'internal_use' => 'Sử dụng nội bộ',
        'other' => 'Khác',
    ];

    public static function getReasons()
    {
        return self::REASONS;
    }

    public static function getReasonColors()
    {
        return [
            'defective' => 'bg-red-100 text-red-800',
            'gift' => 'bg-green-100 text-green-800',
            'sample' => 'bg-blue-100 text-blue-800',
            'loss' => 'bg-orange-100 text-orange-800',
            'return_supplier' => 'bg-purple-100 text-purple-800',
            'internal_use' => 'bg-indigo-100 text-indigo-800',
            'other' => 'bg-gray-100 text-gray-800',
        ];
    }

    public static function generateExportCode()
    {
        $date = date('ymd');
        $lastExport = self::whereDate('created_at', today())->latest()->first();
        
        if ($lastExport) {
            $lastNumber = (int) substr($lastExport->export_code, -3);
            $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }
        
        return 'EXP' . $date . $newNumber;
    }

    public function items()
    {
        return $this->hasMany(InventoryExportItem::class);
    }

    public function getTotalQuantityAttribute()
    {
        return $this->items->sum('quantity');
    }

    public function getTotalProductsAttribute()
    {
        return $this->items->count();
    }

    public function getReasonLabelAttribute()
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }

    public function getReasonColorAttribute()
    {
        return self::getReasonColors()[$this->reason] ?? 'bg-gray-100 text-gray-800';
    }
}
