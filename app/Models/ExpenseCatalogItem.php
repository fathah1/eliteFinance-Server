<?php

namespace App\Models;

use App\Models\Concerns\HasDelStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseCatalogItem extends Model
{
    use HasFactory, HasDelStatus;

    protected $fillable = [
        'user_id',
        'business_id',
        'name',
        'rate',
        'del_status',
    ];
}
