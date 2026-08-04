<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Http\Requests\UpdateSaleRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Services\SaleDraftService;
use App\Services\SaleInventoryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SaleController extends Controller
{
    private const SALES_PER_PAGE = 10;

    public function __construct(
        private readonly SaleDraftService $draftService,
        private readonly SaleInventoryService $inventoryService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Sale::class);
        $sales = Sale::query()->with(['customer', 'creator'])
            ->when($request->filled('sale_number'), fn (Builder $query) => $query->where('sale_number', 'like', '%'.$request->string('sale_number').'%'))
            ->when($request->filled('customer_id'), fn (Builder $query) => $query->where('customer_id', $request->integer('customer_id')))
            ->when($request->filled('date_from'), fn (Builder $query) => $query->whereDate('sale_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn (Builder $query) => $query->whereDate('sale_date', '<=', $request->date('date_to')))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')))
            ->latest('sale_date')
            ->paginate(self::SALES_PER_PAGE)
            ->withQueryString();

        return view('sales.index', [
            'sales' => $sales,
            'customers' => Customer::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Sale::class);

        return view('sales.form', $this->formData());
    }

    public function store(StoreSaleRequest $request): RedirectResponse
    {
        $sale = $this->draftService->create($request->validated());

        return to_route('sales.show', $sale)->with('status', '販売伝票を下書き登録しました。');
    }

    public function show(Sale $sale): View
    {
        $this->authorize('view', $sale);

        return view('sales.show', [
            'sale' => $sale->load(['customer', 'creator', 'confirmer', 'canceller', 'items.product']),
        ]);
    }

    public function deliveryNote(Sale $sale): Response
    {
        $this->authorize('view', $sale);
        abort_unless($sale->isConfirmed(), 403, '確定済みの販売伝票のみ納品書を出力できます。');
        $sale->load(['customer', 'items.product']);

        return Pdf::loadView('sales.delivery-note', ['sale' => $sale, 'issuedAt' => now()])
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
        $this->draftService->update($sale, $request->validated());

        return to_route('sales.show', $sale)->with('status', '下書き伝票を更新しました。');
    }

    public function destroy(Sale $sale): RedirectResponse
    {
        $this->authorize('delete', $sale);
        $sale->delete();

        return to_route('sales.index')->with('status', '下書き伝票を削除しました。');
    }

    public function confirm(Sale $sale): RedirectResponse
    {
        $this->authorize('confirm', $sale);
        $this->inventoryService->confirm($sale);

        return to_route('sales.show', $sale)->with('status', '販売伝票を確定し、在庫を更新しました。');
    }

    public function cancel(Request $request, Sale $sale): RedirectResponse
    {
        $this->authorize('cancel', $sale);
        $validated = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $this->inventoryService->cancel($sale, $validated['reason']);

        return to_route('sales.show', $sale)->with('status', '販売伝票を取消しました。');
    }

    public function correct(Sale $sale): RedirectResponse
    {
        $this->authorize('correct', $sale);
        $this->inventoryService->correct($sale);

        return to_route('sales.edit', $sale)
            ->with('status', '確定を解除して在庫を戻しました。内容を訂正後、再度確定してください。');
    }

    private function formData(): array
    {
        return [
            'customers' => Customer::active()->orderBy('name')->get(),
            'products' => Product::active()->with('stock')->orderBy('code')->get(),
        ];
    }
}
