<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'image',
        'default_price',
        'note',
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getProductCodeAttribute()
    {
        return 'KBC' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }

    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return asset('storage/' . $this->image);
        }
        return null;
    }
}
