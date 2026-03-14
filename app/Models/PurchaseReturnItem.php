<?php

namespace App\Models;

use App\Models\Concerns\HasDelStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturnItem extends Model
{
    use HasFactory, HasDelStatus;

    protected $fillable = [
        'purchase_return_id',
        'item_id',
        'name',
        'qty',
        'price',
        'line_total',
        'del_status',
    ];

    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class);
    }
}
