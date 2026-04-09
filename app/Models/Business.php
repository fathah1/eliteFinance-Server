<?php

namespace App\Models;

use App\Models\Concerns\HasDelStatus;
use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    use HasDelStatus;

    protected $fillable = [
        'user_id',
        'name',
        'sales_tax_enabled',
        'purchase_tax_enabled',
        'address_note',
        'trn_no',
        'del_status',
    ];

    protected $casts = [
        'sales_tax_enabled' => 'boolean',
        'purchase_tax_enabled' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
