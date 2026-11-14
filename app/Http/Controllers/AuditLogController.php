<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->get('search');
        $module = $request->get('module');

        $query = AuditLog::query();

        if ($module) {
            $query->where('module', $module);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('user_name', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $logs = $query->orderByDesc('id')->paginate(15)->withQueryString();
        $modules = AuditLog::select('module')->distinct()->pluck('module');

        return view('settings.audit-logs', compact('logs', 'search', 'module', 'modules'));
    }
}
