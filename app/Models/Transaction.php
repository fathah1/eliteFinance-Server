<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'customer_id',
        'amount',
        'type',
        'note',
        'synced',
        'created_at',
    ];

    protected $casts = [
        'synced' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
