<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CashShiftController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GantiPassController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SalonSettingController;
use App\Http\Controllers\ServiceCategoryController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StylistController;
use App\Http\Controllers\StylistScheduleController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/getSession', [LoginController::class, 'checkSession'])->name('session.check');

// Guest routes (only accessible when NOT logged in)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

// Authenticated routes (only accessible when logged in)
// checkRole: cek akses menu role user via role_menu
Route::middleware(['auth', 'checkRole'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // 1. BOOKING DAN ANTRIAN
    Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::post('bookings', [BookingController::class, 'store'])->name('bookings.store');
    Route::post('bookings/walk-in', [BookingController::class, 'storeWalkIn'])->name('bookings.walkin');
    Route::post('bookings/{appointment}/check-in', [BookingController::class, 'checkIn'])->name('bookings.checkin');
    Route::post('bookings/{appointment}/status', [BookingController::class, 'updateStatus'])->name('bookings.status');
    Route::post('bookings/queue/{serviceOrder}/status', [BookingController::class, 'updateQueueStatus'])->name('bookings.queue.status');

    // 2. KASIR / POS & SHIFT
    Route::get('pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
    Route::get('pos/transactions/{transaction}/receipt', [TransactionController::class, 'receipt'])->name('pos.receipt');

    Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::post('transactions/{transaction}/refund', [TransactionController::class, 'refund'])->name('transactions.refund');

    Route::get('cash-shifts', [CashShiftController::class, 'index'])->name('cash-shifts.index');
    Route::post('cash-shifts/open', [CashShiftController::class, 'open'])->name('cash-shifts.open');
    Route::post('cash-shifts/{cashShift}/close', [CashShiftController::class, 'close'])->name('cash-shifts.close');

    // 3. PELANGGAN
    Route::resource('customers', CustomerController::class);

    // 4. LAYANAN DAN HARGA
    Route::resource('service-categories', ServiceCategoryController::class);
    Route::resource('services', ServiceController::class);

    // 5. STYLISH / STYLIST
    Route::resource('stylists', StylistController::class);
    Route::get('stylist-schedules', [StylistScheduleController::class, 'index'])->name('stylist-schedules.index');
    Route::post('stylist-schedules', [StylistScheduleController::class, 'updateSchedule'])->name('stylist-schedules.update');
    Route::post('stylist-schedules/leaves', [StylistScheduleController::class, 'storeLeave'])->name('stylist-schedules.leaves.store');
    Route::delete('stylist-schedules/leaves/{leave}', [StylistScheduleController::class, 'destroyLeave'])->name('stylist-schedules.leaves.destroy');

    // 6. STOK PRODUK
    Route::resource('product-categories', ProductCategoryController::class);
    Route::resource('units', UnitController::class);
    Route::resource('suppliers', SupplierController::class);
    Route::resource('products', ProductController::class);
    Route::get('stocks', [StockController::class, 'index'])->name('stocks.index');
    Route::post('stocks', [StockController::class, 'store'])->name('stocks.store');

    // 7. LAPORAN
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export-csv', [ReportController::class, 'exportCsv'])->name('reports.export-csv');

    // 8. MANAJEMEN USER & ROLE MENU
    Route::get('roles/{role}/menu', [RoleController::class, 'menu'])->name('roles.menu');
    Route::put('roles/{role}/menu', [RoleController::class, 'updateMenu'])->name('roles.updateMenu');
    Route::get('ganti-password', [GantiPassController::class, 'index'])->name('ganti-password.index');
    Route::put('ganti-password', [GantiPassController::class, 'update'])->name('ganti-password.update');
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    Route::resource('roles', RoleController::class);
    Route::resource('users', UserController::class);
    Route::resource('menus', MenuController::class);

    // 9. PENGATURAN
    Route::get('salon-settings', [SalonSettingController::class, 'index'])->name('salon-settings.index');
    Route::put('salon-settings', [SalonSettingController::class, 'update'])->name('salon-settings.update');
    Route::resource('payment-methods', PaymentMethodController::class);
});

// Redirect root to login or dashboard
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});
