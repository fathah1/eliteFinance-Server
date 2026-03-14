<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserBusinessAccess extends Model
{
    use HasFactory;

    protected $table = 'user_business_accesses';

    protected $fillable = [
        'account_owner_id',
        'user_id',
        'business_id',
    ];
}
