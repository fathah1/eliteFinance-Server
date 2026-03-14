<?php

namespace App\Models;

use App\Models\Concerns\HasDelStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseItem extends Model
{
    use HasFactory, HasDelStatus;

    protected $fillable = [
        'expense_id', 'expense_catalog_item_id', 'item_id', 'name', 'qty', 'price', 'line_total', 'del_status',
    ];
}
