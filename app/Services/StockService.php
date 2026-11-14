<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\StockDetail;
use App\Models\StockHeader;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockService
{
    /**
     * Catat mutasi stok berbasis ledger dengan row lock pada produk.
     *
     * @param string $movementType receive|sale|usage|adjustment|refund
     * @param array<int, array{product_id:int, qty_in?:float, qty_out?:float, unit_cost?:float, notes?:string}> $items
     */
    public function recordMovement(
        string $movementType,
        array $items,
        ?string $reason = null,
        ?int $supplierId = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): StockHeader {
        return DB::transaction(function () use (
            $movementType,
            $items,
            $reason,
            $supplierId,
            $referenceType,
            $referenceId
        ) {
            $docNumber = DocumentNumberService::generate('STK');

            $header = StockHeader::create([
                'document_number' => $docNumber,
                'movement_date' => now(),
                'movement_type' => $movementType,
                'supplier_id' => $supplierId,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reason' => $reason,
                'status' => 'posted',
                'created_by' => auth()->id(),
            ]);

            foreach ($items as $row) {
                /** @var Product $product */
                $product = Product::where('id', $row['product_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $qtyIn = round((float) ($row['qty_in'] ?? 0), 2);
                $qtyOut = round((float) ($row['qty_out'] ?? 0), 2);
                $newBalance = round((float) $product->current_stock + $qtyIn - $qtyOut, 2);

                if ($newBalance < 0) {
                    throw ValidationException::withMessages([
                        'stock' => "Stok produk \"{$product->name}\" tidak mencukupi (tersedia: {$product->current_stock}, dibutuhkan: {$qtyOut}).",
                    ]);
                }

                $unitCost = isset($row['unit_cost']) ? (float) $row['unit_cost'] : (float) $product->purchase_price;

                StockDetail::create([
                    'stock_header_id' => $header->id,
                    'product_id' => $product->id,
                    'qty_in' => $qtyIn,
                    'qty_out' => $qtyOut,
                    'balance_after' => $newBalance,
                    'unit_cost' => $unitCost,
                    'notes' => $row['notes'] ?? null,
                ]);

                $updateProduct = ['current_stock' => $newBalance];
                if ($movementType === 'receive' && $unitCost > 0) {
                    $updateProduct['purchase_price'] = $unitCost;
                }
                $product->update($updateProduct);
            }

            AuditLog::record(
                "stock.{$movementType}",
                'inventory',
                "Mutasi stok [{$movementType}] dokumen {$docNumber}: " . ($reason ?: '-'),
                $header->id,
                $docNumber
            );

            return $header->load('details.product');
        });
    }
}
