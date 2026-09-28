<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignOrder extends Model
{
    protected $fillable = [
        'order_campaign_id',
        'sequence',
        'order_date',
        'customer_name_raw',
        'customer_note_raw',
        'customer_id',
        'customer_match',
        'customer_suggestions',
        'deposit_amount',
        'discount_amount',
        'note',
        'is_marked_done',
        'warnings',
        'raw_line',
        'status',
        'order_id',
        'processed_at',
    ];

    protected $casts = [
        'order_date' => 'date',
        'customer_suggestions' => 'array',
        'warnings' => 'array',
        'is_marked_done' => 'boolean',
        'deposit_amount' => 'decimal:0',
        'discount_amount' => 'decimal:0',
        'processed_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_CREATED = 'created';
    const STATUS_SKIPPED = 'skipped';

    const MATCH_EXACT = 'exact';
    const MATCH_HIGH = 'high';
    const MATCH_LOW = 'low';
    const MATCH_NONE = 'none';

    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Chờ tạo',
            self::STATUS_CREATED => 'Đã tạo',
            self::STATUS_SKIPPED => 'Đã bỏ qua',
        ];
    }

    public static function getStatusColors(): array
    {
        return [
            self::STATUS_PENDING => 'bg-yellow-100 text-yellow-800',
            self::STATUS_CREATED => 'bg-green-100 text-green-800',
            self::STATUS_SKIPPED => 'bg-gray-100 text-gray-600',
        ];
    }

    public function campaign()
    {
        return $this->belongsTo(OrderCampaign::class, 'order_campaign_id');
    }

    public function items()
    {
        return $this->hasMany(CampaignOrderItem::class)->orderBy('sequence');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::getStatuses()[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::getStatusColors()[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Đơn nháp đã đủ dữ liệu để tạo đơn thật chưa.
     */
    public function getIsReadyAttribute(): bool
    {
        if (!$this->customer_id) {
            return false;
        }

        if ($this->items->isEmpty()) {
            return false;
        }

        foreach ($this->items as $item) {
            if (!$item->product_id || !$item->size) {
                return false;
            }
        }

        return true;
    }

    /**
     * Những gì còn thiếu, để hiện nhanh ở danh sách.
     */
    public function getMissingLabelsAttribute(): array
    {
        $missing = [];

        if (!$this->customer_id) {
            $missing[] = 'khách hàng';
        }

        if ($this->items->isEmpty()) {
            $missing[] = 'sản phẩm';
        } else {
            if ($this->items->whereNull('product_id')->isNotEmpty()) {
                $missing[] = 'sản phẩm';
            }

            if ($this->items->whereNull('size')->isNotEmpty()) {
                $missing[] = 'size';
            }
        }

        return $missing;
    }

    public function getEstimatedTotalAttribute(): float
    {
        return (float) $this->items->sum(fn ($item) => (float) ($item->price ?? 0) * $item->quantity);
    }
}
