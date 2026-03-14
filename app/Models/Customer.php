<?php

namespace App\Models;

use App\Models\Concerns\HasDelStatus;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $appends = ['photo_url'];

    use HasDelStatus;

    protected $fillable = [
        'user_id',
        'business_id',
        'name',
        'phone',
        'opening_balance',
        'photo_path',
        'is_archived',
        'del_status',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo_path) {
            return null;
        }

        return url('storage/app/public/' . ltrim($this->photo_path, '/'));
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
