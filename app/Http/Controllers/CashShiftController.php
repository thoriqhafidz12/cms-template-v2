<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CashShift;
use App\Services\DocumentNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashShiftController extends Controller
{
    public function index(): View
    {
        $activeShift = CashShift::where('status', 'open')
            ->with('user')
            ->latest('id')
            ->first();

        $shifts = CashShift::with('user')
            ->withCount('transactions')
            ->orderByDesc('id')
            ->paginate(10);

        return view('pos.shifts', compact('activeShift', 'shifts'));
    }

    public function open(Request $request): RedirectResponse
    {
        $existingOpen = CashShift::where('status', 'open')->first();
        if ($existingOpen) {
            return back()->with('error', "Masih ada shift kasir yang aktif ({$existingOpen->shift_number}). Tutup shift tersebut terlebih dahulu.");
        }

        $validated = $request->validate([
            'opening_cash' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $shiftNumber = DocumentNumberService::generate('SHF');
        $openingCash = round((float) $validated['opening_cash'], 2);

        $shift = CashShift::create([
            'shift_number' => $shiftNumber,
            'user_id' => auth()->id(),
            'opened_at' => now(),
            'opening_cash' => $openingCash,
            'expected_cash' => $openingCash,
            'status' => 'open',
            'notes' => $validated['notes'] ?? null,
        ]);

        AuditLog::record(
            'shift.open',
            'pos',
            "Buka shift kasir {$shiftNumber} dengan modal awal Rp " . number_format($openingCash, 0, ',', '.'),
            $shift->id,
            $shiftNumber
        );

        if ($request->boolean('redirect_pos')) {
            return redirect()->route('pos.index')->with('success', "Shift kasir {$shiftNumber} berhasil dibuka.");
        }

        return redirect()->route('cash-shifts.index')->with('success', "Shift kasir {$shiftNumber} berhasil dibuka.");
    }

    public function close(Request $request, CashShift $cashShift): RedirectResponse
    {
        if ($cashShift->status !== 'open') {
            return back()->with('error', 'Shift kasir ini sudah ditutup.');
        }

        $validated = $request->validate([
            'actual_cash' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $actualCash = round((float) $validated['actual_cash'], 2);
        $diff = round($actualCash - (float) $cashShift->expected_cash, 2);

        $cashShift->update([
            'closed_at' => now(),
            'actual_cash' => $actualCash,
            'difference_cash' => $diff,
            'status' => 'closed',
            'notes' => $validated['notes'] ?? $cashShift->notes,
        ]);

        AuditLog::record(
            'shift.close',
            'pos',
            "Tutup shift kasir {$cashShift->shift_number} (Sistem: Rp " . number_format((float) $cashShift->expected_cash, 0, ',', '.') . ", Aktual: Rp " . number_format($actualCash, 0, ',', '.') . ", Selisih: Rp " . number_format($diff, 0, ',', '.') . ')',
            $cashShift->id,
            $cashShift->shift_number
        );

        return redirect()->route('cash-shifts.index')->with('success', "Shift kasir {$cashShift->shift_number} berhasil ditutup.");
    }
}
