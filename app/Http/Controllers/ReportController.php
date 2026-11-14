<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\EmployeeCommission;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\TransactionRefund;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        // Semua transaksi pada rentang tanggal (paid + refunded)
        $allTransactions = Transaction::whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate)
            ->whereIn('status', ['paid', 'refunded'])
            ->get();

        $paidTransactions = $allTransactions->where('status', 'paid');

        // Definisi KPI sesuai PRD 5.7
        $grossSales = (float) $allTransactions->sum(fn ($t) => (float) $t->subtotal + (float) $t->discount_amount);
        $totalDiscount = (float) $allTransactions->sum('discount_amount');
        $totalRefund = (float) TransactionRefund::whereDate('refund_date', '>=', $startDate)
            ->whereDate('refund_date', '<=', $endDate)
            ->sum('total_refund_amount');
        $netSales = (float) $paidTransactions->sum('grand_total');
        $paidCount = $paidTransactions->count();
        $avgTransaction = $paidCount > 0 ? round($netSales / $paidCount, 2) : 0;

        // Pembayaran per metode
        $paymentsByMethod = Payment::select('payment_method_id', DB::raw('SUM(amount) as total_amount'), DB::raw('COUNT(*) as trx_count'))
            ->with('paymentMethod')
            ->where('status', 'paid')
            ->whereHas('transaction', function ($q) use ($startDate, $endDate) {
                $q->whereDate('transaction_date', '>=', $startDate)
                    ->whereDate('transaction_date', '<=', $endDate)
                    ->where('status', 'paid');
            })
            ->groupBy('payment_method_id')
            ->get();

        // Top Layanan & Produk Terlaris
        $topItems = TransactionDetail::select(
            'item_type',
            'item_name_snapshot',
            DB::raw('SUM(quantity) as total_qty'),
            DB::raw('SUM(subtotal) as total_revenue')
        )
            ->whereHas('transaction', function ($q) use ($startDate, $endDate) {
                $q->whereDate('transaction_date', '>=', $startDate)
                    ->whereDate('transaction_date', '<=', $endDate)
                    ->where('status', 'paid');
            })
            ->groupBy('item_type', 'item_name_snapshot')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        // Performa & Komisi Stylist
        $stylistCommissions = EmployeeCommission::select(
            'employee_id',
            DB::raw('COUNT(*) as total_services'),
            DB::raw('SUM(base_amount) as total_omzet'),
            DB::raw('SUM(commission_amount) as total_commission')
        )
            ->with('employee')
            ->whereDate('commission_date', '>=', $startDate)
            ->whereDate('commission_date', '<=', $endDate)
            ->where('status', 'earned')
            ->groupBy('employee_id')
            ->orderByDesc('total_commission')
            ->get();

        $totalCommissionEarned = (float) $stylistCommissions->sum('total_commission');

        // Statistik Booking
        $bookings = Appointment::whereDate('appointment_date', '>=', $startDate)
            ->whereDate('appointment_date', '<=', $endDate)
            ->get();
        $bookingSummary = [
            'total' => $bookings->count(),
            'completed' => $bookings->where('status', 'completed')->count(),
            'no_show' => $bookings->where('status', 'no_show')->count(),
            'cancelled' => $bookings->where('status', 'cancelled')->count(),
        ];

        // Produk stok rendah
        $lowStockProducts = Product::with(['category', 'unit'])
            ->where('is_active', 1)
            ->whereColumn('current_stock', '<=', 'min_stock')
            ->orderBy('current_stock')
            ->get();

        return view('reports.index', compact(
            'startDate',
            'endDate',
            'grossSales',
            'totalDiscount',
            'totalRefund',
            'netSales',
            'paidCount',
            'avgTransaction',
            'totalCommissionEarned',
            'paymentsByMethod',
            'topItems',
            'stylistCommissions',
            'bookingSummary',
            'lowStockProducts'
        ));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $transactions = Transaction::with(['customer', 'creator', 'payments.paymentMethod'])
            ->whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate)
            ->orderBy('transaction_date')
            ->get();

        $filename = "laporan-penjualan-salon-{$startDate}-sd-{$endDate}.csv";

        return response()->streamDownload(function () use ($transactions) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'No Transaksi',
                'Tanggal',
                'Pelanggan',
                'Kasir',
                'Subtotal',
                'Diskon',
                'Pajak',
                'Grand Total',
                'Status',
                'Metode Pembayaran',
            ]);

            foreach ($transactions as $trx) {
                $methods = $trx->payments->map(fn ($p) => $p->paymentMethod?->name)->filter()->implode(' + ');
                fputcsv($out, [
                    $trx->transaction_number,
                    $trx->transaction_date?->format('Y-m-d H:i:s'),
                    $trx->customer?->name ?? 'Umum',
                    $trx->creator?->name ?? '-',
                    $trx->subtotal,
                    $trx->discount_amount,
                    $trx->tax_amount,
                    $trx->grand_total,
                    $trx->status,
                    $methods,
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
