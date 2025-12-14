<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'facebook_link',
        'zalo_link',
        'note',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
