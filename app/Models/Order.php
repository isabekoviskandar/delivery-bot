<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = 
    [
        'user_id',
        'address',
        'time',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class ,'user_id');
    }

    public function food_order()
    {
        return $this->hasMany(FoodOrder::class);
    }
}
