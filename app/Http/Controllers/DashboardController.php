<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\EmployeeCommission;
use App\Models\Product;
use App\Models\ServiceOrder;
use App\Models\Transaction;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = now()->format('Y-m-d');

        $bookingsToday = Appointment::whereDate('appointment_date', $today)->count();
        $activeQueuesCount = ServiceOrder::whereDate('queue_date', $today)
            ->whereIn('status', ['waiting', 'called', 'in_service'])
            ->count();

        $todayPaidTransactions = Transaction::whereDate('transaction_date', $today)
            ->where('status', 'paid');

        $transactionsTodayCount = (clone $todayPaidTransactions)->count();
        $netSalesToday = (float) (clone $todayPaidTransactions)->sum('grand_total');

        $lowStockCount = Product::where('is_active', 1)
            ->whereColumn('current_stock', '<=', 'min_stock')
            ->count();

        $totalCustomers = Customer::where('is_active', 1)->count();

        $activeQueues = ServiceOrder::with(['customer', 'details.employee'])
            ->whereDate('queue_date', $today)
            ->whereIn('status', ['waiting', 'called', 'in_service'])
            ->orderBy('queue_number')
            ->limit(8)
            ->get();

        $recentTransactions = Transaction::with(['customer', 'payments.paymentMethod'])
            ->orderByDesc('id')
            ->limit(6)
            ->get();

        $topStylistsThisMonth = EmployeeCommission::selectRaw('employee_id, COUNT(*) as service_count, SUM(commission_amount) as total_commission')
            ->with('employee')
            ->whereMonth('commission_date', now()->month)
            ->whereYear('commission_date', now()->year)
            ->where('status', 'earned')
            ->groupBy('employee_id')
            ->orderByDesc('total_commission')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'bookingsToday',
            'activeQueuesCount',
            'transactionsTodayCount',
            'netSalesToday',
            'lowStockCount',
            'totalCustomers',
            'activeQueues',
            'recentTransactions',
            'topStylistsThisMonth'
        ));
    }
}
