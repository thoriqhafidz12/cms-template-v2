<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends BaseController
{
    public function __construct()
    {
        $this->model = Service::class;
        $this->route = 'services';
        $this->titlePage = 'Daftar Layanan & Harga';
        $this->primaryKey = 'id';
        $this->table = 'services';
        $this->searchColumn = ['services.service_code', 'services.name'];

        $this->form = [
            [
                'name' => 'service_code',
                'label' => 'Kode Layanan',
                'placeholder' => 'Kosongkan untuk otomatis (e.g. SRV-001)',
                'type' => 'text',
                'col' => 'col-md-12',
                'required' => false,
                'unique' => 'services,service_code',
            ],
            [
                'name' => 'service_category_id',
                'label' => 'Kategori Layanan',
                'placeholder' => '-- Pilih Kategori --',
                'type' => 'select',
                'col' => 'col-md-12',
                'required' => true,
                'exists' => 'service_categories,id',
                'options' => [],
            ],
            [
                'name' => 'name',
                'label' => 'Nama Layanan',
                'placeholder' => 'Contoh: Haircut & Blow Dry',
                'type' => 'text',
                'col' => 'col-md-12',
                'required' => true,
            ],
            [
                'name' => 'price',
                'label' => 'Harga Jual (Rp)',
                'placeholder' => '150.000',
                'type' => 'angka',
                'col' => 'col-md-6',
                'required' => true,
            ],
            [
                'name' => 'duration_minutes',
                'label' => 'Durasi (Menit)',
                'placeholder' => '45',
                'type' => 'number',
                'col' => 'col-md-6',
                'required' => true,
            ],
            [
                'name' => 'commission_type',
                'label' => 'Tipe Komisi Stylist',
                'placeholder' => '-- Pilih Tipe Komisi --',
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
                'label' => 'Nilai Komisi (% / Rp)',
                'placeholder' => '15',
                'type' => 'number',
                'col' => 'col-md-6',
                'required' => true,
            ],
            [
                'name' => 'description',
                'label' => 'Deskripsi Layanan',
                'placeholder' => 'Rincian layanan',
                'type' => 'textarea',
                'col' => 'col-md-12',
                'required' => false,
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
            ['label' => 'Kode', 'field' => 'service_code', 'type' => 'text'],
            ['label' => 'Kategori', 'field' => 'category_name', 'type' => 'text'],
            ['label' => 'Nama Layanan', 'field' => 'name', 'type' => 'text'],
            ['label' => 'Durasi', 'field' => 'duration_label', 'type' => 'text'],
            ['label' => 'Harga', 'field' => 'price', 'type' => 'rupiah'],
            ['label' => 'Komisi', 'field' => 'commission_label', 'type' => 'text'],
            ['label' => 'Status', 'field' => 'status_label', 'type' => 'badge'],
        ];

        $this->extraViewData = [
            'service_category_ids' => fn () => ServiceCategory::where('is_active', 1)->orderBy('name')->get(),
        ];
    }

    public function index(Request $request): View
    {
        $search = $request->get('search');
        $editId = $request->get('edit');

        $query = Service::leftJoin('service_categories', 'services.service_category_id', '=', 'service_categories.id')
            ->select('services.*', 'service_categories.name as category_name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('services.name', 'like', "%{$search}%")
                    ->orWhere('services.service_code', 'like', "%{$search}%")
                    ->orWhere('service_categories.name', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('services.id', 'desc')
            ->paginate(10)
            ->withQueryString();

        $items->getCollection()->transform(function ($item) {
            $item->duration_label = $item->duration_minutes . ' mnt';
            $item->commission_label = $item->commission_type === 'percentage'
                ? ((float) $item->commission_value . '%')
                : ('Rp ' . number_format((float) $item->commission_value, 0, ',', '.'));
            $item->status_label = $item->is_active ? 'Aktif' : 'Nonaktif';
            return $item;
        });

        $editData = $editId ? Service::find($editId) : null;

        $extra = [];
        foreach ($this->extraViewData as $key => $resolver) {
            $extra[$key] = is_callable($resolver) ? $resolver() : $resolver;
        }

        return view('master', array_merge([
            'items' => $items,
            'search' => $search,
            'editData' => $editData,
            'form' => $this->form,
            'route' => $this->route,
            'primaryKey' => $this->primaryKey,
            'titlePage' => $this->titlePage,
            'grid' => $this->grid,
        ], $extra));
    }

    protected function beforeSave(array $data, $record = null): array
    {
        if (empty($data['service_code'])) {
            $next = ((int) Service::withTrashed()->max('id')) + 1;
            $data['service_code'] = sprintf('SRV-%03d', $next);
        }
        $data['is_active'] = $data['is_active'] ?? 1;

        return $data;
    }

    protected function beforeUpdate(array $data, $record): array
    {
        AuditLog::record(
            'service.update',
            'service',
            "Update layanan #{$record}: {$data['name']} (Harga: Rp " . number_format((float) $data['price'], 0, ',', '.') . ')'
        );

        return $data;
    }
}
