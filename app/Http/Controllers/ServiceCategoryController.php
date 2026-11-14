<?php

namespace App\Http\Controllers;

use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceCategoryController extends BaseController
{
    public function __construct()
    {
        $this->model = ServiceCategory::class;
        $this->route = 'service-categories';
        $this->titlePage = 'Kategori Layanan Salon';
        $this->primaryKey = 'id';
        $this->table = 'service_categories';
        $this->searchColumn = ['name', 'description'];

        $this->form = [
            [
                'name' => 'name',
                'label' => 'Nama Kategori Layanan',
                'placeholder' => 'Contoh: Hair Treatment, Hair Coloring, Nail Art',
                'type' => 'text',
                'col' => 'col-md-12',
                'required' => true,
                'unique' => 'service_categories,name',
            ],
            [
                'name' => 'description',
                'label' => 'Deskripsi',
                'placeholder' => 'Keterangan kategori layanan',
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
            ['label' => 'Kategori', 'field' => 'name', 'type' => 'text'],
            ['label' => 'Deskripsi', 'field' => 'description', 'type' => 'text'],
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
        $data['is_active'] = $data['is_active'] ?? 1;
        return $data;
    }
}
