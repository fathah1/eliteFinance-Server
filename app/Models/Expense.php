<?php

namespace App\Models;

use App\Models\Concerns\HasDelStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory, HasDelStatus;

    protected $fillable = [
        'user_id', 'business_id', 'expense_category_id', 'expense_number', 'date', 'category_name', 'manual_amount', 'amount', 'vat_amount', 'del_status',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function items()
    {
        return $this->hasMany(ExpenseItem::class);
    }
}
