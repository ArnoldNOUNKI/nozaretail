<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = ['order_id', 'gateway', 'reference', 'external_reference', 'operator', 'phone', 'amount', 'currency', 'status', 'failure_reason', 'payload', 'completed_at'];

    protected $casts = ['payload' => 'array', 'completed_at' => 'datetime'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
