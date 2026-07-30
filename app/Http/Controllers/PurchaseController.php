<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseRequest;
use App\Http\Requests\UpdatePurchaseRequest;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PurchaseController extends Controller
{
    private const CURRENCY_FACTOR = 100;

    private const PURCHASES_PER_PAGE = 10;

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
        $data = $request->validated();

        $purchase = DB::transaction(function () use ($data): Purchase {
            $subtotal = 0;
            $items = collect($data['items'])->map(function (array $item) use (&$subtotal): array {
                $lineSubtotal = $item['quantity'] * (float) $item['unit_price'];
                $subtotal += $lineSubtotal;

                return [...$item, 'subtotal' => $lineSubtotal, 'tax_amount' => 0];
            });
            $purchase = Purchase::create([
                'purchase_number' => $this->purchaseNumber(),
                'supplier_id' => $data['supplier_id'],
                'purchase_date' => $data['purchase_date'],
                'status' => Purchase::STATUS_DRAFT,
                'subtotal' => $subtotal,
                'tax_amount' => 0,
                'total_amount' => $subtotal,
                'created_by' => auth()->id(),
            ]);
            $purchase->items()->createMany($items->all());

            return $purchase;
        });

        return to_route('purchases.show', $purchase)->with('status', '仕入伝票を下書きとして登録しました。');
    }

    public function edit(Purchase $purchase): View
    {
        $this->authorize('update', $purchase);

        return view('purchases.form', $this->formData() + ['purchase' => $purchase->load('items')]);
    }

    public function update(UpdatePurchaseRequest $request, Purchase $purchase): RedirectResponse
    {
        $data = $request->validated();
        DB::transaction(function () use ($purchase, $data) {
            $subtotal = collect($data['items'])->sum(fn ($item) => $item['quantity'] * $item['unit_price']);
            $purchase->update(['supplier_id' => $data['supplier_id'], 'purchase_date' => $data['purchase_date'], 'subtotal' => $subtotal, 'total_amount' => $subtotal]);
            $purchase->items()->delete();
            $purchase->items()->createMany(collect($data['items'])->map(fn ($item) => [...$item, 'subtotal' => $item['quantity'] * $item['unit_price'], 'tax_amount' => 0])->all());
        });

        return to_route('purchases.show', $purchase)->with('status', '下書き伝票を更新しました。');
    }

    public function destroy(Purchase $purchase): RedirectResponse
    {
        $this->authorize('delete', $purchase);
        $purchase->delete();

        return to_route('purchases.index')->with('status', '下書き伝票を削除しました。');
    }

    public function show(Purchase $purchase): View
    {
        $this->authorize('view', $purchase);

        return view('purchases.show', [
            'purchase' => $purchase->load(['supplier', 'creator', 'confirmer', 'canceller', 'items.product']),
        ]);
    }

    private function formData(): array
    {
        return [
            'suppliers' => Supplier::active()->orderBy('name')->get(),
            'products' => Product::active()->orderBy('code')->get(),
        ];
    }

    private function purchaseNumber(): string
    {
        do {
            $number = 'PUR-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
        } while (Purchase::query()->where('purchase_number', $number)->exists());

        return $number;
    }

    public function confirm(Purchase $purchase): RedirectResponse
    {
        $this->authorize('confirm', $purchase);

        DB::transaction(function () use ($purchase): void {
            $purchase = Purchase::query()
                ->lockForUpdate()
                ->with('items')
                ->findOrFail($purchase->id);

            if (! $purchase->isDraft()) {
                throw ValidationException::withMessages([
                    'purchase' => '確定済みの仕入伝票は再度確定できません。',
                ]);
            }

            $items = $purchase->items->groupBy('product_id')->sortKeys();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'purchase' => '明細がない仕入伝票は確定できません。',
                ]);
            }

            $stocks = Stock::query()
                ->whereIn('product_id', $items->keys())
                ->orderBy('product_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('product_id');

            foreach ($items as $productId => $productItems) {
                $stock = $stocks->get($productId);

                if (! $stock) {
                    throw new RuntimeException('対象商品の在庫レコードが見つかりません。');
                }

                $this->applyPurchaseToStock($stock, $productItems, $purchase);
            }

            $purchase->update([
                'status' => Purchase::STATUS_CONFIRMED,
                'confirmed_at' => now(),
                'confirmed_by' => auth()->id(),
            ]);
        }, attempts: 3);

        return to_route('purchases.show', $purchase)->with('status', '仕入伝票を確定し、在庫を更新しました。');
    }

    public function cancel(Request $request, Purchase $purchase): RedirectResponse
    {
        $this->authorize('cancel', $purchase);
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($purchase, $data): void {
            $purchase = $this->lockedConfirmedPurchase($purchase);
            $this->reversePurchaseStock($purchase, 'purchase_cancel');
            $purchase->update([
                'status' => Purchase::STATUS_CANCELLED,
                'cancellation_reason' => $data['reason'],
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id(),
            ]);
        }, attempts: 3);

        return to_route('purchases.show', $purchase)->with('status', '仕入伝票を取消しました。');
    }

    public function correct(Purchase $purchase): RedirectResponse
    {
        $this->authorize('correct', $purchase);

        DB::transaction(function () use ($purchase): void {
            $purchase = $this->lockedConfirmedPurchase($purchase);
            $this->reversePurchaseStock($purchase, 'purchase_correction');
            $purchase->update([
                'status' => Purchase::STATUS_DRAFT,
                'confirmed_at' => null,
                'confirmed_by' => null,
            ]);
        }, attempts: 3);

        return to_route('purchases.edit', $purchase)
            ->with('status', '確定を解除して在庫を戻しました。内容を訂正後、再度確定してください。');
    }

    private function lockedConfirmedPurchase(Purchase $purchase): Purchase
    {
        $lockedPurchase = Purchase::query()
            ->lockForUpdate()
            ->with('items')
            ->findOrFail($purchase->id);

        if (! $lockedPurchase->isConfirmed()) {
            throw ValidationException::withMessages([
                'purchase' => '確定済み伝票のみ処理できます。',
            ]);
        }

        return $lockedPurchase;
    }

    private function reversePurchaseStock(Purchase $purchase, string $movementType): void
    {
        $items = $purchase->items->groupBy('product_id')->sortKeys();
        $stocks = Stock::query()
            ->whereIn('product_id', $items->keys())
            ->orderBy('product_id')
            ->lockForUpdate()
            ->get()
            ->keyBy('product_id');

        foreach ($items as $productId => $productItems) {
            $stock = $stocks->get($productId);
            $quantity = $productItems->sum('quantity');

            if (! $stock || $stock->quantity < $quantity) {
                throw ValidationException::withMessages([
                    'purchase' => '訂正・取消に必要な在庫が不足しています。',
                ]);
            }

            $remainingQuantity = $stock->quantity - $quantity;
            $purchaseCost = $productItems->sum(function ($item): int {
                return $item->quantity * $this->toCents($item->unit_price);
            });
            $remainingCost = max(
                0,
                ($stock->quantity * $this->toCents($stock->average_cost)) - $purchaseCost,
            );
            $remainingAverageCost = $remainingQuantity === 0
                ? 0
                : $this->roundHalfUp($remainingCost, $remainingQuantity);

            $stock->update([
                'quantity' => $remainingQuantity,
                'average_cost' => number_format(
                    $remainingAverageCost / self::CURRENCY_FACTOR,
                    2,
                    '.',
                    '',
                ),
            ]);

            foreach ($productItems as $item) {
                StockMovement::create([
                    'product_id' => $item->product_id,
                    'movement_type' => $movementType,
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                    'quantity_change' => -$item->quantity,
                    'unit_cost' => $item->unit_price,
                    'occurred_at' => now(),
                    'created_by' => auth()->id(),
                ]);
            }
        }
    }

    private function applyPurchaseToStock(Stock $stock, Collection $items, Purchase $purchase): void
    {
        $purchaseQuantity = $items->sum('quantity');
        $purchaseCost = $items->sum(function ($item): int {
            return $item->quantity * $this->toCents($item->unit_price);
        });
        $currentCost = $stock->quantity * $this->toCents($stock->average_cost);
        $newQuantity = $stock->quantity + $purchaseQuantity;
        $newAverageCost = $this->roundHalfUp($currentCost + $purchaseCost, $newQuantity);

        $stock->update([
            'quantity' => $newQuantity,
            'average_cost' => number_format($newAverageCost / self::CURRENCY_FACTOR, 2, '.', ''),
        ]);

        foreach ($items as $item) {
            StockMovement::create([
                'product_id' => $stock->product_id,
                'movement_type' => 'purchase',
                'reference_type' => Purchase::class,
                'reference_id' => $purchase->id,
                'quantity_change' => $item->quantity,
                'unit_cost' => $item->unit_price,
                'occurred_at' => now(),
                'created_by' => auth()->id(),
            ]);
        }
    }

    private function toCents(string|float|int $amount): int
    {
        return (int) round((float) $amount * self::CURRENCY_FACTOR, 0, PHP_ROUND_HALF_UP);
    }

    private function roundHalfUp(int $amount, int $quantity): int
    {
        return intdiv($amount + intdiv($quantity, 2), $quantity);
    }
}
