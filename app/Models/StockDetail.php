<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockDetail extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'qty_in' => 'decimal:2',
            'qty_out' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function header(): BelongsTo
    {
        return $this->belongsTo(StockHeader::class, 'stock_header_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
