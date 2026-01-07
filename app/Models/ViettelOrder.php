<?php
// app/Models/ViettelOrder.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ViettelOrder extends Model
{
    // ========== STATUS CONSTANTS ==========
    const STATUS_CREATED = 'created';
    const STATUS_SHIPPING = 'shipping';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';

    // ========== SERVICE CODES ==========
    const SERVICE_VCN = 'VCN'; // Viettel Chuyển phát nhanh
    const SERVICE_PHS = 'PHS'; // Phát hỏa tốc
    const SERVICE_VCBO = 'VCBO'; // Viettel Chuyển phát thường

    protected $fillable = [
        'order_id',
        'tracking_number',
        'service_code',
        'status',
        'receiver_name',
        'receiver_phone',
        'receiver_address',
        'product_weight',
        'money_collection',
        'shipping_fee',
        'estimated_delivery_time',
        'note',
        'api_response',
    ];

    protected $casts = [
        'product_weight' => 'integer',
        'money_collection' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'estimated_delivery_time' => 'double',
        'api_response' => 'array',
    ];

    // ========== RELATIONSHIPS ==========
    
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // ========== HELPER METHODS ==========
    
    public static function getStatuses(): array
    {
        return [
            self::STATUS_CREATED => 'Đã tạo đơn',
            self::STATUS_SHIPPING => 'Đang vận chuyển',
            self::STATUS_DELIVERED => 'Đã giao hàng',
            self::STATUS_CANCELLED => 'Đã hủy',
        ];
    }

    public static function getStatusColors(): array
    {
        return [
            self::STATUS_CREATED => 'bg-blue-100 text-blue-800',
            self::STATUS_SHIPPING => 'bg-yellow-100 text-yellow-800',
            self::STATUS_DELIVERED => 'bg-green-100 text-green-800',
            self::STATUS_CANCELLED => 'bg-red-100 text-red-800',
        ];
    }

    public static function getServiceCodes(): array
    {
        return [
            self::SERVICE_VCN => 'Chuyển phát nhanh',
            self::SERVICE_PHS => 'Chuyển phát hỏa tốc',
            self::SERVICE_VCBO => 'Chuyển phát tiêu chuẩn',
        ];
    }

    // ========== ACCESSORS ==========
    
    public function getStatusLabelAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::getStatusColors()[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    public function getServiceNameAttribute(): string
    {
        return self::getServiceCodes()[$this->service_code] ?? $this->service_code;
    }

    public function getFormattedShippingFeeAttribute(): string
    {
        return number_format($this->shipping_fee) . 'đ';
    }

    public function getFormattedMoneyCollectionAttribute(): string
    {
        return number_format($this->money_collection) . 'đ';
    }

    public function getFormattedDeliveryTimeAttribute(): string
    {
        if (!$this->estimated_delivery_time) return '';
        return round($this->estimated_delivery_time) . ' giờ';
    }

    // ========== SCOPES ==========
    
    public function scopeShipping($query)
    {
        return $query->where('status', self::STATUS_SHIPPING);
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', self::STATUS_DELIVERED);
    }
}
