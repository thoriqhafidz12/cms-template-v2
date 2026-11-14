<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends BaseController
{
    public function __construct()
    {
        $this->model = Customer::class;
        $this->route = 'customers';
        $this->titlePage = 'Master Pelanggan Salon';
        $this->primaryKey = 'id';
        $this->table = 'customers';
        $this->searchColumn = ['customer_code', 'name', 'phone', 'email'];

        $this->form = [
            [
                'name' => 'customer_code',
                'label' => 'Kode Pelanggan',
                'placeholder' => 'Kosongkan untuk otomatis (e.g. CUST-001)',
                'type' => 'text',
                'col' => 'col-md-12',
                'required' => false,
                'unique' => 'customers,customer_code',
            ],
            [
                'name' => 'name',
                'label' => 'Nama Pelanggan',
                'placeholder' => 'Masukkan nama lengkap pelanggan',
                'type' => 'text',
                'col' => 'col-md-12',
                'required' => true,
            ],
            [
                'name' => 'phone',
                'label' => 'Nomor Telepon / WhatsApp',
                'placeholder' => '0812xxxxxxxx',
                'type' => 'text',
                'col' => 'col-md-12',
                'required' => true,
                'unique' => 'customers,phone',
            ],
            [
                'name' => 'email',
                'label' => 'Email (Opsional)',
                'placeholder' => 'pelanggan@email.com',
                'type' => 'email',
                'col' => 'col-md-12',
                'required' => false,
            ],
            [
                'name' => 'birth_date',
                'label' => 'Tanggal Lahir',
                'type' => 'date',
                'col' => 'col-md-6',
                'required' => false,
            ],
            [
                'name' => 'gender',
                'label' => 'Jenis Kelamin',
                'placeholder' => '-- Pilih --',
                'type' => 'select',
                'col' => 'col-md-6',
                'required' => false,
                'rule_type' => 'string',
                'options' => [
                    ['value' => 'P', 'label' => 'Perempuan'],
                    ['value' => 'L', 'label' => 'Laki-laki'],
                ],
            ],
            [
                'name' => 'address',
                'label' => 'Alamat',
                'placeholder' => 'Alamat pelanggan',
                'type' => 'textarea',
                'col' => 'col-md-12',
                'required' => false,
            ],
            [
                'name' => 'notes',
                'label' => 'Catatan Preferensi / Alergi Produk',
                'placeholder' => 'Contoh: Kulit kepala sensitif, alergi amonia',
                'type' => 'textarea',
                'col' => 'col-md-12',
                'required' => false,
            ],
            [
                'name' => 'is_active',
                'label' => 'Status',
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
            ['label' => 'Kode', 'field' => 'customer_code', 'type' => 'text'],
            ['label' => 'Nama', 'field' => 'name', 'type' => 'text'],
            ['label' => 'Telepon', 'field' => 'phone', 'type' => 'text'],
            ['label' => 'L/P', 'field' => 'gender', 'type' => 'text'],
            ['label' => 'Catatan/Alergi', 'field' => 'notes', 'type' => 'text'],
            ['label' => 'Status', 'field' => 'status_label', 'type' => 'badge'],
        ];
    }

    public function index(Request $request): View
    {
        $view = parent::index($request);
        $data = $view->getData();
        $data['items']->getCollection()->transform(function ($item) {
            $item->status_label = $item->is_active ? 'Aktif' : 'Nonaktif';
            return $item;
        });

        return $view;
    }

    protected function beforeSave(array $data, $record = null): array
    {
        if (empty($data['customer_code'])) {
            $next = ((int) Customer::withTrashed()->max('id')) + 1;
            $data['customer_code'] = sprintf('CUST-%04d', $next);
        }
        $data['is_active'] = $data['is_active'] ?? 1;

        return $data;
    }

    protected function afterSave(array $res): void
    {
        AuditLog::record('customer.create', 'customer', "Pelanggan baru ditambahkan: {$res['name']} ({$res['customer_code']})");
    }
}
