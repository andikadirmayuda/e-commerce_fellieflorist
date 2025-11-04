<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'customer_name',
        'customer_phone',
        'pickup_date',
        'notes',
    ];

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
}
