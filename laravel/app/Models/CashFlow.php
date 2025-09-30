<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashFlow extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'type',
        'amount',
        'description',
        'payment_method',
        'transaction_date',
    ];

    public function category()
    {
        return $this->belongsTo(CashFlowCategory::class, 'category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
