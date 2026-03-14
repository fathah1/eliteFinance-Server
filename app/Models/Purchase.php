<?php

namespace App\Models;

use App\Models\Concerns\HasDelStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $appends = ['note_photo_urls'];

    use HasFactory, HasDelStatus;

    protected $fillable = [
        'user_id','business_id','supplier_id','purchase_number','date','party_name','party_phone',
        'manual_amount','subtotal','additional_charges_total','discount_value','discount_type','discount_label','discount_amount',
        'total_amount','vat_amount','payment_mode','paid_amount','balance_due','payment_status','due_date','payment_reference','private_notes','note_photos',
        'del_status',
    ];

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'note_photos' => 'array',
    ];

    public function getNotePhotoUrlsAttribute(): array
    {
        $paths = is_array($this->note_photos) ? $this->note_photos : [];

        return array_values(array_filter(array_map(function ($path) {
            if (!is_string($path) || trim($path) === '') {
                return null;
            }

            return url('storage/app/public/' . ltrim($path, '/'));
        }, $paths)));
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }
}
