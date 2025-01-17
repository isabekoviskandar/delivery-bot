<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Food extends Model
{
    use HasFactory;

    protected $fillable = 
    [
        'category_id',
        'name',
        'price',
        'count',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function food_order()
    {
        return $this->hasMany(FoodOrder::class);
    }
}
