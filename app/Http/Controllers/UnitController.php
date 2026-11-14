<?php

namespace App\Http\Controllers;

use App\Models\Unit;

class UnitController extends BaseController
{
    public function __construct()
    {
        $this->model = Unit::class;
        $this->route = 'units';
        $this->titlePage = 'Satuan Produk';
        $this->primaryKey = 'id';
        $this->table = 'units';
        $this->searchColumn = ['name', 'symbol'];

        $this->form = [
            [
                'name' => 'name',
                'label' => 'Nama Satuan',
                'placeholder' => 'Contoh: Botol, Tube, Pcs, Box',
                'type' => 'text',
                'col' => 'col-md-12',
                'required' => true,
            ],
            [
                'name' => 'symbol',
                'label' => 'Singkatan / Simbol',
                'placeholder' => 'btl, tube, pcs',
                'type' => 'text',
                'col' => 'col-md-12',
                'required' => true,
            ],
        ];

        $this->grid = [
            ['label' => 'Nama Satuan', 'field' => 'name', 'type' => 'text'],
            ['label' => 'Simbol', 'field' => 'symbol', 'type' => 'badge'],
        ];
    }
}
