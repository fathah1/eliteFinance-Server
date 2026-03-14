<?php

namespace App\Models;

use App\Models\Concerns\HasDelStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $appends = ['attachment_url'];

    use HasDelStatus;

    protected $fillable = [
        'user_id',
        'business_id',
        'customer_id',
        'amount',
        'type',
        'payment_number',
        'payment_mode',
        'sale_ids',
        'allocations',
        'note',
        'attachment_path',
        'synced',
        'created_at',
        'del_status',
    ];

    protected $casts = [
        'synced' => 'boolean',
        'sale_ids' => 'array',
        'allocations' => 'array',
    ];

    public function getAttachmentUrlAttribute(): ?string
    {
        if (!$this->attachment_path) {
            return null;
        }

        return url('storage/app/public/' . ltrim($this->attachment_path, '/'));
    }

    public function getCreatedAtAttribute($value)
    {
        if (!$value) {
            return $value;
        }
        $created = $value instanceof Carbon ? $value : Carbon::parse($value);
        $updated = $this->attributes['updated_at'] ?? null;
        if ($created->format('H:i:s') === '00:00:00' && $updated) {
            $updatedAt = $updated instanceof Carbon ? $updated : Carbon::parse($updated);
            return $created->setTimeFrom($updatedAt);
        }
        return $created;
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
}
