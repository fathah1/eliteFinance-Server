<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_id',
        'type',
        'name',
        'unit',
        'sale_price',
        'purchase_price',
        'tax_included',
        'opening_stock',
        'current_stock',
        'low_stock_alert',
        'photo_path',
    ];
}
