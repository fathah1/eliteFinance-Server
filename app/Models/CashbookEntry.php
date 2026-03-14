<?php

namespace App\Models;

use App\Models\Concerns\HasDelStatus;
use Illuminate\Database\Eloquent\Model;

class CashbookEntry extends Model
{
    use HasDelStatus;

    protected $appends = ['photo_url'];

    protected $fillable = [
        'user_id',
        'business_id',
        'direction',
        'amount',
        'payment_mode',
        'note',
        'date',
        'photo_path',
        'created_at',
        'del_status',
    ];

    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo_path) {
            return null;
        }

        return url('storage/app/public/' . ltrim($this->photo_path, '/'));
    }
}
