<?php

namespace App\Models;

use App\Models\Concerns\HasDelStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $appends = ['photo_url'];

    use HasFactory, HasDelStatus;

    protected $fillable = [
        'user_id',
        'business_id',
        'type',
        'name',
        'unit',
        'sale_price',
        'purchase_price',
        'tax_included',
        'opening_stock',
        'current_stock',
        'low_stock_alert',
        'photo_path',
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
