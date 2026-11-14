<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\SalonSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalonSettingController extends Controller
{
    public function index(): View
    {
        $setting = SalonSetting::current();

        return view('settings.salon', compact('setting'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'salon_name' => ['required', 'string', 'max:150'],
            'tagline' => ['nullable', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100'],
            'open_time' => ['required', 'string', 'max:10'],
            'close_time' => ['required', 'string', 'max:10'],
            'tax_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'default_discount_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'booking_prefix' => ['required', 'string', 'max:15'],
            'service_order_prefix' => ['required', 'string', 'max:15'],
            'transaction_prefix' => ['required', 'string', 'max:15'],
            'receipt_width' => ['required', 'in:58mm,80mm'],
            'receipt_footer' => ['nullable', 'string', 'max:500'],
            'low_stock_threshold_default' => ['required', 'integer', 'min:0'],
        ]);

        $setting = SalonSetting::current();
        $setting->update($validated);

        AuditLog::record(
            'setting.update',
            'settings',
            "Pengaturan profil & printer salon diperbarui ({$setting->salon_name}, kertas {$setting->receipt_width}).",
            $setting->id
        );

        return redirect()
            ->route('salon-settings.index')
            ->with('success', 'Pengaturan salon dan printer berhasil disimpan.');
    }
}
