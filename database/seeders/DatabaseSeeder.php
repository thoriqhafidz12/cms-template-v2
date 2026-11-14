<?php

namespace Database\Seeders;

use App\Models\CashShift;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Menu;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\SalonSetting;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Services\BookingService;
use App\Services\CheckoutService;
use App\Services\StockService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // ── 1. ROLES (Sesuai PRD Bab 4) ─────────────────────────────────
        $roles = [
            1 => 'Owner / Admin',
            2 => 'Admin / Resepsionis',
            3 => 'Kasir',
            4 => 'Stylist',
            5 => 'Petugas Stok',
        ];

        foreach ($roles as $rId => $rNama) {
            Role::create([
                'rId' => $rId,
                'rNama' => $rNama,
                'rCreatedBy' => 'Seeder',
                'rUpdatedBy' => 'Seeder',
                'rCreatedAt' => now(),
                'rUpdatedAt' => now(),
            ]);
        }

        // ── 2. USERS ────────────────────────────────────────────────────
        $adminUser = User::create([
            'name' => 'Owner / Admin Salon',
            'email' => 'admin@cms.test',
            'password' => Hash::make('1234'),
            'role' => 1,
            'is_active' => 1,
        ]);

        auth()->login($adminUser);

        User::create([
            'name' => 'Rina (Resepsionis)',
            'email' => 'resepsionis@salon.test',
            'password' => Hash::make('1234'),
            'role' => 2,
            'is_active' => 1,
        ]);

        $kasirUser = User::create([
            'name' => 'Nadia (Kasir)',
            'email' => 'kasir@salon.test',
            'password' => Hash::make('1234'),
            'role' => 3,
            'is_active' => 1,
        ]);

        $stylistUser = User::create([
            'name' => 'Bella (Senior Stylist)',
            'email' => 'stylist@salon.test',
            'password' => Hash::make('1234'),
            'role' => 4,
            'is_active' => 1,
        ]);

        User::create([
            'name' => 'Dimas (Petugas Stok)',
            'email' => 'stok@salon.test',
            'password' => Hash::make('1234'),
            'role' => 5,
            'is_active' => 1,
        ]);

        // ── 3. MENU HIERARCHY (Sesuai PRD Bab 3 & Desain Bab 2) ─────────
        $menus = [
            // Top-level & Standalone
            ['mId' => 1,  'mNama' => 'Dashboard',           'mRoute' => 'dashboard',           'mParentId' => null, 'mIcon' => 'fa-tachometer-alt', 'mOrder' => 1],
            ['mId' => 2,  'mNama' => 'Booking dan Antrian', 'mRoute' => 'bookings',            'mParentId' => null, 'mIcon' => 'fa-calendar-check', 'mOrder' => 2],

            // Kasir (Parent = 3)
            ['mId' => 3,  'mNama' => 'Kasir',               'mRoute' => null,                  'mParentId' => null, 'mIcon' => 'fa-cash-register',  'mOrder' => 3],
            ['mId' => 4,  'mNama' => 'POS Kasir',           'mRoute' => 'pos',                 'mParentId' => 3,    'mIcon' => '',                  'mOrder' => 1],
            ['mId' => 5,  'mNama' => 'Riwayat & Refund',    'mRoute' => 'transactions',        'mParentId' => 3,    'mIcon' => '',                  'mOrder' => 2],
            ['mId' => 6,  'mNama' => 'Shift Kasir',         'mRoute' => 'cash-shifts',         'mParentId' => 3,    'mIcon' => '',                  'mOrder' => 3],

            // Pelanggan (Standalone = 7)
            ['mId' => 7,  'mNama' => 'Pelanggan',           'mRoute' => 'customers',           'mParentId' => null, 'mIcon' => 'fa-user-friends',   'mOrder' => 4],

            // Layanan dan Harga (Parent = 8)
            ['mId' => 8,  'mNama' => 'Layanan dan Harga',   'mRoute' => null,                  'mParentId' => null, 'mIcon' => 'fa-spa',            'mOrder' => 5],
            ['mId' => 9,  'mNama' => 'Kategori Layanan',    'mRoute' => 'service-categories',  'mParentId' => 8,    'mIcon' => '',                  'mOrder' => 1],
            ['mId' => 10, 'mNama' => 'Daftar Layanan',      'mRoute' => 'services',            'mParentId' => 8,    'mIcon' => '',                  'mOrder' => 2],

            // Stylish (Parent = 11)
            ['mId' => 11, 'mNama' => 'Stylish',             'mRoute' => null,                  'mParentId' => null, 'mIcon' => 'fa-cut',            'mOrder' => 6],
            ['mId' => 12, 'mNama' => 'Daftar Stylist',      'mRoute' => 'stylists',            'mParentId' => 11,   'mIcon' => '',                  'mOrder' => 1],
            ['mId' => 13, 'mNama' => 'Jadwal & Cuti',       'mRoute' => 'stylist-schedules',   'mParentId' => 11,   'mIcon' => '',                  'mOrder' => 2],

            // Stok Produk (Parent = 14)
            ['mId' => 14, 'mNama' => 'Stok Produk',         'mRoute' => null,                  'mParentId' => null, 'mIcon' => 'fa-boxes',          'mOrder' => 7],
            ['mId' => 15, 'mNama' => 'Daftar Produk',       'mRoute' => 'products',            'mParentId' => 14,   'mIcon' => '',                  'mOrder' => 1],
            ['mId' => 16, 'mNama' => 'Mutasi & Kartu Stok', 'mRoute' => 'stocks',              'mParentId' => 14,   'mIcon' => '',                  'mOrder' => 2],
            ['mId' => 17, 'mNama' => 'Kategori Produk',     'mRoute' => 'product-categories',  'mParentId' => 14,   'mIcon' => '',                  'mOrder' => 3],
            ['mId' => 18, 'mNama' => 'Satuan Produk',       'mRoute' => 'units',               'mParentId' => 14,   'mIcon' => '',                  'mOrder' => 4],
            ['mId' => 19, 'mNama' => 'Supplier',            'mRoute' => 'suppliers',           'mParentId' => 14,   'mIcon' => '',                  'mOrder' => 5],

            // Laporan (Standalone = 20)
            ['mId' => 20, 'mNama' => 'Laporan',             'mRoute' => 'reports',             'mParentId' => null, 'mIcon' => 'fa-chart-bar',      'mOrder' => 8],

            // Manajemen User (Parent = 21)
            ['mId' => 21, 'mNama' => 'Manajemen User',      'mRoute' => null,                  'mParentId' => null, 'mIcon' => 'fa-users-cog',      'mOrder' => 9],
            ['mId' => 22, 'mNama' => 'Users',               'mRoute' => 'users',               'mParentId' => 21,   'mIcon' => '',                  'mOrder' => 1],
            ['mId' => 23, 'mNama' => 'Role',                'mRoute' => 'roles',               'mParentId' => 21,   'mIcon' => '',                  'mOrder' => 2],
            ['mId' => 24, 'mNama' => 'Menu',                'mRoute' => 'menus',               'mParentId' => 21,   'mIcon' => '',                  'mOrder' => 3],
            ['mId' => 25, 'mNama' => 'Audit Log',           'mRoute' => 'audit-logs',          'mParentId' => 21,   'mIcon' => '',                  'mOrder' => 4],
            ['mId' => 26, 'mNama' => 'Ganti Password',      'mRoute' => 'ganti-password',      'mParentId' => 21,   'mIcon' => '',                  'mOrder' => 5],

            // Pengaturan (Parent = 27)
            ['mId' => 27, 'mNama' => 'Pengaturan',          'mRoute' => null,                  'mParentId' => null, 'mIcon' => 'fa-sliders-h',      'mOrder' => 10],
            ['mId' => 28, 'mNama' => 'Profil & Printer',    'mRoute' => 'salon-settings',      'mParentId' => 27,   'mIcon' => '',                  'mOrder' => 1],
            ['mId' => 29, 'mNama' => 'Metode Pembayaran',   'mRoute' => 'payment-methods',     'mParentId' => 27,   'mIcon' => '',                  'mOrder' => 2],
        ];

        foreach ($menus as $m) {
            Menu::create(array_merge($m, [
                'mIsActive' => 1,
                'mCreatedBy' => 'Seeder',
                'mUpdatedBy' => 'Seeder',
                'mCreatedAt' => now(),
                'mUpdatedAt' => now(),
            ]));
        }

        // ── 4. ROLE_MENU ASSIGNMENT ─────────────────────────────────────
        $roleMenus = [
            // Owner / Admin: Semua menu (1..29)
            1 => range(1, 29),
            // Admin / Resepsionis: Dashboard, Booking, Pelanggan, Layanan, Stylish, Laporan, Ganti Password
            2 => [1, 2, 7, 8, 9, 10, 11, 12, 13, 20, 21, 26],
            // Kasir: Dashboard, Booking, Kasir (POS, Riwayat, Shift), Pelanggan, Layanan, Produk, Ganti Password
            3 => [1, 2, 3, 4, 5, 6, 7, 8, 10, 14, 15, 21, 26],
            // Stylist: Dashboard, Booking, Stylish (Daftar & Jadwal), Ganti Password
            4 => [1, 2, 11, 12, 13, 21, 26],
            // Petugas Stok: Dashboard, Stok Produk (14..19), Laporan, Ganti Password
            5 => [1, 14, 15, 16, 17, 18, 19, 20, 21, 26],
        ];

        $pivotRows = [];
        foreach ($roleMenus as $roleId => $menuIds) {
            foreach ($menuIds as $menuId) {
                $pivotRows[] = [
                    'rmRoleId' => $roleId,
                    'rmMenuId' => $menuId,
                    'rmCreatedAt' => now(),
                    'rmUpdatedAt' => now(),
                ];
            }
        }
        DB::table('role_menu')->insert($pivotRows);

        // ── 5. SALON SETTINGS & PAYMENT METHODS ─────────────────────────
        $salonSetting = SalonSetting::current();

        $pmCash = PaymentMethod::create(['code' => 'CASH', 'name' => 'Tunai (Cash)', 'type' => 'cash', 'account_number' => 'Laci Kasir Utama', 'is_active' => 1]);
        $pmQris = PaymentMethod::create(['code' => 'QRIS', 'name' => 'QRIS ' . $salonSetting->salon_name, 'type' => 'qris', 'account_number' => 'NMID-9988776655', 'is_active' => 1]);
        PaymentMethod::create(['code' => 'BCA', 'name' => 'Transfer BCA', 'type' => 'transfer', 'account_number' => '5240-8899-01 a/n ' . $salonSetting->salon_name, 'is_active' => 1]);
        PaymentMethod::create(['code' => 'DEBIT', 'name' => 'Kartu Debit / Kredit (EDC)', 'type' => 'card', 'account_number' => 'EDC BCA/Mandiri', 'is_active' => 1]);

        // ── 6. SERVICE CATEGORIES & SERVICES ────────────────────────────
        $catCut = ServiceCategory::create(['name' => 'Hair Cut & Styling', 'description' => 'Potong rambut, cuci, dan blow styling', 'is_active' => 1]);
        $catSpa = ServiceCategory::create(['name' => 'Hair Treatment & Spa', 'description' => 'Creambath, Hair Mask, dan Keratin Therapy', 'is_active' => 1]);
        $catColor = ServiceCategory::create(['name' => 'Hair Coloring & Perm', 'description' => 'Pewarnaan rambut, Balayage, dan Smoothing', 'is_active' => 1]);
        $catNail = ServiceCategory::create(['name' => 'Nail Care & Art', 'description' => 'Manicure, Pedicure, dan Gel Polish', 'is_active' => 1]);

        $svcCut = Service::create([
            'service_code' => 'SRV-001',
            'service_category_id' => $catCut->id,
            'name' => 'Signature Wash, Cut & Blow Dry',
            'price' => 150000,
            'duration_minutes' => 45,
            'commission_type' => 'percentage',
            'commission_value' => 15,
            'is_active' => 1,
        ]);

        $svcSpa = Service::create([
            'service_code' => 'SRV-002',
            'service_category_id' => $catSpa->id,
            'name' => 'Royal Keratin Hair Spa',
            'price' => 250000,
            'duration_minutes' => 60,
            'commission_type' => 'percentage',
            'commission_value' => 15,
            'is_active' => 1,
        ]);

        Service::create([
            'service_code' => 'SRV-003',
            'service_category_id' => $catColor->id,
            'name' => 'Full Balayage & Toning',
            'price' => 650000,
            'duration_minutes' => 120,
            'commission_type' => 'percentage',
            'commission_value' => 12,
            'is_active' => 1,
        ]);

        Service::create([
            'service_code' => 'SRV-004',
            'service_category_id' => $catNail->id,
            'name' => 'Luxury Manicure & Gel Polish',
            'price' => 180000,
            'duration_minutes' => 60,
            'commission_type' => 'fixed',
            'commission_value' => 25000,
            'is_active' => 1,
        ]);

        // ── 7. STYLISTS & SCHEDULES ─────────────────────────────────────
        $stylist1 = Employee::create([
            'employee_code' => 'STY-001',
            'user_id' => $stylistUser->id,
            'name' => 'Bella Anastasia',
            'phone' => '081288990011',
            'specialization' => 'Senior Hair Stylist & Colorist',
            'joined_date' => '2024-02-01',
            'commission_type' => 'percentage',
            'commission_value' => 15,
            'is_active' => 1,
        ]);

        $stylist2 = Employee::create([
            'employee_code' => 'STY-002',
            'name' => 'Clara Wijaya',
            'phone' => '081288990022',
            'specialization' => 'Hair Spa & Scalp Specialist',
            'joined_date' => '2024-05-10',
            'commission_type' => 'percentage',
            'commission_value' => 15,
            'is_active' => 1,
        ]);

        $stylist3 = Employee::create([
            'employee_code' => 'STY-003',
            'name' => 'Kevin Pratama',
            'phone' => '081288990033',
            'specialization' => 'Nail Artist & Hair Styling',
            'joined_date' => '2025-01-15',
            'commission_type' => 'fixed',
            'commission_value' => 25000,
            'is_active' => 1,
        ]);

        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
        foreach ([$stylist1, $stylist2, $stylist3] as $sty) {
            foreach ($days as $day) {
                EmployeeSchedule::create([
                    'employee_id' => $sty->id,
                    'day_of_week' => $day,
                    'start_time' => '08:00',
                    'end_time' => '21:00',
                    'is_day_off' => 0,
                ]);
            }
        }

        // ── 8. PRODUCTS, UNITS, SUPPLIERS & INITIAL STOCK LEDGER ────────
        $pCat1 = ProductCategory::create(['name' => 'Hair Serum & Vitamin', 'description' => 'Serum dan vitamin perawatan rambut', 'is_active' => 1]);
        $pCat2 = ProductCategory::create(['name' => 'Shampoo & Conditioner', 'description' => 'Produk perawatan harian salon', 'is_active' => 1]);

        $unitBotol = Unit::create(['name' => 'Botol', 'symbol' => 'btl']);
        $unitTube = Unit::create(['name' => 'Tube', 'symbol' => 'tube']);

        $supplier = Supplier::create([
            'supplier_code' => 'SUP-001',
            'name' => 'PT. L\'Oreal & Kerastase Distribusi',
            'phone' => '021-55001122',
            'email' => 'order@loreal-dist.co.id',
            'address' => 'Kawasan Industri Pulogadung, Jakarta',
            'is_active' => 1,
        ]);

        $prdSerum = Product::create([
            'sku' => 'PRD-001',
            'barcode' => '8991002003001',
            'product_category_id' => $pCat1->id,
            'unit_id' => $unitBotol->id,
            'supplier_id' => $supplier->id,
            'name' => 'Moroccan Argan Hair Serum 100ml',
            'purchase_price' => 90000,
            'sell_price' => 145000,
            'current_stock' => 0,
            'min_stock' => 5,
            'is_active' => 1,
        ]);

        $prdShampoo = Product::create([
            'sku' => 'PRD-002',
            'barcode' => '8991002003002',
            'product_category_id' => $pCat2->id,
            'unit_id' => $unitBotol->id,
            'supplier_id' => $supplier->id,
            'name' => 'Keratin Repair Shampoo 250ml',
            'purchase_price' => 110000,
            'sell_price' => 175000,
            'current_stock' => 0,
            'min_stock' => 5,
            'is_active' => 1,
        ]);

        $prdMask = Product::create([
            'sku' => 'PRD-003',
            'barcode' => '8991002003003',
            'product_category_id' => $pCat2->id,
            'unit_id' => $unitTube->id,
            'supplier_id' => $supplier->id,
            'name' => 'Collagen Intensive Hair Mask 200gr',
            'purchase_price' => 85000,
            'sell_price' => 135000,
            'current_stock' => 0,
            'min_stock' => 5,
            'is_active' => 1,
        ]);

        // Posting stok awal via StockService (Ledger)
        $stockService = app(StockService::class);
        $stockService->recordMovement(
            'receive',
            [
                ['product_id' => $prdSerum->id, 'qty_in' => 20, 'unit_cost' => 90000, 'notes' => 'Saldo awal stok'],
                ['product_id' => $prdShampoo->id, 'qty_in' => 15, 'unit_cost' => 110000, 'notes' => 'Saldo awal stok'],
                ['product_id' => $prdMask->id, 'qty_in' => 4, 'unit_cost' => 85000, 'notes' => 'Saldo awal stok (contoh low stock)'],
            ],
            'Penerimaan stok awal pembukaan cabang',
            $supplier->id
        );

        // ── 9. CUSTOMERS ────────────────────────────────────────────────
        $cust1 = Customer::create([
            'customer_code' => 'CUST-0001',
            'name' => 'Putri Maharani',
            'phone' => '081199887711',
            'email' => 'putri@gmail.com',
            'gender' => 'P',
            'notes' => 'Suka air hangat saat hair wash',
            'is_active' => 1,
        ]);

        $cust2 = Customer::create([
            'customer_code' => 'CUST-0002',
            'name' => 'Amanda Saraswati',
            'phone' => '081199887722',
            'email' => 'amanda@gmail.com',
            'gender' => 'P',
            'notes' => 'Alergi pewarna rambut beramonia tinggi',
            'is_active' => 1,
        ]);

        // ── 10. SAMPLE BOOKING, QUEUE, SHIFT & POS TRANSACTION ──────────
        $shift = CashShift::create([
            'shift_number' => \App\Services\DocumentNumberService::generate('SHF'),
            'user_id' => $adminUser->id,
            'opened_at' => now()->subHours(2),
            'opening_cash' => 300000,
            'expected_cash' => 300000,
            'status' => 'open',
            'notes' => 'Shift Operasional Utama',
        ]);

        $bookingService = app(BookingService::class);
        $apt = $bookingService->createBooking([
            'customer_id' => $cust1->id,
            'appointment_date' => now()->format('Y-m-d'),
            'start_time' => '10:00',
            'service_ids' => [$svcCut->id, $svcSpa->id],
            'employee_id' => $stylist1->id,
            'notes' => 'Booking VIP pagi',
        ]);

        $so = $bookingService->checkInAppointment($apt);

        // Satu antrian walk-in aktif yang menunggu dilayani
        $bookingService->createWalkInQueue([
            'customer_id' => $cust2->id,
            'service_ids' => [$svcCut->id],
            'employee_id' => $stylist2->id,
            'notes' => 'Walk-in siang',
        ]);

        // Checkout sample transaksi pertama dari Service Order #1
        $checkoutService = app(CheckoutService::class);
        $checkoutService->checkout([
            'cash_shift_id' => $shift->id,
            'customer_id' => $cust1->id,
            'service_order_id' => $so->id,
            'idempotency_key' => 'seed-trx-001',
            'discount_amount' => 0,
            'items' => [
                [
                    'item_type' => 'service',
                    'item_id' => $svcCut->id,
                    'quantity' => 1,
                    'discount_amount' => 0,
                    'employee_id' => $stylist1->id,
                ],
                [
                    'item_type' => 'service',
                    'item_id' => $svcSpa->id,
                    'quantity' => 1,
                    'discount_amount' => 0,
                    'employee_id' => $stylist1->id,
                ],
                [
                    'item_type' => 'product',
                    'item_id' => $prdSerum->id,
                    'quantity' => 1,
                    'discount_amount' => 0,
                    'employee_id' => $stylist1->id,
                ],
            ],
            'payments' => [
                [
                    'payment_method_id' => $pmCash->id,
                    'amount' => 300000,
                    'reference_number' => null,
                ],
                [
                    'payment_method_id' => $pmQris->id,
                    'amount' => 245000,
                    'reference_number' => 'QRIS-SEED-001',
                ],
            ],
        ]);

        auth()->logout();
    }
}
