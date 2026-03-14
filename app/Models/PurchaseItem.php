<?php

namespace App\Models;

use App\Models\Concerns\HasDelStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    use HasFactory, HasDelStatus;

    protected $fillable = [
        'purchase_id','item_id','name','qty','price','line_total','del_status',
    ];
}
