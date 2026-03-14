<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFeaturePermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_owner_id',
        'user_id',
        'feature',
        'can_view',
        'can_add',
        'can_edit',
    ];

    protected $casts = [
        'can_view' => 'boolean',
        'can_add' => 'boolean',
        'can_edit' => 'boolean',
    ];
}
