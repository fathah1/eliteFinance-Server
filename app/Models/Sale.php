<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_id',
        'customer_id',
        'bill_number',
        'date',
        'party_name',
        'party_phone',
        'manual_amount',
        'subtotal',
        'additional_charges_total',
        'discount_value',
        'discount_type',
        'discount_label',
        'discount_amount',
        'total_amount',
        'payment_mode',
        'received_amount',
        'balance_due',
        'payment_status',
        'due_date',
        'payment_reference',
        'private_notes',
        'note_photos',
    ];

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'note_photos' => 'array',
    ];

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }
}
