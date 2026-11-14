<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends BaseController
{
    public function __construct()
    {
        $this->model = Supplier::class;
        $this->route = 'suppliers';
        $this->titlePage = 'Master Supplier';
        $this->primaryKey = 'id';
        $this->table = 'suppliers';
        $this->searchColumn = ['supplier_code', 'name', 'phone', 'email'];

        $this->form = [
            [
                'name' => 'supplier_code',
                'label' => 'Kode Supplier',
                'placeholder' => 'Kosongkan untuk otomatis (e.g. SUP-001)',
                'type' => 'text',
                'col' => 'col-md-12',
                'required' => false,
                'unique' => 'suppliers,supplier_code',
            ],
            [
                'name' => 'name',
                'label' => 'Nama Supplier / Distributor',
                'placeholder' => 'PT. Kosmetik Nusantara',
                'type' => 'text',
                'col' => 'col-md-12',
                'required' => true,
            ],
            [
                'name' => 'phone',
                'label' => 'Nomor Telepon',
                'placeholder' => '021-xxxxxxx',
                'type' => 'text',
                'col' => 'col-md-6',
                'required' => false,
            ],
            [
                'name' => 'email',
                'label' => 'Email',
                'placeholder' => 'sales@supplier.co.id',
                'type' => 'email',
                'col' => 'col-md-6',
                'required' => false,
            ],
            [
                'name' => 'address',
                'label' => 'Alamat',
                'placeholder' => 'Alamat lengkap supplier',
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
            ['label' => 'Kode', 'field' => 'supplier_code', 'type' => 'text'],
            ['label' => 'Nama Supplier', 'field' => 'name', 'type' => 'text'],
            ['label' => 'Telepon', 'field' => 'phone', 'type' => 'text'],
            ['label' => 'Email', 'field' => 'email', 'type' => 'text'],
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
        if (empty($data['supplier_code'])) {
            $next = ((int) Supplier::withTrashed()->max('id')) + 1;
            $data['supplier_code'] = sprintf('SUP-%03d', $next);
        }
        $data['is_active'] = $data['is_active'] ?? 1;
        return $data;
    }
}
