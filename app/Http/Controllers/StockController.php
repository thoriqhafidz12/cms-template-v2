<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockDetail;
use App\Models\Supplier;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockController extends Controller
{
    public function __construct(
        protected StockService $stockService
    ) {}

    public function index(Request $request): View
    {
        $productId = $request->get('product_id');
        $movementType = $request->get('movement_type');

        $products = Product::with(['unit', 'category'])->where('is_active', 1)->orderBy('name')->get();
        $suppliers = Supplier::where('is_active', 1)->orderBy('name')->get();

        $lowStockProducts = Product::with('unit')
            ->where('is_active', 1)
            ->whereColumn('current_stock', '<=', 'min_stock')
            ->orderBy('current_stock')
            ->get();

        $ledgerQuery = StockDetail::with(['header.supplier', 'header.creator', 'product.unit'])
            ->orderByDesc('id');

        if ($productId) {
            $ledgerQuery->where('product_id', $productId);
        }

        if ($movementType) {
            $ledgerQuery->whereHas('header', fn ($q) => $q->where('movement_type', $movementType));
        }

        $ledgers = $ledgerQuery->paginate(15)->withQueryString();

        return view('inventory.stocks', compact(
            'products',
            'suppliers',
            'lowStockProducts',
            'ledgers',
            'productId',
            'movementType'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'movement_action' => ['required', 'in:receive,usage,adjustment_in,adjustment_out'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $action = $validated['movement_action'];
        $qty = (float) $validated['quantity'];

        $movementType = match ($action) {
            'receive' => 'receive',
            'usage' => 'usage',
            default => 'adjustment',
        };

        $qtyIn = in_array($action, ['receive', 'adjustment_in'], true) ? $qty : 0.0;
        $qtyOut = in_array($action, ['usage', 'adjustment_out'], true) ? $qty : 0.0;

        $header = $this->stockService->recordMovement(
            $movementType,
            [[
                'product_id' => (int) $validated['product_id'],
                'qty_in' => $qtyIn,
                'qty_out' => $qtyOut,
                'unit_cost' => isset($validated['unit_cost']) ? (float) $validated['unit_cost'] : null,
                'notes' => $validated['reason'],
            ]],
            $validated['reason'],
            !empty($validated['supplier_id']) ? (int) $validated['supplier_id'] : null
        );

        return redirect()
            ->route('stocks.index', ['product_id' => $validated['product_id']])
            ->with('success', "Mutasi stok berhasil diposting dengan nomor dokumen {$header->document_number}.");
    }
}
