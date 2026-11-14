<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Service;
use App\Models\ServiceOrder;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(
        protected BookingService $bookingService
    ) {}

    public function index(Request $request): View
    {
        $date = $request->get('date', now()->format('Y-m-d'));
        $status = $request->get('status');
        $stylistId = $request->get('employee_id');

        $appointmentsQuery = Appointment::with(['customer', 'details.employee', 'serviceOrder'])
            ->whereDate('appointment_date', $date)
            ->orderBy('start_at');

        if ($status) {
            $appointmentsQuery->where('status', $status);
        }

        if ($stylistId) {
            $appointmentsQuery->whereHas('details', fn ($q) => $q->where('employee_id', $stylistId));
        }

        $appointments = $appointmentsQuery->get();

        $queues = ServiceOrder::with(['customer', 'details.employee', 'appointment', 'transaction'])
            ->whereDate('queue_date', $date)
            ->orderBy('queue_number')
            ->get();

        $customers = Customer::where('is_active', 1)->orderBy('name')->get();
        $services = Service::with('category')->where('is_active', 1)->orderBy('name')->get();
        $stylists = Employee::where('is_active', 1)->orderBy('name')->get();

        return view('bookings.index', compact(
            'date',
            'status',
            'stylistId',
            'appointments',
            'queues',
            'customers',
            'services',
            'stylists'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'new_customer_name' => ['nullable', 'string', 'max:150'],
            'new_customer_phone' => ['nullable', 'string', 'max:40'],
            'appointment_date' => ['required', 'date'],
            'start_time' => ['required', 'string'],
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => ['integer', 'exists:services,id'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        // Buat pelanggan baru cepat jika dipilih
        $customerId = $validated['customer_id'] ?? null;
        if (!$customerId && !empty($validated['new_customer_name'])) {
            $next = ((int) Customer::withTrashed()->max('id')) + 1;
            $customer = Customer::create([
                'customer_code' => sprintf('CUST-%04d', $next),
                'name' => $validated['new_customer_name'],
                'phone' => $validated['new_customer_phone'] ?? '-',
                'is_active' => 1,
            ]);
            $customerId = $customer->id;
        }

        if (!$customerId) {
            return back()->with('error', 'Pilih pelanggan terdaftar atau isi nama pelanggan baru.')->withInput();
        }

        $validated['customer_id'] = $customerId;
        $appointment = $this->bookingService->createBooking($validated);

        return redirect()
            ->route('bookings.index', ['date' => $validated['appointment_date']])
            ->with('success', "Booking {$appointment->appointment_number} berhasil dibuat.");
    }

    public function storeWalkIn(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'new_customer_name' => ['nullable', 'string', 'max:150'],
            'new_customer_phone' => ['nullable', 'string', 'max:40'],
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => ['integer', 'exists:services,id'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $customerId = $validated['customer_id'] ?? null;
        if (!$customerId && !empty($validated['new_customer_name'])) {
            $next = ((int) Customer::withTrashed()->max('id')) + 1;
            $customer = Customer::create([
                'customer_code' => sprintf('CUST-%04d', $next),
                'name' => $validated['new_customer_name'],
                'phone' => $validated['new_customer_phone'] ?? '-',
                'is_active' => 1,
            ]);
            $customerId = $customer->id;
        }

        $validated['customer_id'] = $customerId;
        $so = $this->bookingService->createWalkInQueue($validated);

        return redirect()
            ->route('bookings.index', ['date' => now()->format('Y-m-d')])
            ->with('success', "Antrian Walk-In #{$so->queue_number} ({$so->service_order_number}) berhasil ditambahkan.");
    }

    public function checkIn(Appointment $appointment): RedirectResponse
    {
        $so = $this->bookingService->checkInAppointment($appointment);

        return redirect()
            ->route('bookings.index', ['date' => now()->format('Y-m-d')])
            ->with('success', "Pelanggan berhasil Check-In ke Nomor Antrian #{$so->queue_number} ({$so->service_order_number}).");
    }

    public function updateStatus(Request $request, Appointment $appointment): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,confirmed,in_service,completed,cancelled,no_show'],
            'cancel_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $appointment->update([
            'status' => $validated['status'],
            'cancel_reason' => $validated['cancel_reason'] ?? $appointment->cancel_reason,
        ]);

        AuditLog::record(
            'booking.status',
            'booking',
            "Status booking {$appointment->appointment_number} diubah menjadi {$validated['status']}.",
            $appointment->id,
            $appointment->appointment_number
        );

        return back()->with('success', "Status booking {$appointment->appointment_number} diperbarui.");
    }

    public function updateQueueStatus(Request $request, ServiceOrder $serviceOrder): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:waiting,called,in_service,completed,cancelled'],
        ]);

        $updates = ['status' => $validated['status']];
        if ($validated['status'] === 'in_service' && !$serviceOrder->started_at) {
            $updates['started_at'] = now();
        }
        if ($validated['status'] === 'completed' && !$serviceOrder->completed_at) {
            $updates['completed_at'] = now();
        }

        $serviceOrder->update($updates);
        $serviceOrder->details()->update(['status' => $validated['status']]);

        if ($serviceOrder->appointment) {
            if ($validated['status'] === 'in_service') {
                $serviceOrder->appointment->update(['status' => 'in_service']);
            } elseif ($validated['status'] === 'completed') {
                $serviceOrder->appointment->update(['status' => 'completed']);
            }
        }

        AuditLog::record(
            'queue.status',
            'booking',
            "Status antrian {$serviceOrder->service_order_number} diubah menjadi {$validated['status']}.",
            $serviceOrder->id,
            $serviceOrder->service_order_number
        );

        return back()->with('success', "Status antrian #{$serviceOrder->queue_number} diperbarui menjadi {$validated['status']}.");
    }
}
