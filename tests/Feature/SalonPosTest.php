<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\CashShift;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeeCommission;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalonPosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_dashboard_and_master_pages_accessible_by_owner(): void
    {
        $this->get(route('login'))
            ->assertStatus(200)
            ->assertSee('E-SALON')
            ->assertSee('Masuk ke Panel Kerja')
            ->assertSee('Alamat Email')
            ->assertSee('Kata Sandi');

        $owner = User::where('email', 'admin@cms.test')->firstOrFail();

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertSee('E-SALON')
            ->assertSee('mobile-bottom-nav')
            ->assertSee('mobile-segmented-tabs')
            ->assertSee('mobileMenuHub');

        $this->actingAs($owner)
            ->get(route('pos.index'))
            ->assertStatus(200)
            ->assertSee('posFloatingBar')
            ->assertSee('1. Katalog')
            ->assertSee('2. Keranjang')
            ->assertSee('3. Bayar');

        $this->actingAs($owner)->get(route('customers.index'))->assertStatus(200)->assertSee('masterCrudTabGroup');
        $this->actingAs($owner)->get(route('services.index'))->assertStatus(200);
        $this->actingAs($owner)->get(route('stylists.index'))->assertStatus(200);
        $this->actingAs($owner)->get(route('products.index'))->assertStatus(200);
        $this->actingAs($owner)->get(route('bookings.index'))->assertStatus(200)->assertSee('bookingTabGroup');
        $this->actingAs($owner)->get(route('stocks.index'))->assertStatus(200)->assertSee('stocksTabGroup');
        $this->actingAs($owner)->get(route('reports.index'))->assertStatus(200)->assertSee('reportsTabGroup');
        $this->actingAs($owner)->get(route('salon-settings.index'))->assertStatus(200)->assertSee('salonSettingTabGroup');
        $this->actingAs($owner)->get(route('audit-logs.index'))->assertStatus(200);
        $this->actingAs($owner)->get(route('cash-shifts.index'))->assertStatus(200)->assertSee('shiftsTabGroup');
        $this->actingAs($owner)->get(route('transactions.index'))->assertStatus(200)->assertSee('transactionsTabGroup');
        $this->actingAs($owner)->get(route('stylist-schedules.index'))->assertStatus(200)->assertSee('stylistScheduleTabGroup');
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::where('email', 'kasir@salon.test')->firstOrFail();
        $user->update(['is_active' => 0]);

        $response = $this->post('/login', [
            'email' => 'kasir@salon.test',
            'password' => '1234',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_role_menu_authorization_blocks_unauthorized_routes(): void
    {
        $stylistUser = User::where('email', 'stylist@salon.test')->firstOrFail();

        // Stylist boleh akses bookings & stylists
        $this->actingAs($stylistUser)->get(route('bookings.index'))->assertStatus(200);
        $this->actingAs($stylistUser)->get(route('stylists.index'))->assertStatus(200);

        // Stylist tidak boleh akses manajemen user atau POS kasir
        $this->actingAs($stylistUser)->get(route('users.index'))->assertStatus(403);
        $this->actingAs($stylistUser)->get(route('pos.index'))->assertStatus(403);
    }

    public function test_booking_conflict_prevention_and_checkin_to_queue(): void
    {
        $owner = User::where('email', 'admin@cms.test')->firstOrFail();
        $customer = Customer::firstOrFail();
        $service = Service::firstOrFail();
        $stylist = Employee::where('employee_code', 'STY-002')->firstOrFail();
        $tomorrow = now()->addDay()->format('Y-m-d');

        // Booking pertama pukul 13:00 berhasil
        $this->actingAs($owner)->post(route('bookings.store'), [
            'customer_id' => $customer->id,
            'appointment_date' => $tomorrow,
            'start_time' => '13:00',
            'service_ids' => [$service->id],
            'employee_id' => $stylist->id,
            'notes' => 'Booking pertama',
        ])->assertRedirect();

        $apt = Appointment::whereDate('appointment_date', $tomorrow)->firstOrFail();
        $this->assertEquals('confirmed', $apt->status);

        // Booking kedua di jam yang sama (13:15) dengan stylist yang sama harus ditolak karena bentrok!
        $this->actingAs($owner)->post(route('bookings.store'), [
            'customer_id' => $customer->id,
            'appointment_date' => $tomorrow,
            'start_time' => '13:15',
            'service_ids' => [$service->id],
            'employee_id' => $stylist->id,
        ])->assertSessionHasErrors('employee_id');

        // Check-in booking pertama menghasilkan ServiceOrder
        $this->actingAs($owner)->post(route('bookings.checkin', $apt->id))->assertRedirect();
        $apt->refresh();
        $this->assertEquals('checked_in', $apt->status);
        $this->assertNotNull($apt->serviceOrder);
    }

    public function test_pos_checkout_idempotency_stock_ledger_commission_and_refund(): void
    {
        $owner = User::where('email', 'admin@cms.test')->firstOrFail();
        $shift = CashShift::where('status', 'open')->firstOrFail();
        $customer = Customer::firstOrFail();
        $service = Service::where('service_code', 'SRV-001')->firstOrFail(); // 150.000, komisi 15%
        $product = Product::where('sku', 'PRD-001')->firstOrFail(); // 145.000
        $stylist = Employee::where('employee_code', 'STY-001')->firstOrFail();
        $pmCash = PaymentMethod::where('code', 'CASH')->firstOrFail();
        $pmQris = PaymentMethod::where('code', 'QRIS')->firstOrFail();

        $stockBefore = (float) $product->current_stock;
        $idempotencyKey = 'test-idem-key-999';

        $payload = [
            'cash_shift_id' => $shift->id,
            'customer_id' => $customer->id,
            'idempotency_key' => $idempotencyKey,
            'discount_amount' => 5000,
            'items' => [
                [
                    'item_type' => 'service',
                    'item_id' => $service->id,
                    'quantity' => 1,
                    'discount_amount' => 0,
                    'employee_id' => $stylist->id,
                ],
                [
                    'item_type' => 'product',
                    'item_id' => $product->id,
                    'quantity' => 2,
                    'discount_amount' => 0,
                    'employee_id' => $stylist->id,
                ],
            ],
            'payments' => [
                ['payment_method_id' => $pmCash->id, 'amount' => 200000, 'reference_number' => null],
                ['payment_method_id' => $pmQris->id, 'amount' => 240000, 'reference_number' => 'QR-999'],
            ],
        ];

        // 1. Checkout pertama
        $this->actingAs($owner)
            ->post(route('pos.checkout'), $payload)
            ->assertRedirect();

        $trx = Transaction::where('idempotency_key', $idempotencyKey)->firstOrFail();
        // Subtotal = 150.000 + (2 * 145.000) = 440.000; Diskon = 5.000 -> Grand Total = 435.000
        // Dibayar = 440.000 -> Kembalian tunai = 5.000
        $this->assertEquals(440000, (float) $trx->subtotal);
        $this->assertEquals(435000, (float) $trx->grand_total);
        $this->assertEquals(5000, (float) $trx->change_amount);

        // Stok berkurang 2 melalui ledger
        $product->refresh();
        $this->assertEquals($stockBefore - 2, (float) $product->current_stock);

        // Komisi stylist tercatat (15% x 150.000 = 22.500)
        $comm = EmployeeCommission::where('transaction_id', $trx->id)->firstOrFail();
        $this->assertEquals(22500, (float) $comm->commission_amount);
        $this->assertEquals('earned', $comm->status);

        // 2. Klik ganda dengan idempotency_key yang sama tidak membuat transaksi baru
        $countBefore = Transaction::count();
        $this->actingAs($owner)->post(route('pos.checkout'), $payload)->assertRedirect();
        $this->assertEquals($countBefore, Transaction::count());

        // 3. Halaman Struk Thermal 80mm & 58mm
        $this->actingAs($owner)
            ->get(route('pos.receipt', ['transaction' => $trx->id, 'width' => '58mm']))
            ->assertStatus(200)
            ->assertSee($trx->transaction_number);

        // 4. Refund Transaksi mengembalikan stok & membalik komisi
        $this->actingAs($owner)
            ->post(route('transactions.refund', $trx->id), [
                'reason' => 'Pelanggan batal beli produk dan layanan dikoreksi',
                'restock_products' => 1,
            ])
            ->assertRedirect();

        $trx->refresh();
        $product->refresh();
        $comm->refresh();

        $this->assertEquals('refunded', $trx->status);
        $this->assertEquals($stockBefore, (float) $product->current_stock);
        $this->assertEquals('reversed', $comm->status);
    }

    public function test_master_crud_stock_movement_shift_and_csv_export(): void
    {
        $owner = User::where('email', 'admin@cms.test')->firstOrFail();

        // 1. Tambah pelanggan baru via BaseController
        $this->actingAs($owner)->post(route('customers.store'), [
            'name' => 'Raisa Adriana',
            'phone' => '081911223344',
            'gender' => 'P',
            'notes' => 'VIP Customer',
            'is_active' => 1,
        ])->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', ['name' => 'Raisa Adriana', 'phone' => '081911223344']);

        // 2. Posting mutasi penerimaan stok
        $product = Product::firstOrFail();
        $beforeStock = (float) $product->current_stock;

        $this->actingAs($owner)->post(route('stocks.store'), [
            'movement_action' => 'receive',
            'product_id' => $product->id,
            'quantity' => 5,
            'unit_cost' => 92000,
            'reason' => 'Restock mingguan',
        ])->assertRedirect();

        $product->refresh();
        $this->assertEquals($beforeStock + 5, (float) $product->current_stock);

        // 3. Export CSV Laporan
        $this->actingAs($owner)
            ->get(route('reports.export-csv'))
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        // 4. Tutup dan buka kembali shift kasir
        $activeShift = CashShift::where('status', 'open')->firstOrFail();
        $this->actingAs($owner)->post(route('cash-shifts.close', $activeShift->id), [
            'actual_cash' => (float) $activeShift->expected_cash,
            'notes' => 'Shift selesai tepat jumlah',
        ])->assertRedirect(route('cash-shifts.index'));

        $activeShift->refresh();
        $this->assertEquals('closed', $activeShift->status);

        $this->actingAs($owner)->post(route('cash-shifts.open'), [
            'opening_cash' => 250000,
            'notes' => 'Shift Sore',
        ])->assertRedirect(route('cash-shifts.index'));

        $this->assertDatabaseHas('cash_shifts', ['status' => 'open', 'opening_cash' => 250000]);

        // 5. Ubah Nama Salon di Profil & pastikan branding seluruh halaman otomatis mengikuti
        $this->actingAs($owner)->put(route('salon-settings.update'), [
            'salon_name' => 'CANTIKA GLAM SALON',
            'tagline' => 'Luxury Hair & Spa',
            'address' => 'Jl. Sudirman No. 10',
            'phone' => '021-5556677',
            'email' => 'info@cantikaglam.id',
            'open_time' => '09:00',
            'close_time' => '21:00',
            'tax_percentage' => 11,
            'default_discount_percentage' => 0,
            'booking_prefix' => 'BKG',
            'service_order_prefix' => 'SO',
            'transaction_prefix' => 'SAL',
            'receipt_width' => '80mm',
            'receipt_footer' => 'Terima kasih dari Cantika Glam!',
            'low_stock_threshold_default' => 5,
        ])->assertRedirect(route('salon-settings.index'));

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertSee('CANTIKA GLAM SALON')
            ->assertSee('Luxury Hair & Spa');
    }
}
