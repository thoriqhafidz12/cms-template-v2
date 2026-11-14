<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeLeave;
use App\Models\EmployeeSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StylistScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $stylists = Employee::where('is_active', 1)->with('schedules')->orderBy('name')->get();
        $selectedStylistId = $request->get('employee_id', $stylists->first()?->id);
        $selectedStylist = $stylists->firstWhere('id', (int) $selectedStylistId);

        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

        if ($selectedStylist) {
            foreach ($days as $day) {
                EmployeeSchedule::firstOrCreate([
                    'employee_id' => $selectedStylist->id,
                    'day_of_week' => $day,
                ], [
                    'start_time' => '09:00',
                    'end_time' => '20:00',
                    'is_day_off' => 0,
                ]);
            }
            $selectedStylist->load('schedules');
        }

        $leaves = EmployeeLeave::with('employee')
            ->orderByDesc('start_date')
            ->paginate(10);

        return view('stylists.schedules', compact('stylists', 'selectedStylist', 'days', 'leaves'));
    }

    public function updateSchedule(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'schedules' => ['required', 'array'],
        ]);

        foreach ($validated['schedules'] as $day => $row) {
            EmployeeSchedule::updateOrCreate(
                [
                    'employee_id' => $validated['employee_id'],
                    'day_of_week' => $day,
                ],
                [
                    'start_time' => $row['start_time'] ?? '09:00',
                    'end_time' => $row['end_time'] ?? '20:00',
                    'is_day_off' => !empty($row['is_day_off']) ? 1 : 0,
                ]
            );
        }

        return redirect()
            ->route('stylist-schedules.index', ['employee_id' => $validated['employee_id']])
            ->with('success', 'Jadwal kerja stylist berhasil disimpan.');
    }

    public function storeLeave(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $validated['status'] = 'approved';
        EmployeeLeave::create($validated);

        return redirect()
            ->route('stylist-schedules.index', ['employee_id' => $validated['employee_id']])
            ->with('success', 'Data cuti / hari libur stylist berhasil ditambahkan.');
    }

    public function destroyLeave(EmployeeLeave $leave): RedirectResponse
    {
        $leave->delete();

        return back()->with('success', 'Data cuti berhasil dihapus.');
    }
}
