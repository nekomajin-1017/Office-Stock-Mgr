<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Http\Requests\UpdateSaleRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\StockMovement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class SaleController extends Controller
{
    private const SALES_PER_PAGE = 10;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Sale::class);
        $sales = Sale::query()->with(['customer', 'creator'])
            ->when($request->filled('sale_number'), fn (Builder $q) => $q->where('sale_number', 'like', '%'.$request->string('sale_number').'%'))
            ->when($request->filled('customer_id'), fn (Builder $q) => $q->where('customer_id', $request->integer('customer_id')))
            ->when($request->filled('date_from'), fn (Builder $q) => $q->whereDate('sale_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn (Builder $q) => $q->whereDate('sale_date', '<=', $request->date('date_to')))
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->status))
            ->latest('sale_date')->paginate(self::SALES_PER_PAGE)->withQueryString();

        return view('sales.index', ['sales' => $sales, 'customers' => Customer::query()->orderBy('name')->get()]);
    }

    public function create(): View
    {
        $this->authorize('create', Sale::class);

        return view('sales.form', $this->formData());
    }

    public function store(StoreSaleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $this->ensureSufficientStock($data['items']);

        $sale = DB::transaction(function () use ($data): Sale {
            $items = $this->saleItems($data['items']);
            $subtotal = $items->sum('subtotal');
            $sale = Sale::create(['sale_number' => 'SAL-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)), 'customer_id' => $data['customer_id'], 'sale_date' => $data['sale_date'], 'status' => Sale::STATUS_DRAFT, 'subtotal' => $subtotal, 'tax_amount' => 0, 'total_amount' => $subtotal, 'created_by' => auth()->id()]);
            $sale->items()->createMany($items->all());

            return $sale;
        });

        return to_route('sales.show', $sale)->with('status', '販売伝票を下書き登録しました。');
    }

    public function show(Sale $sale): View
    {
        $this->authorize('view', $sale);

        return view('sales.show', ['sale' => $sale->load(['customer', 'creator', 'confirmer', 'items.product'])]);
    }

    public function deliveryNote(Sale $sale): Response
    {
        $this->authorize('view', $sale);

        abort_unless($sale->isConfirmed(), 403, '確定済みの販売伝票のみ納品書を出力できます。');

        $sale->load(['customer', 'items.product']);

        return Pdf::loadView('sales.delivery-note', [
            'sale' => $sale,
            'issuedAt' => now(),
        ])
            ->setPaper('a4', 'portrait')
            ->download("delivery-note-{$sale->sale_number}.pdf");
    }

    public function edit(Sale $sale): View
    {
        $this->authorize('update', $sale);

        return view('sales.form', $this->formData() + ['sale' => $sale->load('items')]);
    }

    public function update(UpdateSaleRequest $request, Sale $sale): RedirectResponse
    {
        $data = $request->validated();
        $this->ensureSufficientStock($data['items']);

        DB::transaction(function () use ($sale, $data): void {
            $items = $this->saleItems($data['items']);
            $subtotal = $items->sum('subtotal');

            $sale->update([
                'customer_id' => $data['customer_id'],
                'sale_date' => $data['sale_date'],
                'subtotal' => $subtotal,
                'total_amount' => $subtotal,
            ]);
            $sale->items()->delete();
            $sale->items()->createMany($items->all());
        });

        return to_route('sales.show', $sale)->with('status', '下書き伝票を更新しました。');
    }

    public function destroy(Sale $sale): RedirectResponse
    {
        $this->authorize('delete', $sale);

        DB::transaction(function () use ($sale): void {
            $sale->delete();
        });

        return to_route('sales.index')->with('status', '下書き伝票を削除しました。');
    }

    private function formData(): array
    {
        return [
            'customers' => Customer::active()->orderBy('name')->get(),
            'products' => Product::active()->with('stock')->orderBy('code')->get(),
        ];
    }

    private function ensureSufficientStock(array $items): void
    {
        $products = Product::query()
            ->with('stock')
            ->whereIn('id', collect($items)->pluck('product_id'))
            ->get()
            ->keyBy('id');

        foreach ($items as $index => $item) {
            $stock = $products[$item['product_id']]->stock;

            if (! $stock || $stock->quantity < $item['quantity']) {
                throw ValidationException::withMessages([
                    "items.$index.quantity" => '在庫数が不足しています。',
                ]);
            }
        }
    }

    private function saleItems(array $items)
    {
        return collect($items)->map(fn (array $item): array => [
            ...$item,
            'cost_unit_price' => 0,
            'subtotal' => $item['quantity'] * $item['unit_price'],
            'cost_amount' => 0,
            'tax_amount' => 0,
        ]);
    }

    public function confirm(Sale $sale): RedirectResponse
    {
        $this->authorize('confirm', $sale);
        DB::transaction(function () use ($sale): void {
            $sale = Sale::query()->lockForUpdate()->with('items')->findOrFail($sale->id);
            if (! $sale->isDraft()) {
                throw ValidationException::withMessages(['sale' => '確定済みの販売伝票は再度確定できません。']);
            }
            $stocks = Stock::query()->whereIn('product_id', $sale->items->pluck('product_id'))->lockForUpdate()->get()->keyBy('product_id');
            foreach ($sale->items as $item) {
                $stock = $stocks->get($item->product_id);
                if (! $stock || $stock->quantity < $item->quantity) {
                    throw ValidationException::withMessages(['sale' => '在庫数が不足しています。']);
                } $stock->decrement('quantity', $item->quantity);
                StockMovement::create(['product_id' => $item->product_id, 'movement_type' => 'sale', 'reference_type' => Sale::class, 'reference_id' => $sale->id, 'quantity_change' => -$item->quantity, 'unit_cost' => $item->cost_unit_price, 'occurred_at' => now(), 'created_by' => auth()->id()]);
            }
            $sale->update(['status' => Sale::STATUS_CONFIRMED, 'confirmed_at' => now(), 'confirmed_by' => auth()->id()]);
        });

        return to_route('sales.show', $sale)->with('status', '販売伝票を確定し、在庫を更新しました。');
    }

    public function cancel(Request $request, Sale $sale): RedirectResponse
    {
        $this->authorize('cancel', $sale);
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        DB::transaction(function () use ($sale, $data) {
            $sale = Sale::query()->lockForUpdate()->with('items')->findOrFail($sale->id);
            if (! $sale->isConfirmed()) {
                throw ValidationException::withMessages(['sale' => '確定済み伝票のみ取消できます。']);
            } foreach ($sale->items as $item) {
                $stock = Stock::query()->where('product_id', $item->product_id)->lockForUpdate()->firstOrFail();
                $stock->increment('quantity', $item->quantity);
                StockMovement::create(['product_id' => $item->product_id, 'movement_type' => 'sale_cancel', 'reference_type' => Sale::class, 'reference_id' => $sale->id, 'quantity_change' => $item->quantity, 'unit_cost' => $item->cost_unit_price, 'occurred_at' => now(), 'created_by' => auth()->id()]);
            }$sale->update(['status' => Sale::STATUS_CANCELLED, 'cancellation_reason' => $data['reason'], 'cancelled_at' => now(), 'cancelled_by' => auth()->id()]);
        });

        return to_route('sales.show', $sale)->with('status', '販売伝票を取消しました。');
    }
}
