<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'image',
        'size',
        'quantity',
        'price',
        'note',
    ];

    public static function getSizes()
    {
        return [
            'S', 'M', 'L', 'XL', 'XXL', 'XXXL',
            '73', '80', '90', '100', '110', '120', '130', '140',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return asset('storage/' . $this->image);
        }
        return $this->product?->image_url;
    }

    public function getSubtotalAttribute()
    {
        return $this->price * $this->quantity;
    }
}
