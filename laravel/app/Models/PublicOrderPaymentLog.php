<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicOrderPaymentLog extends Model
{
    protected $fillable = [
        'public_order_id',
        'old_status',
        'new_status',
        'changed_by',
        'source',
        'note',
    ];
}
