<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionRefund extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'refund_date' => 'datetime',
            'total_refund_amount' => 'decimal:2',
            'restock_products' => 'boolean',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
