<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->integer('is_active')->default(1)->after('role');
            });
        }

        // 1. Salon Settings
        Schema::create('salon_settings', function (Blueprint $table) {
            $table->id();
            $table->string('salon_name')->default('E-SALON');
            $table->string('tagline')->nullable()->default('Hair, Nail & Spa Studio');
            $table->text('address')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('open_time', 10)->default('09:00');
            $table->string('close_time', 10)->default('20:00');
            $table->string('timezone', 50)->default('Asia/Jakarta');
            $table->string('currency', 10)->default('IDR');
            $table->decimal('tax_percentage', 5, 2)->default(0);
            $table->decimal('default_discount_percentage', 5, 2)->default(0);
            $table->string('booking_prefix', 15)->default('BKG');
            $table->string('service_order_prefix', 15)->default('SO');
            $table->string('transaction_prefix', 15)->default('SAL');
            $table->string('receipt_width', 10)->default('80mm'); // 58mm / 80mm
            $table->text('receipt_footer')->nullable();
            $table->integer('low_stock_threshold_default')->default(5);
            $table->timestamps();
        });

        // 2. Document Sequences
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('doc_type', 30);
            $table->string('sequence_date', 16);
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
            $table->unique(['doc_type', 'sequence_date']);
        });

        // 3. Audit Logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_name')->nullable();
            $table->string('action', 60)->index();
            $table->string('module', 60)->index();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_number', 60)->nullable();
            $table->text('description')->nullable();
            $table->text('payload')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        // 4. Customers
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code', 30)->unique();
            $table->string('name');
            $table->string('phone', 40)->index();
            $table->string('email')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 10)->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->integer('is_active')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        // 5. Employees (Stylists)
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code', 30)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('phone', 40)->nullable();
            $table->string('specialization')->nullable();
            $table->date('joined_date')->nullable();
            $table->string('commission_type', 20)->default('percentage'); // percentage / fixed
            $table->decimal('commission_value', 15, 2)->default(10);
            $table->integer('is_active')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        // 6. Employee Schedules
        Schema::create('employee_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('day_of_week', 20); // Senin..Minggu
            $table->string('start_time', 10)->default('09:00');
            $table->string('end_time', 10)->default('20:00');
            $table->integer('is_day_off')->default(0);
            $table->timestamps();
        });

        // 7. Employee Leaves
        Schema::create('employee_leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('reason')->nullable();
            $table->string('status', 20)->default('approved');
            $table->timestamps();
        });

        // 8. Service Categories
        Schema::create('service_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('is_active')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        // 9. Services
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('service_code', 30)->unique();
            $table->foreignId('service_category_id')->constrained('service_categories')->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2)->default(0);
            $table->integer('duration_minutes')->default(30);
            $table->string('commission_type', 20)->default('percentage'); // percentage / fixed
            $table->decimal('commission_value', 15, 2)->default(10);
            $table->integer('is_active')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        // 10. Product Categories
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('is_active')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        // 11. Units
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('symbol', 20);
            $table->timestamps();
        });

        // 12. Suppliers
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('supplier_code', 30)->unique();
            $table->string('name');
            $table->string('phone', 40)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->integer('is_active')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        // 13. Products
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 40)->unique();
            $table->string('barcode', 60)->nullable()->index();
            $table->foreignId('product_category_id')->constrained('product_categories')->restrictOnDelete();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('name');
            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->decimal('sell_price', 15, 2)->default(0);
            $table->decimal('current_stock', 12, 2)->default(0);
            $table->decimal('min_stock', 12, 2)->default(5);
            $table->integer('is_active')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        // 14. Payment Methods
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('type', 20)->default('cash'); // cash / transfer / qris / card
            $table->string('account_number')->nullable();
            $table->integer('is_active')->default(1);
            $table->timestamps();
        });

        // 15. Appointments (Bookings)
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('appointment_number', 40)->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->date('appointment_date')->index();
            $table->dateTime('start_at')->index();
            $table->dateTime('end_at')->index();
            $table->integer('total_duration_minutes')->default(30);
            $table->decimal('estimated_total', 15, 2)->default(0);
            $table->string('status', 30)->default('confirmed')->index();
            $table->string('source', 30)->default('admin');
            $table->text('notes')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        // 16. Appointment Details
        Schema::create('appointment_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->restrictOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('service_name_snapshot');
            $table->integer('duration_minutes')->default(30);
            $table->decimal('price_snapshot', 15, 2)->default(0);
            $table->integer('sort_order')->default(1);
            $table->timestamps();
        });

        // 17. Service Orders (Queue / Antrian)
        Schema::create('service_orders', function (Blueprint $table) {
            $table->id();
            $table->string('service_order_number', 40)->unique();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->integer('queue_number')->default(1);
            $table->date('queue_date')->index();
            $table->string('status', 30)->default('waiting')->index(); // waiting, called, in_service, completed, cancelled
            $table->dateTime('checked_in_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        // 18. Service Order Details
        Schema::create('service_order_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_order_id')->constrained('service_orders')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->restrictOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('service_name_snapshot');
            $table->integer('duration_minutes')->default(30);
            $table->decimal('price_snapshot', 15, 2)->default(0);
            $table->string('status', 30)->default('waiting');
            $table->timestamps();
        });

        // 19. Cash Shifts
        Schema::create('cash_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('shift_number', 40)->unique();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->dateTime('opened_at');
            $table->dateTime('closed_at')->nullable();
            $table->decimal('opening_cash', 15, 2)->default(0);
            $table->decimal('expected_cash', 15, 2)->default(0);
            $table->decimal('actual_cash', 15, 2)->nullable();
            $table->decimal('difference_cash', 15, 2)->nullable();
            $table->string('status', 20)->default('open')->index(); // open / closed
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 20. Transactions (POS Header)
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 40)->unique();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->foreignId('service_order_id')->nullable()->constrained('service_orders')->nullOnDelete();
            $table->foreignId('cash_shift_id')->constrained('cash_shifts')->restrictOnDelete();
            $table->dateTime('transaction_date')->index();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('change_amount', 15, 2)->default(0);
            $table->string('status', 30)->default('paid')->index(); // draft, unpaid, partially_paid, paid, cancelled, refunded
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['transaction_date', 'status']);
        });

        // 21. Transaction Details
        Schema::create('transaction_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->string('item_type', 20); // service / product
            $table->unsignedBigInteger('item_id')->nullable();
            $table->string('item_name_snapshot');
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->string('commission_type', 20)->nullable();
            $table->decimal('commission_value', 15, 2)->nullable();
            $table->decimal('commission_amount', 15, 2)->default(0);
            $table->timestamps();
        });

        // 22. Payments
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('payment_method_id')->constrained('payment_methods')->restrictOnDelete();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('reference_number', 100)->nullable();
            $table->string('status', 20)->default('paid');
            $table->dateTime('paid_at');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        // 23. Transaction Refunds
        Schema::create('transaction_refunds', function (Blueprint $table) {
            $table->id();
            $table->string('refund_number', 40)->unique();
            $table->foreignId('transaction_id')->constrained('transactions')->restrictOnDelete();
            $table->dateTime('refund_date');
            $table->decimal('total_refund_amount', 15, 2)->default(0);
            $table->text('reason');
            $table->boolean('restock_products')->default(true);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        // 24. Stock Headers (Stock Ledger Header)
        Schema::create('stock_headers', function (Blueprint $table) {
            $table->id();
            $table->string('document_number', 40)->unique();
            $table->dateTime('movement_date')->index();
            $table->string('movement_type', 30)->index(); // receive, sale, usage, adjustment, refund
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('reference_type', 40)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('posted');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        // 25. Stock Details (Stock Ledger Rows)
        Schema::create('stock_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_header_id')->constrained('stock_headers')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('qty_in', 12, 2)->default(0);
            $table->decimal('qty_out', 12, 2)->default(0);
            $table->decimal('balance_after', 12, 2)->default(0);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        // 26. Employee Commissions
        Schema::create('employee_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('transaction_id')->constrained('transactions')->restrictOnDelete();
            $table->foreignId('transaction_detail_id')->constrained('transaction_details')->restrictOnDelete();
            $table->dateTime('commission_date')->index();
            $table->string('service_name_snapshot');
            $table->decimal('base_amount', 15, 2)->default(0);
            $table->string('commission_type', 20);
            $table->decimal('commission_value', 15, 2)->default(0);
            $table->decimal('commission_amount', 15, 2)->default(0);
            $table->string('status', 20)->default('earned')->index(); // earned, reversed, paid
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_commissions');
        Schema::dropIfExists('stock_details');
        Schema::dropIfExists('stock_headers');
        Schema::dropIfExists('transaction_refunds');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('transaction_details');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('cash_shifts');
        Schema::dropIfExists('service_order_details');
        Schema::dropIfExists('service_orders');
        Schema::dropIfExists('appointment_details');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('products');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('units');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('services');
        Schema::dropIfExists('service_categories');
        Schema::dropIfExists('employee_leaves');
        Schema::dropIfExists('employee_schedules');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('document_sequences');
        Schema::dropIfExists('salon_settings');
    }
};
