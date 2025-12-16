<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Order extends Model
{
    protected $fillable = [
        'customer_id',
        'status',
        'total_amount',
        'deposit_amount',
        'note',
        'shipping_code',
        'shipping_image',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
    ];

    const STATUS_NEW = 'new';
    const STATUS_PREPARING = 'preparing';
    const STATUS_ORDERED = 'ordered';
    const STATUS_SHIPPING = 'shipping';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_FAILED = 'failed';

    public static function getStatuses()
    {
        return [
            self::STATUS_NEW => 'Đơn mới',
            self::STATUS_PREPARING => 'Chuẩn bị đặt hàng',
            self::STATUS_ORDERED => 'Đã đặt hàng',
            self::STATUS_SHIPPING => 'Đang ship',
            self::STATUS_DELIVERED => 'Giao thành công',
            self::STATUS_FAILED => 'Giao thất bại',
        ];
    }

    public static function getStatusColors()
    {
        return [
            self::STATUS_NEW => 'bg-blue-100 text-blue-800',
            self::STATUS_PREPARING => 'bg-yellow-100 text-yellow-800',
            self::STATUS_ORDERED => 'bg-purple-100 text-purple-800',
            self::STATUS_SHIPPING => 'bg-orange-100 text-orange-800',
            self::STATUS_DELIVERED => 'bg-green-100 text-green-800',
            self::STATUS_FAILED => 'bg-red-100 text-red-800',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getStatusLabelAttribute()
    {
        return self::getStatuses()[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute()
    {
        return self::getStatusColors()[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    // Accessors
    public function getShippingImageUrlAttribute()
    {
        if ($this->shipping_image) {
            return Storage::url($this->shipping_image);
        }
        return null;
    }

    public function getRemainingAmountAttribute()
    {
        return $this->total_amount - $this->deposit_amount;
    }

    public function getDepositPercentageAttribute()
    {
        if ($this->total_amount > 0) {
            return ($this->deposit_amount / $this->total_amount) * 100;
        }
        return 0;
    }

    public function calculateTotal()
    {
        $this->total_amount = $this->items->sum(function ($item) {
            return $item->price * $item->quantity;
        });
        $this->save();
    }
}
