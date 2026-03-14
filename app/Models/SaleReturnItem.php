<?php

namespace App\Models;

use App\Models\Concerns\HasDelStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleReturnItem extends Model
{
    use HasFactory, HasDelStatus;

    protected $fillable = [
        'sale_return_id',
        'item_id',
        'name',
        'qty',
        'price',
        'line_total',
        'del_status',
    ];

    public function saleReturn()
    {
        return $this->belongsTo(SaleReturn::class);
    }
}
