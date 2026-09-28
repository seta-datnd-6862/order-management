<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignOrderItem extends Model
{
    protected $fillable = [
        'campaign_order_id',
        'sequence',
        'product_name_raw',
        'product_id',
        'product_match',
        'product_suggestions',
        'size_raw',
        'size',
        'quantity',
        'price',
        'note',
        'raw_text',
    ];

    protected $casts = [
        'product_suggestions' => 'array',
        'quantity' => 'integer',
        'price' => 'decimal:0',
    ];

    public function campaignOrder()
    {
        return $this->belongsTo(CampaignOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
