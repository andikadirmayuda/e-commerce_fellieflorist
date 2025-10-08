<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashFlowCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    public function cashFlows()
    {
        return $this->hasMany(CashFlow::class, 'category_id');
    }
}
