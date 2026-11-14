<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeCommission extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'commission_date' => 'datetime',
            'base_amount' => 'decimal:2',
            'commission_value' => 'decimal:2',
            'commission_amount' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function transactionDetail(): BelongsTo
    {
        return $this->belongsTo(TransactionDetail::class);
    }
}
