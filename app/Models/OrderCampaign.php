<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderCampaign extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'source_note',
        'raw_json',
        'status',
        'import_warnings',
    ];

    protected $casts = [
        'import_warnings' => 'array',
    ];

    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function campaignOrders()
    {
        return $this->hasMany(CampaignOrder::class)->orderBy('sequence');
    }

    public function getTotalCountAttribute(): int
    {
        return $this->campaignOrders()->count();
    }

    public function getCreatedCountAttribute(): int
    {
        return $this->campaignOrders()->where('status', CampaignOrder::STATUS_CREATED)->count();
    }

    public function getSkippedCountAttribute(): int
    {
        return $this->campaignOrders()->where('status', CampaignOrder::STATUS_SKIPPED)->count();
    }

    public function getPendingCountAttribute(): int
    {
        return $this->campaignOrders()->where('status', CampaignOrder::STATUS_PENDING)->count();
    }

    /**
     * % đơn đã xử lý (đã tạo + đã bỏ qua).
     */
    public function getProgressPercentAttribute(): int
    {
        $total = $this->total_count;

        if ($total === 0) {
            return 0;
        }

        return (int) round((($this->created_count + $this->skipped_count) / $total) * 100);
    }

    /**
     * Đơn nháp tiếp theo cần xử lý, tính từ một vị trí nhất định.
     */
    public function nextPending(?int $afterSequence = null): ?CampaignOrder
    {
        $query = $this->campaignOrders()->where('status', CampaignOrder::STATUS_PENDING);

        if ($afterSequence !== null) {
            $next = (clone $query)->where('sequence', '>', $afterSequence)->first();

            if ($next) {
                return $next;
            }
        }

        return $query->first();
    }

    /**
     * Đánh dấu hoàn thành khi không còn đơn nháp nào đang chờ.
     */
    public function refreshStatus(): void
    {
        $status = $this->pending_count === 0 && $this->total_count > 0
            ? self::STATUS_COMPLETED
            : self::STATUS_IN_PROGRESS;

        if ($this->status !== $status) {
            $this->update(['status' => $status]);
        }
    }
}
