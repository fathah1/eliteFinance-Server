<?php

namespace App\Models;

use App\Models\Concerns\HasDelStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleReturn extends Model
{
    use HasFactory, HasDelStatus;

    protected $fillable = [
        'user_id',
        'business_id',
        'sale_id',
        'customer_id',
        'return_number',
        'date',
        'settlement_mode',
        'total_amount',
        'note',
        'del_status',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(SaleReturnItem::class);
    }
}
