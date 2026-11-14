<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends BaseController
{
    public function __construct()
    {
        $this->model = Product::class;
        $this->route = 'products';
        $this->titlePage = 'Master Produk & Persediaan';
        $this->primaryKey = 'id';
        $this->table = 'products';
        $this->searchColumn = ['products.sku', 'products.name', 'products.barcode'];

        $this->form = [
            [
                'name' => 'sku',
                'label' => 'Kode SKU',
                'placeholder' => 'Kosongkan untuk otomatis (e.g. PRD-001)',
                'type' => 'text',
                'col' => 'col-md-6',
                'required' => false,
                'unique' => 'products,sku',
            ],
            [
                'name' => 'barcode',
                'label' => 'Barcode (Opsional)',
                'placeholder' => '899xxxxxxxxxx',
                'type' => 'text',
                'col' => 'col-md-6',
                'required' => false,
            ],
            [
                'name' => 'name',
                'label' => 'Nama Produk',
                'placeholder' => 'Contoh: Keratin Argan Hair Serum 100ml',
                'type' => 'text',
                'col' => 'col-md-12',
                'required' => true,
            ],
            [
                'name' => 'product_category_id',
                'label' => 'Kategori Produk',
                'placeholder' => '-- Pilih Kategori --',
                'type' => 'select',
                'col' => 'col-md-6',
                'required' => true,
                'exists' => 'product_categories,id',
                'options' => [],
            ],
            [
                'name' => 'unit_id',
                'label' => 'Satuan',
                'placeholder' => '-- Pilih Satuan --',
                'type' => 'select',
                'col' => 'col-md-6',
                'required' => true,
                'exists' => 'units,id',
                'options' => [],
            ],
            [
                'name' => 'supplier_id',
                'label' => 'Supplier Default',
                'placeholder' => '-- Pilih Supplier --',
                'type' => 'select',
                'col' => 'col-md-12',
                'required' => false,
                'exists' => 'suppliers,id',
                'options' => [],
            ],
            [
                'name' => 'purchase_price',
                'label' => 'Harga Beli / HPP (Rp)',
                'placeholder' => '85.000',
                'type' => 'angka',
                'col' => 'col-md-6',
                'required' => true,
            ],
            [
                'name' => 'sell_price',
                'label' => 'Harga Jual (Rp)',
                'placeholder' => '135.000',
                'type' => 'angka',
                'col' => 'col-md-6',
                'required' => true,
            ],
            [
                'name' => 'min_stock',
                'label' => 'Batas Stok Minimum',
                'placeholder' => '5',
                'type' => 'number',
                'col' => 'col-md-6',
                'required' => true,
            ],
            [
                'name' => 'is_active',
                'label' => 'Status Aktif',
                'placeholder' => '-- Pilih Status --',
                'type' => 'select',
                'col' => 'col-md-6',
                'required' => false,
                'options' => [
                    ['value' => 1, 'label' => 'Aktif'],
                    ['value' => 0, 'label' => 'Nonaktif'],
                ],
            ],
        ];

        $this->grid = [
            ['label' => 'SKU', 'field' => 'sku', 'type' => 'text'],
            ['label' => 'Produk', 'field' => 'name', 'type' => 'text'],
            ['label' => 'Kategori', 'field' => 'category_name', 'type' => 'text'],
            ['label' => 'Harga Beli', 'field' => 'purchase_price', 'type' => 'rupiah'],
            ['label' => 'Harga Jual', 'field' => 'sell_price', 'type' => 'rupiah'],
            ['label' => 'Stok', 'field' => 'stock_label', 'type' => 'badge'],
            ['label' => 'Min', 'field' => 'min_stock', 'type' => 'text'],
        ];

        $this->extraViewData = [
            'product_category_ids' => fn () => ProductCategory::where('is_active', 1)->orderBy('name')->get(),
            'unit_ids' => fn () => Unit::orderBy('name')->get(),
            'supplier_ids' => fn () => Supplier::where('is_active', 1)->orderBy('name')->get(),
        ];
    }

    public function index(Request $request): View
    {
        $search = $request->get('search');
        $editId = $request->get('edit');

        $query = Product::leftJoin('product_categories', 'products.product_category_id', '=', 'product_categories.id')
            ->leftJoin('units', 'products.unit_id', '=', 'units.id')
            ->select('products.*', 'product_categories.name as category_name', 'units.symbol as unit_symbol');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                    ->orWhere('products.sku', 'like', "%{$search}%")
                    ->orWhere('products.barcode', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('products.id', 'desc')
            ->paginate(10)
            ->withQueryString();

        $items->getCollection()->transform(function ($item) {
            $qty = number_format((float) $item->current_stock, 0, ',', '.');
            $warn = ((float) $item->current_stock <= (float) $item->min_stock) ? ' (LOW)' : '';
            $item->stock_label = "{$qty} {$item->unit_symbol}{$warn}";
            return $item;
        });

        $editData = $editId ? Product::find($editId) : null;

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
        if (empty($data['sku'])) {
            $next = ((int) Product::withTrashed()->max('id')) + 1;
            $data['sku'] = sprintf('PRD-%03d', $next);
        }
        $data['is_active'] = $data['is_active'] ?? 1;
        $data['current_stock'] = 0;
        if (empty($data['supplier_id'])) {
            $data['supplier_id'] = null;
        }

        return $data;
    }

    protected function beforeUpdate(array $data, $record): array
    {
        if (empty($data['supplier_id'])) {
            $data['supplier_id'] = null;
        }
        AuditLog::record('product.update', 'inventory', "Update master produk #{$record}: {$data['name']}");

        return $data;
    }
}
