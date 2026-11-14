<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentDetail;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeLeave;
use App\Models\EmployeeSchedule;
use App\Models\SalonSetting;
use App\Models\Service;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public const DAY_MAP = [
        0 => 'Minggu',
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
    ];

    /**
     * Buat booking baru beserta pemeriksaan jadwal operasional, jadwal/cuti stylist, dan konflik jadwal.
     */
    public function createBooking(array $data): Appointment
    {
        return DB::transaction(function () use ($data) {
            $setting = SalonSetting::current();
            $date = Carbon::parse($data['appointment_date'])->format('Y-m-d');
            $startAt = Carbon::parse($date . ' ' . $data['start_time']);

            $serviceIds = (array) ($data['service_ids'] ?? []);
            $stylistIds = (array) ($data['employee_ids'] ?? []);

            if (empty($serviceIds)) {
                throw ValidationException::withMessages([
                    'service_ids' => 'Pilih minimal satu layanan untuk booking.',
                ]);
            }

            $services = Service::whereIn('id', $serviceIds)
                ->where('is_active', 1)
                ->get()
                ->keyBy('id');

            if ($services->count() !== count(array_unique($serviceIds))) {
                throw ValidationException::withMessages([
                    'service_ids' => 'Terdapat layanan yang tidak aktif atau tidak valid.',
                ]);
            }

            $totalDuration = 0;
            $estimatedTotal = 0;
            $detailRows = [];

            foreach ($serviceIds as $idx => $sId) {
                $service = $services[$sId];
                $empId = !empty($stylistIds[$idx]) ? (int) $stylistIds[$idx] : (!empty($data['employee_id']) ? (int) $data['employee_id'] : null);

                $totalDuration += (int) $service->duration_minutes;
                $estimatedTotal += (float) $service->price;

                $detailRows[] = [
                    'service_id' => $service->id,
                    'employee_id' => $empId,
                    'service_name_snapshot' => $service->name,
                    'duration_minutes' => (int) $service->duration_minutes,
                    'price_snapshot' => (float) $service->price,
                    'sort_order' => $idx + 1,
                ];
            }

            $endAt = (clone $startAt)->addMinutes(max(15, $totalDuration));

            // Validasi stylist jadwal, cuti, dan konflik booking
            $uniqueEmployeeIds = array_values(array_unique(array_filter(array_column($detailRows, 'employee_id'))));
            foreach ($uniqueEmployeeIds as $empId) {
                $this->validateStylistAvailability((int) $empId, $startAt, $endAt);
            }

            $appointmentNumber = DocumentNumberService::generate($setting->booking_prefix ?: 'BKG', $startAt->format('Ymd'));

            $appointment = Appointment::create([
                'appointment_number' => $appointmentNumber,
                'customer_id' => $data['customer_id'],
                'appointment_date' => $date,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'total_duration_minutes' => $totalDuration,
                'estimated_total' => $estimatedTotal,
                'status' => $data['status'] ?? 'confirmed',
                'source' => $data['source'] ?? 'admin',
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($detailRows as $row) {
                $appointment->details()->create($row);
            }

            AuditLog::record(
                'booking.create',
                'booking',
                "Booking {$appointmentNumber} dibuat untuk tanggal {$startAt->format('d/m/Y H:i')}.",
                $appointment->id,
                $appointmentNumber
            );

            return $appointment->load(['customer', 'details.employee']);
        });
    }

    /**
     * Validasi apakah stylist aktif, tidak sedang libur/cuti, dan tidak bentrok jadwal booking lain.
     */
    public function validateStylistAvailability(int $employeeId, Carbon $startAt, Carbon $endAt, ?int $ignoreAppointmentId = null): void
    {
        $employee = Employee::find($employeeId);
        if (!$employee || !$employee->is_active) {
            throw ValidationException::withMessages([
                'employee_id' => 'Stylist tidak ditemukan atau sedang nonaktif.',
            ]);
        }

        $date = $startAt->format('Y-m-d');
        $dayName = self::DAY_MAP[$startAt->dayOfWeek] ?? 'Senin';

        // Cek cuti
        $onLeave = EmployeeLeave::where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->exists();

        if ($onLeave) {
            throw ValidationException::withMessages([
                'employee_id' => "Stylist {$employee->name} sedang cuti/libur pada tanggal {$startAt->format('d/m/Y')}.",
            ]);
        }

        // Cek jadwal kerja harian jika sudah diatur
        $schedule = EmployeeSchedule::where('employee_id', $employeeId)
            ->where('day_of_week', $dayName)
            ->first();

        if ($schedule) {
            if ($schedule->is_day_off) {
                throw ValidationException::withMessages([
                    'employee_id' => "Stylist {$employee->name} libur rutin pada hari {$dayName}.",
                ]);
            }

            $shiftStart = Carbon::parse($date . ' ' . $schedule->start_time);
            $shiftEnd = Carbon::parse($date . ' ' . $schedule->end_time);

            if ($startAt->lt($shiftStart) || $endAt->gt($shiftEnd)) {
                throw ValidationException::withMessages([
                    'employee_id' => "Waktu booking berada di luar jam kerja Stylist {$employee->name} ({$schedule->start_time} - {$schedule->end_time}).",
                ]);
            }
        }

        // Cek konflik dengan appointment aktif lain pada stylist yang sama
        $conflictQuery = Appointment::whereIn('status', ['pending', 'confirmed', 'checked_in', 'in_service'])
            ->where('start_at', '<', $endAt)
            ->where('end_at', '>', $startAt)
            ->whereHas('details', function ($q) use ($employeeId) {
                $q->where('employee_id', $employeeId);
            });

        if ($ignoreAppointmentId) {
            $conflictQuery->where('id', '!=', $ignoreAppointmentId);
        }

        if ($conflictQuery->exists()) {
            throw ValidationException::withMessages([
                'employee_id' => "Jadwal Stylist {$employee->name} bentrok dengan booking lain pada jam {$startAt->format('H:i')} - {$endAt->format('H:i')}.",
            ]);
        }
    }

    /**
     * Check-in booking menjadi Service Order (Antrian).
     */
    public function checkInAppointment(Appointment $appointment): ServiceOrder
    {
        return DB::transaction(function () use ($appointment) {
            if ($appointment->serviceOrder) {
                return $appointment->serviceOrder;
            }

            if (in_array($appointment->status, ['cancelled', 'no_show'], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Booking yang sudah dibatalkan atau no-show tidak dapat di-check-in.',
                ]);
            }

            $setting = SalonSetting::current();
            $today = now()->format('Y-m-d');
            $nextQueue = ((int) ServiceOrder::whereDate('queue_date', $today)->max('queue_number')) + 1;
            $soNumber = DocumentNumberService::generate($setting->service_order_prefix ?: 'SO');

            $serviceOrder = ServiceOrder::create([
                'service_order_number' => $soNumber,
                'appointment_id' => $appointment->id,
                'customer_id' => $appointment->customer_id,
                'queue_number' => $nextQueue,
                'queue_date' => $today,
                'status' => 'waiting',
                'checked_in_at' => now(),
                'notes' => $appointment->notes,
                'created_by' => auth()->id(),
            ]);

            foreach ($appointment->details as $detail) {
                ServiceOrderDetail::create([
                    'service_order_id' => $serviceOrder->id,
                    'service_id' => $detail->service_id,
                    'employee_id' => $detail->employee_id,
                    'service_name_snapshot' => $detail->service_name_snapshot,
                    'duration_minutes' => $detail->duration_minutes,
                    'price_snapshot' => $detail->price_snapshot,
                    'status' => 'waiting',
                ]);
            }

            $appointment->update(['status' => 'checked_in']);

            AuditLog::record(
                'booking.checkin',
                'booking',
                "Booking {$appointment->appointment_number} check-in menjadi antrian {$soNumber} (Antrian #{$nextQueue}).",
                $serviceOrder->id,
                $soNumber
            );

            return $serviceOrder->load(['customer', 'details.employee']);
        });
    }

    /**
     * Buat antrian walk-in langsung (tanpa appointment).
     */
    public function createWalkInQueue(array $data): ServiceOrder
    {
        return DB::transaction(function () use ($data) {
            $setting = SalonSetting::current();
            $today = now()->format('Y-m-d');
            $nextQueue = ((int) ServiceOrder::whereDate('queue_date', $today)->max('queue_number')) + 1;
            $soNumber = DocumentNumberService::generate($setting->service_order_prefix ?: 'SO');

            $serviceIds = (array) ($data['service_ids'] ?? []);
            $services = Service::whereIn('id', $serviceIds)->where('is_active', 1)->get()->keyBy('id');

            if ($services->isEmpty()) {
                throw ValidationException::withMessages([
                    'service_ids' => 'Pilih minimal satu layanan aktif untuk antrian walk-in.',
                ]);
            }

            $serviceOrder = ServiceOrder::create([
                'service_order_number' => $soNumber,
                'appointment_id' => null,
                'customer_id' => $data['customer_id'] ?: null,
                'queue_number' => $nextQueue,
                'queue_date' => $today,
                'status' => 'waiting',
                'checked_in_at' => now(),
                'notes' => $data['notes'] ?? 'Walk-In',
                'created_by' => auth()->id(),
            ]);

            $employeeId = !empty($data['employee_id']) ? (int) $data['employee_id'] : null;

            foreach ($serviceIds as $sId) {
                if (!isset($services[$sId])) {
                    continue;
                }
                $svc = $services[$sId];
                ServiceOrderDetail::create([
                    'service_order_id' => $serviceOrder->id,
                    'service_id' => $svc->id,
                    'employee_id' => $employeeId,
                    'service_name_snapshot' => $svc->name,
                    'duration_minutes' => $svc->duration_minutes,
                    'price_snapshot' => $svc->price,
                    'status' => 'waiting',
                ]);
            }

            AuditLog::record(
                'queue.walkin',
                'booking',
                "Antrian Walk-In {$soNumber} (#{$nextQueue}) dibuat.",
                $serviceOrder->id,
                $soNumber
            );

            return $serviceOrder->load(['customer', 'details.employee']);
        });
    }
}
