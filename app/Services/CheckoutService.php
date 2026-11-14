<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CashShift;
use App\Models\Employee;
use App\Models\EmployeeCommission;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\SalonSetting;
use App\Models\Service;
use App\Models\ServiceOrder;
use App\Models\Transaction;
use App\Models\TransactionRefund;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        protected StockService $stockService
    ) {}

    /**
     * Proses checkout POS secara atomik dengan DB transaction, row lock, dan idempotency.
     */
    public function checkout(array $payload): Transaction
    {
        return DB::transaction(function () use ($payload) {
            // 1. Cek Idempotency Key agar klik ganda tidak membuat transaksi ganda
            $idempotencyKey = $payload['idempotency_key'] ?? null;
            if (!empty($idempotencyKey)) {
                $existing = Transaction::where('idempotency_key', $idempotencyKey)->first();
                if ($existing) {
                    return $existing->load(['details.employee', 'payments.paymentMethod', 'customer']);
                }
            }

            // 2. Kunci Cash Shift aktif
            /** @var CashShift|null $shift */
            $shift = CashShift::where('id', $payload['cash_shift_id'])
                ->lockForUpdate()
                ->first();

            if (!$shift || $shift->status !== 'open') {
                throw ValidationException::withMessages([
                    'cash_shift_id' => 'Shift kasir belum dibuka atau sudah ditutup. Silakan buka shift kasir terlebih dahulu.',
                ]);
            }

            $setting = SalonSetting::current();
            $itemsInput = $payload['items'] ?? [];
            if (empty($itemsInput)) {
                throw ValidationException::withMessages([
                    'items' => 'Keranjang transaksi tidak boleh kosong.',
                ]);
            }

            $subtotal = 0.0;
            $detailSnapshots = [];
            $stockOutItems = [];

            // 3. Ambil ulang harga & status dari database (jangan percaya angka dari browser)
            foreach ($itemsInput as $idx => $itemRow) {
                $type = $itemRow['item_type'] ?? 'service';
                $itemId = (int) ($itemRow['item_id'] ?? 0);
                $qty = round(max(1, (float) ($itemRow['quantity'] ?? 1)), 2);
                $itemDiscount = round(max(0, (float) ($itemRow['discount_amount'] ?? 0)), 2);
                $employeeId = !empty($itemRow['employee_id']) ? (int) $itemRow['employee_id'] : null;

                if ($type === 'service') {
                    $service = Service::where('id', $itemId)->where('is_active', 1)->first();
                    if (!$service) {
                        throw ValidationException::withMessages([
                            "items.{$idx}" => 'Layanan tidak ditemukan atau sedang nonaktif.',
                        ]);
                    }

                    $unitPrice = round((float) $service->price, 2);
                    $lineGross = round($unitPrice * $qty, 2);
                    $itemDiscount = min($itemDiscount, $lineGross);
                    $lineSubtotal = round($lineGross - $itemDiscount, 2);

                    // Hitung komisi stylist bila stylist dipilih pada layanan
                    $commType = $service->commission_type ?: 'percentage';
                    $commVal = (float) $service->commission_value;
                    $commAmount = 0.0;

                    if ($employeeId) {
                        $employee = Employee::find($employeeId);
                        if (!$employee) {
                            $employeeId = null;
                        } else {
                            if ($commType === 'percentage') {
                                $commAmount = round($lineSubtotal * ($commVal / 100), 2);
                            } else {
                                $commAmount = round($commVal * $qty, 2);
                            }
                        }
                    }

                    $subtotal = round($subtotal + $lineSubtotal, 2);

                    $detailSnapshots[] = [
                        'item_type' => 'service',
                        'item_id' => $service->id,
                        'item_name_snapshot' => $service->name,
                        'employee_id' => $employeeId,
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                        'discount_amount' => $itemDiscount,
                        'subtotal' => $lineSubtotal,
                        'commission_type' => $commType,
                        'commission_value' => $commVal,
                        'commission_amount' => $commAmount,
                    ];
                } elseif ($type === 'product') {
                    $product = Product::where('id', $itemId)
                        ->where('is_active', 1)
                        ->lockForUpdate()
                        ->first();

                    if (!$product) {
                        throw ValidationException::withMessages([
                            "items.{$idx}" => 'Produk tidak ditemukan atau sedang nonaktif.',
                        ]);
                    }

                    if ((float) $product->current_stock < $qty) {
                        throw ValidationException::withMessages([
                            "items.{$idx}" => "Stok produk \"{$product->name}\" tidak mencukupi (tersedia: {$product->current_stock}).",
                        ]);
                    }

                    $unitPrice = round((float) $product->sell_price, 2);
                    $lineGross = round($unitPrice * $qty, 2);
                    $itemDiscount = min($itemDiscount, $lineGross);
                    $lineSubtotal = round($lineGross - $itemDiscount, 2);

                    $subtotal = round($subtotal + $lineSubtotal, 2);

                    $detailSnapshots[] = [
                        'item_type' => 'product',
                        'item_id' => $product->id,
                        'item_name_snapshot' => $product->name,
                        'employee_id' => $employeeId,
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                        'discount_amount' => $itemDiscount,
                        'subtotal' => $lineSubtotal,
                        'commission_type' => null,
                        'commission_value' => 0,
                        'commission_amount' => 0,
                    ];

                    $stockOutItems[] = [
                        'product_id' => $product->id,
                        'qty_in' => 0,
                        'qty_out' => $qty,
                        'unit_cost' => (float) $product->purchase_price,
                        'notes' => 'Penjualan POS',
                    ];
                }
            }

            // 4. Hitung Diskon Transaksi, Pajak, dan Grand Total di Backend
            $headerDiscount = round(max(0, (float) ($payload['discount_amount'] ?? 0)), 2);
            $headerDiscount = min($headerDiscount, $subtotal);
            $afterDiscount = round($subtotal - $headerDiscount, 2);

            $taxPercent = isset($payload['use_tax']) && !$payload['use_tax']
                ? 0.0
                : (float) ($setting->tax_percentage ?? 0);
            $taxAmount = round($afterDiscount * ($taxPercent / 100), 2);
            $grandTotal = round($afterDiscount + $taxAmount, 2);

            // 5. Validasi Pembayaran (mendukung single & multi-payment)
            $paymentsInput = $payload['payments'] ?? [];
            if (empty($paymentsInput)) {
                throw ValidationException::withMessages([
                    'payments' => 'Metode pembayaran wajib diisi.',
                ]);
            }

            $paidAmount = 0.0;
            $hasCashPayment = false;
            $cashPaidTotal = 0.0;
            $paymentRows = [];

            foreach ($paymentsInput as $pRow) {
                $amount = round((float) ($pRow['amount'] ?? 0), 2);
                if ($amount <= 0) {
                    continue;
                }
                $pm = PaymentMethod::where('id', $pRow['payment_method_id'] ?? 0)
                    ->where('is_active', 1)
                    ->first();

                if (!$pm) {
                    throw ValidationException::withMessages([
                        'payments' => 'Metode pembayaran tidak valid atau nonaktif.',
                    ]);
                }

                if ($pm->type === 'cash') {
                    $hasCashPayment = true;
                    $cashPaidTotal = round($cashPaidTotal + $amount, 2);
                }

                $paidAmount = round($paidAmount + $amount, 2);
                $paymentRows[] = [
                    'payment_method_id' => $pm->id,
                    'amount' => $amount,
                    'reference_number' => $pRow['reference_number'] ?? null,
                    'status' => 'paid',
                    'paid_at' => now(),
                    'created_by' => auth()->id(),
                ];
            }

            if ($paidAmount < $grandTotal) {
                throw ValidationException::withMessages([
                    'payments' => 'Total pembayaran (Rp ' . number_format($paidAmount, 0, ',', '.') . ') kurang dari total tagihan (Rp ' . number_format($grandTotal, 0, ',', '.') . ').',
                ]);
            }

            $changeAmount = round($paidAmount - $grandTotal, 2);
            if ($changeAmount > 0 && !$hasCashPayment) {
                throw ValidationException::withMessages([
                    'payments' => 'Pembayaran non-tunai tidak boleh melebihi total tagihan.',
                ]);
            }

            // 6. Buat Header Transaksi
            $trxNumber = DocumentNumberService::generate($setting->transaction_prefix ?: 'SAL');

            $serviceOrderId = !empty($payload['service_order_id']) ? (int) $payload['service_order_id'] : null;
            $appointmentId = !empty($payload['appointment_id']) ? (int) $payload['appointment_id'] : null;
            $customerId = !empty($payload['customer_id']) ? (int) $payload['customer_id'] : null;

            if ($serviceOrderId && !$appointmentId) {
                $so = ServiceOrder::find($serviceOrderId);
                if ($so) {
                    $appointmentId = $so->appointment_id;
                    $customerId = $customerId ?: $so->customer_id;
                }
            }

            $transaction = Transaction::create([
                'transaction_number' => $trxNumber,
                'customer_id' => $customerId,
                'appointment_id' => $appointmentId,
                'service_order_id' => $serviceOrderId,
                'cash_shift_id' => $shift->id,
                'transaction_date' => now(),
                'subtotal' => $subtotal,
                'discount_amount' => $headerDiscount,
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'status' => 'paid',
                'idempotency_key' => $idempotencyKey,
                'notes' => $payload['notes'] ?? null,
                'created_by' => auth()->id() ?: $shift->user_id,
            ]);

            // 7. Buat Detail Snapshot & Komisi Stylist
            foreach ($detailSnapshots as $dRow) {
                $detail = $transaction->details()->create($dRow);

                if ($detail->item_type === 'service' && $detail->employee_id && $detail->commission_amount > 0) {
                    EmployeeCommission::create([
                        'employee_id' => $detail->employee_id,
                        'transaction_id' => $transaction->id,
                        'transaction_detail_id' => $detail->id,
                        'commission_date' => $transaction->transaction_date,
                        'service_name_snapshot' => $detail->item_name_snapshot,
                        'base_amount' => $detail->subtotal,
                        'commission_type' => $detail->commission_type,
                        'commission_value' => $detail->commission_value,
                        'commission_amount' => $detail->commission_amount,
                        'status' => 'earned',
                    ]);
                }
            }

            // 8. Buat Payment Records
            $transaction->payments()->createMany($paymentRows);

            // 9. Update Expected Cash pada Cash Shift untuk penerimaan tunai bersih
            if ($hasCashPayment) {
                $netCashReceived = max(0, round($cashPaidTotal - $changeAmount, 2));
                $shift->update([
                    'expected_cash' => round((float) $shift->expected_cash + $netCashReceived, 2),
                ]);
            }

            // 10. Posting Mutasi Stok Keluar untuk produk yang terjual
            if (!empty($stockOutItems)) {
                $this->stockService->recordMovement(
                    'sale',
                    $stockOutItems,
                    "Penjualan POS {$trxNumber}",
                    null,
                    'transaction',
                    $transaction->id
                );
            }

            // 11. Tandai ServiceOrder & Appointment selesai
            if ($serviceOrderId) {
                $so = ServiceOrder::find($serviceOrderId);
                if ($so) {
                    $so->update([
                        'status' => 'completed',
                        'completed_at' => $so->completed_at ?: now(),
                    ]);
                    $so->details()->update(['status' => 'completed']);
                    if ($so->appointment) {
                        $so->appointment->update(['status' => 'completed']);
                    }
                }
            }

            // 12. Simpan Audit Log
            AuditLog::record(
                'pos.checkout',
                'pos',
                "Checkout transaksi {$trxNumber} senilai Rp " . number_format($grandTotal, 0, ',', '.'),
                $transaction->id,
                $trxNumber
            );

            return $transaction->load(['details.employee', 'payments.paymentMethod', 'customer']);
        });
    }

    /**
     * Proses Refund / Void transaksi final dengan alasan, pembalik stok, pembalik komisi, dan audit log.
     */
    public function refundTransaction(Transaction $transaction, string $reason, bool $restockProducts = true): TransactionRefund
    {
        return DB::transaction(function () use ($transaction, $reason, $restockProducts) {
            $trx = Transaction::where('id', $transaction->id)
                ->with(['details', 'payments.paymentMethod', 'cashShift'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($trx->status === 'refunded' || $trx->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'transaction' => 'Transaksi ini sudah pernah di-refund atau dibatalkan sebelumnya.',
                ]);
            }

            $refundNumber = DocumentNumberService::generate('RFD');

            $refund = TransactionRefund::create([
                'refund_number' => $refundNumber,
                'transaction_id' => $trx->id,
                'refund_date' => now(),
                'total_refund_amount' => $trx->grand_total,
                'reason' => $reason,
                'restock_products' => $restockProducts,
                'created_by' => auth()->id() ?: $trx->created_by,
            ]);

            // Kembalikan stok produk jika diaktifkan
            if ($restockProducts) {
                $restockItems = [];
                foreach ($trx->details as $detail) {
                    if ($detail->item_type === 'product' && $detail->item_id) {
                        $restockItems[] = [
                            'product_id' => $detail->item_id,
                            'qty_in' => (float) $detail->quantity,
                            'qty_out' => 0,
                            'notes' => "Refund transaksi {$trx->transaction_number}",
                        ];
                    }
                }

                if (!empty($restockItems)) {
                    $this->stockService->recordMovement(
                        'refund',
                        $restockItems,
                        "Refund POS {$trx->transaction_number} ({$refundNumber}): {$reason}",
                        null,
                        'transaction_refund',
                        $refund->id
                    );
                }
            }

            // Reversal komisi stylist
            EmployeeCommission::where('transaction_id', $trx->id)
                ->update(['status' => 'reversed']);

            // Tandai payment refunded
            $trx->payments()->update(['status' => 'refunded']);

            // Kurangi expected_cash pada shift jika masih terbuka dan ada pembayaran tunai
            if ($trx->cashShift && $trx->cashShift->status === 'open') {
                $cashPaid = $trx->payments
                    ->filter(fn ($p) => $p->paymentMethod?->type === 'cash')
                    ->sum('amount');
                $netCash = max(0, (float) $cashPaid - (float) $trx->change_amount);
                if ($netCash > 0) {
                    $trx->cashShift->update([
                        'expected_cash' => max(0, round((float) $trx->cashShift->expected_cash - $netCash, 2)),
                    ]);
                }
            }

            $trx->update(['status' => 'refunded']);

            AuditLog::record(
                'pos.refund',
                'pos',
                "Refund transaksi {$trx->transaction_number} ({$refundNumber}) alasan: {$reason}",
                $trx->id,
                $trx->transaction_number
            );

            return $refund;
        });
    }
}
