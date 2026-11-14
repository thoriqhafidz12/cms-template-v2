<?php

namespace App\Http\Controllers;

use App\Models\CashShift;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SalonSetting;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceOrder;
use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PosController extends Controller
{
    public function __construct(
        protected CheckoutService $checkoutService
    ) {}

    public function index(Request $request): View
    {
        $setting = SalonSetting::current();
        $activeShift = CashShift::where('status', 'open')->latest('id')->first();

        $serviceCategories = ServiceCategory::where('is_active', 1)->orderBy('name')->get();
        $services = Service::with('category')->where('is_active', 1)->orderBy('name')->get();

        $productCategories = ProductCategory::where('is_active', 1)->orderBy('name')->get();
        $products = Product::with(['category', 'unit'])->where('is_active', 1)->orderBy('name')->get();

        $stylists = Employee::where('is_active', 1)->orderBy('name')->get();
        $customers = Customer::where('is_active', 1)->orderBy('name')->get();
        $paymentMethods = PaymentMethod::where('is_active', 1)->orderBy('id')->get();

        // Antrian / Service Order yang belum ditagihkan di POS
        $pendingOrders = ServiceOrder::with(['customer', 'details.employee'])
            ->whereDoesntHave('transaction')
            ->whereIn('status', ['waiting', 'called', 'in_service', 'completed'])
            ->orderByDesc('id')
            ->limit(25)
            ->get();

        $selectedServiceOrder = null;
        $initialCart = [];

        if ($request->filled('service_order_id')) {
            $selectedServiceOrder = ServiceOrder::with(['customer', 'details.service', 'details.employee'])
                ->find($request->get('service_order_id'));

            if ($selectedServiceOrder) {
                foreach ($selectedServiceOrder->details as $det) {
                    $initialCart[] = [
                        'uid' => 'so_' . $det->id,
                        'item_type' => 'service',
                        'item_id' => $det->service_id,
                        'name' => $det->service_name_snapshot,
                        'price' => (float) ($det->service?->price ?? $det->price_snapshot),
                        'quantity' => 1,
                        'discount_amount' => 0,
                        'employee_id' => $det->employee_id ?: '',
                    ];
                }
            }
        }

        $idempotencyKey = (string) Str::uuid();

        return view('pos.index', compact(
            'setting',
            'activeShift',
            'serviceCategories',
            'services',
            'productCategories',
            'products',
            'stylists',
            'customers',
            'paymentMethods',
            'pendingOrders',
            'selectedServiceOrder',
            'initialCart',
            'idempotencyKey'
        ));
    }

    public function checkout(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cash_shift_id' => ['required', 'integer', 'exists:cash_shifts,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'service_order_id' => ['nullable', 'integer', 'exists:service_orders,id'],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'idempotency_key' => ['required', 'string', 'max:100'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'use_tax' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_type' => ['required', 'in:service,product'],
            'items.*.item_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.employee_id' => ['nullable', 'integer'],
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'payments.*.amount' => ['required', 'numeric', 'min:0'],
            'payments.*.reference_number' => ['nullable', 'string', 'max:100'],
        ]);

        $transaction = $this->checkoutService->checkout($validated);

        return redirect()
            ->route('pos.receipt', $transaction->id)
            ->with('success', "Transaksi {$transaction->transaction_number} berhasil diproses!");
    }
}
