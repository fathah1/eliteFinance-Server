<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemStockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_id',
        'item_id',
        'sale_id',
        'sale_bill_number',
        'type',
        'quantity',
        'price',
        'date',
        'note',
    ];
}
