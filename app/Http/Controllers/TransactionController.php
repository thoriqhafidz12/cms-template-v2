<?php

namespace App\Http\Controllers;

use App\Models\SalonSetting;
use App\Models\Transaction;
use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function __construct(
        protected CheckoutService $checkoutService
    ) {}

    public function index(Request $request): View
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $status = $request->get('status');
        $search = $request->get('search');

        $query = Transaction::with(['customer', 'creator', 'payments.paymentMethod', 'details.employee', 'refunds'])
            ->whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate);

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        $summaryPaid = (clone $query)->where('status', 'paid')->sum('grand_total');
        $summaryRefunded = (clone $query)->where('status', 'refunded')->sum('grand_total');

        $transactions = $query->orderByDesc('id')->paginate(12)->withQueryString();

        return view('pos.transactions', compact(
            'transactions',
            'startDate',
            'endDate',
            'status',
            'search',
            'summaryPaid',
            'summaryRefunded'
        ));
    }

    public function receipt(Request $request, Transaction $transaction): View
    {
        $transaction->load([
            'customer',
            'creator',
            'cashShift',
            'details.employee',
            'payments.paymentMethod',
            'refunds.creator',
        ]);

        $setting = SalonSetting::current();
        $paperWidth = $request->get('width', $setting->receipt_width ?: '80mm');

        return view('pos.receipt', compact('transaction', 'setting', 'paperWidth'));
    }

    public function refund(Request $request, Transaction $transaction): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
            'restock_products' => ['nullable', 'boolean'],
        ]);

        $refund = $this->checkoutService->refundTransaction(
            $transaction,
            $validated['reason'],
            $request->boolean('restock_products', true)
        );

        return redirect()
            ->route('transactions.index')
            ->with('success', "Transaksi {$transaction->transaction_number} berhasil di-refund ({$refund->refund_number}).");
    }
}
