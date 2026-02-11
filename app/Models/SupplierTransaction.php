<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'business_id',
        'supplier_id',
        'amount',
        'type',
        'note',
        'attachment_path',
        'synced',
        'created_at',
    ];

    protected $casts = [
        'synced' => 'boolean',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
