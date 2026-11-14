<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StylistController extends BaseController
{
    public function __construct()
    {
        $this->model = Employee::class;
        $this->route = 'stylists';
        $this->titlePage = 'Daftar Stylish / Stylist';
        $this->primaryKey = 'id';
        $this->table = 'employees';
        $this->searchColumn = ['employee_code', 'name', 'phone', 'specialization'];

        $this->form = [
            [
                'name' => 'employee_code',
                'label' => 'Kode Stylist',
                'placeholder' => 'Kosongkan untuk otomatis (e.g. STY-001)',
                'type' => 'text',
                'col' => 'col-md-12',
                'required' => false,
                'unique' => 'employees,employee_code',
            ],
            [
                'name' => 'name',
                'label' => 'Nama Stylist',
                'placeholder' => 'Masukkan nama lengkap stylist',
                'type' => 'text',
                'col' => 'col-md-12',
                'required' => true,
            ],
            [
                'name' => 'phone',
                'label' => 'Nomor Telepon',
                'placeholder' => '0812xxxxxxxx',
                'type' => 'text',
                'col' => 'col-md-6',
                'required' => false,
            ],
            [
                'name' => 'joined_date',
                'label' => 'Tanggal Bergabung',
                'type' => 'date',
                'col' => 'col-md-6',
                'required' => false,
            ],
            [
                'name' => 'specialization',
                'label' => 'Keahlian / Spesialisasi',
                'placeholder' => 'Contoh: Senior Hair Stylist & Colorist',
                'type' => 'text',
                'col' => 'col-md-12',
                'required' => true,
            ],
            [
                'name' => 'commission_type',
                'label' => 'Aturan Komisi Default',
                'placeholder' => '-- Pilih Tipe --',
                'type' => 'select',
                'col' => 'col-md-6',
                'required' => true,
                'rule_type' => 'string',
                'options' => [
                    ['value' => 'percentage', 'label' => 'Persentase (%)'],
                    ['value' => 'fixed', 'label' => 'Nominal Tetap (Rp)'],
                ],
            ],
            [
                'name' => 'commission_value',
                'label' => 'Nilai Komisi Default',
                'placeholder' => '15',
                'type' => 'number',
                'col' => 'col-md-6',
                'required' => true,
            ],
            [
                'name' => 'user_id',
                'label' => 'Akun Login (Opsional)',
                'placeholder' => '-- Tanpa Akun Login --',
                'type' => 'select',
                'col' => 'col-md-12',
                'required' => false,
                'exists' => 'users,id',
                'options' => [],
            ],
            [
                'name' => 'is_active',
                'label' => 'Status Aktif',
                'placeholder' => '-- Pilih Status --',
                'type' => 'select',
                'col' => 'col-md-12',
                'required' => false,
                'options' => [
                    ['value' => 1, 'label' => 'Aktif'],
                    ['value' => 0, 'label' => 'Nonaktif'],
                ],
            ],
        ];

        $this->grid = [
            ['label' => 'Kode', 'field' => 'employee_code', 'type' => 'text'],
            ['label' => 'Nama Stylist', 'field' => 'name', 'type' => 'text'],
            ['label' => 'Telepon', 'field' => 'phone', 'type' => 'text'],
            ['label' => 'Spesialisasi', 'field' => 'specialization', 'type' => 'text'],
            ['label' => 'Komisi Default', 'field' => 'commission_label', 'type' => 'text'],
            ['label' => 'Status', 'field' => 'status_label', 'type' => 'badge'],
        ];

        $this->extraViewData = [
            'user_ids' => fn () => User::orderBy('name')->get(['id', 'name']),
        ];
    }

    public function index(Request $request): View
    {
        $view = parent::index($request);
        $data = $view->getData();
        $data['items']->getCollection()->transform(function ($item) {
            $item->commission_label = $item->commission_type === 'percentage'
                ? ((float) $item->commission_value . '%')
                : ('Rp ' . number_format((float) $item->commission_value, 0, ',', '.'));
            $item->status_label = $item->is_active ? 'Aktif' : 'Nonaktif';
            return $item;
        });

        return $view;
    }

    protected function beforeSave(array $data, $record = null): array
    {
        if (empty($data['employee_code'])) {
            $next = ((int) Employee::withTrashed()->max('id')) + 1;
            $data['employee_code'] = sprintf('STY-%03d', $next);
        }
        $data['is_active'] = $data['is_active'] ?? 1;
        if (empty($data['user_id'])) {
            $data['user_id'] = null;
        }

        return $data;
    }

    protected function afterSave(array $res): void
    {
        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
        foreach ($days as $day) {
            EmployeeSchedule::firstOrCreate([
                'employee_id' => $res['id'],
                'day_of_week' => $day,
            ], [
                'start_time' => '09:00',
                'end_time' => '20:00',
                'is_day_off' => 0,
            ]);
        }
    }
}
