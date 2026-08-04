<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseRequest;
use App\Http\Requests\UpdatePurchaseRequest;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchaseDraftService;
use App\Services\PurchaseInventoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    private const PURCHASES_PER_PAGE = 10;

    public function __construct(
        private readonly PurchaseDraftService $draftService,
        private readonly PurchaseInventoryService $inventoryService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Purchase::class);

        $purchases = Purchase::query()
            ->with(['supplier', 'creator'])
            ->when($request->filled('purchase_number'), fn (Builder $query) => $query->where('purchase_number', 'like', '%'.$request->string('purchase_number')->toString().'%'))
            ->when($request->filled('supplier_id'), fn (Builder $query) => $query->where('supplier_id', $request->integer('supplier_id')))
            ->when($request->filled('date_from'), fn (Builder $query) => $query->whereDate('purchase_date', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn (Builder $query) => $query->whereDate('purchase_date', '<=', $request->date('date_to')))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')))
            ->latest('purchase_date')
            ->paginate(self::PURCHASES_PER_PAGE)
            ->withQueryString();

        return view('purchases.index', [
            'purchases' => $purchases,
            'suppliers' => Supplier::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Purchase::class);

        return view('purchases.form', $this->formData());
    }

    public function store(StorePurchaseRequest $request): RedirectResponse
    {
        $purchase = $this->draftService->create($request->validated());

        return to_route('purchases.show', $purchase)->with('status', '仕入伝票を下書きとして登録しました。');
    }

    public function show(Purchase $purchase): View
    {
        $this->authorize('view', $purchase);

        return view('purchases.show', [
            'purchase' => $purchase->load(['supplier', 'creator', 'confirmer', 'canceller', 'items.product']),
        ]);
    }

    public function edit(Purchase $purchase): View
    {
        $this->authorize('update', $purchase);

        return view('purchases.form', $this->formData() + ['purchase' => $purchase->load('items')]);
    }

    public function update(UpdatePurchaseRequest $request, Purchase $purchase): RedirectResponse
    {
        $this->draftService->update($purchase, $request->validated());

        return to_route('purchases.show', $purchase)->with('status', '下書き伝票を更新しました。');
    }

    public function destroy(Purchase $purchase): RedirectResponse
    {
        $this->authorize('delete', $purchase);
        $purchase->delete();

        return to_route('purchases.index')->with('status', '下書き伝票を削除しました。');
    }

    public function confirm(Purchase $purchase): RedirectResponse
    {
        $this->authorize('confirm', $purchase);
        $this->inventoryService->confirm($purchase);

        return to_route('purchases.show', $purchase)->with('status', '仕入伝票を確定し、在庫を更新しました。');
    }

    public function cancel(Request $request, Purchase $purchase): RedirectResponse
    {
        $this->authorize('cancel', $purchase);
        $validated = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $this->inventoryService->cancel($purchase, $validated['reason']);

        return to_route('purchases.show', $purchase)->with('status', '仕入伝票を取消しました。');
    }

    public function correct(Purchase $purchase): RedirectResponse
    {
        $this->authorize('correct', $purchase);
        $this->inventoryService->correct($purchase);

        return to_route('purchases.edit', $purchase)
            ->with('status', '確定を解除して在庫を戻しました。内容を訂正後、再度確定してください。');
    }

    private function formData(): array
    {
        return [
            'suppliers' => Supplier::active()->orderBy('name')->get(),
            'products' => Product::active()->orderBy('code')->get(),
        ];
    }
}
