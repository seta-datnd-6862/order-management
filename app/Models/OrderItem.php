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
            '20', '21', '22', '23', '24', '25', '26', '27', '28', 'XS',
            'S', 'M', 'L', 'XL', 'XXL', 'XXXL', '66',
            '73', '80', '90', '100', '110', '120', '130', '140', '150', '160', '170', '180',
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
