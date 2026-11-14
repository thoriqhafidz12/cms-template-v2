<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentMethodController extends BaseController
{
    public function __construct()
    {
        $this->model = PaymentMethod::class;
        $this->route = 'payment-methods';
        $this->titlePage = 'Metode Pembayaran POS';
        $this->primaryKey = 'id';
        $this->table = 'payment_methods';
        $this->searchColumn = ['code', 'name', 'type'];

        $this->form = [
            [
                'name' => 'code',
                'label' => 'Kode Metode',
                'placeholder' => 'CASH, QRIS, BCA, DEBIT',
                'type' => 'text',
                'col' => 'col-md-12',
                'required' => true,
                'unique' => 'payment_methods,code',
            ],
            [
                'name' => 'name',
                'label' => 'Nama Metode Pembayaran',
                'placeholder' => 'Contoh: Tunai, QRIS Statis, Transfer BCA',
                'type' => 'text',
                'col' => 'col-md-12',
                'required' => true,
            ],
            [
                'name' => 'type',
                'label' => 'Kategori Metode',
                'placeholder' => '-- Pilih Tipe --',
                'type' => 'select',
                'col' => 'col-md-12',
                'required' => true,
                'rule_type' => 'string',
                'options' => [
                    ['value' => 'cash', 'label' => 'Tunai (Cash)'],
                    ['value' => 'qris', 'label' => 'QRIS'],
                    ['value' => 'transfer', 'label' => 'Transfer Bank'],
                    ['value' => 'card', 'label' => 'Kartu Debit / Kredit (EDC)'],
                ],
            ],
            [
                'name' => 'account_number',
                'label' => 'Nomor Rekening / Info Merchant (Opsional)',
                'placeholder' => 'BCA 1234567890 a/n ' . (\App\Models\SalonSetting::current()->salon_name ?: 'E-SALON'),
                'type' => 'text',
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
            ['label' => 'Kode', 'field' => 'code', 'type' => 'text'],
            ['label' => 'Metode Pembayaran', 'field' => 'name', 'type' => 'text'],
            ['label' => 'Tipe', 'field' => 'type', 'type' => 'badge'],
            ['label' => 'Info Rekening/Merchant', 'field' => 'account_number', 'type' => 'text'],
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
        $data['code'] = strtoupper($data['code']);
        $data['is_active'] = $data['is_active'] ?? 1;
        return $data;
    }
}
