<?php

namespace App\Testing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    protected $guarded = [];

    use HasFactory;

    public function scopePriceOver($query, $price)
    {
        return $query->where('price', '>', $price);
    }
}
