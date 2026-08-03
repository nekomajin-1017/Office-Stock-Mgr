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
        // 権限確認後、検索条件で仕入伝票を絞り込み、関連情報と一緒にページ表示する。
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
        // 登録権限を確認し、有効な仕入先・商品を仕入伝票登録画面へ渡す。
        $this->authorize('create', Purchase::class);

        return view('purchases.form', $this->formData());
    }

    public function store(StorePurchaseRequest $request): RedirectResponse
    {
        // 入力明細の金額を計算し、仕入伝票と明細を同一トランザクションで下書き登録する。
        $validatedPurchaseData = $request->validated();

        $purchase = DB::transaction(function () use ($validatedPurchaseData): Purchase {
            $subtotal = 0;
            $purchaseItems = collect($validatedPurchaseData['items'])->map(function (array $purchaseItem) use (&$subtotal): array {
                $lineSubtotal = $purchaseItem['quantity'] * (float) $purchaseItem['unit_price'];
                $subtotal += $lineSubtotal;

                return [...$purchaseItem, 'subtotal' => $lineSubtotal, 'tax_amount' => 0];
            });
            $purchase = Purchase::create([
                'purchase_number' => $this->generateUniquePurchaseNumber(),
                'supplier_id' => $validatedPurchaseData['supplier_id'],
                'purchase_date' => $validatedPurchaseData['purchase_date'],
                'status' => Purchase::STATUS_DRAFT,
                'subtotal' => $subtotal,
                'tax_amount' => 0,
                'total_amount' => $subtotal,
                'created_by' => auth()->id(),
            ]);
            $purchase->items()->createMany($purchaseItems->all());

            return $purchase;
        });

        return to_route('purchases.show', $purchase)->with('status', '仕入伝票を下書きとして登録しました。');
    }

    public function edit(Purchase $purchase): View
    {
        // 下書きの更新権限を確認し、既存明細と選択肢を編集画面へ渡す。
        $this->authorize('update', $purchase);

        return view('purchases.form', $this->formData() + ['purchase' => $purchase->load('items')]);
    }

    public function update(UpdatePurchaseRequest $request, Purchase $purchase): RedirectResponse
    {
        // 合計を再計算し、伝票更新と明細の入れ替えを同一トランザクションで実行する。
        $validatedPurchaseData = $request->validated();
        DB::transaction(function () use ($purchase, $validatedPurchaseData) {
            $subtotal = collect($validatedPurchaseData['items'])->sum(fn ($purchaseItem) => $purchaseItem['quantity'] * $purchaseItem['unit_price']);
            $purchase->update(['supplier_id' => $validatedPurchaseData['supplier_id'], 'purchase_date' => $validatedPurchaseData['purchase_date'], 'subtotal' => $subtotal, 'total_amount' => $subtotal]);
            $purchase->items()->delete();
            $purchase->items()->createMany(collect($validatedPurchaseData['items'])->map(fn ($purchaseItem) => [...$purchaseItem, 'subtotal' => $purchaseItem['quantity'] * $purchaseItem['unit_price'], 'tax_amount' => 0])->all());
        });

        return to_route('purchases.show', $purchase)->with('status', '下書き伝票を更新しました。');
    }

    public function destroy(Purchase $purchase): RedirectResponse
    {
        // 下書きの削除権限を確認して伝票を削除し、一覧画面へ戻す。
        $this->authorize('delete', $purchase);
        $purchase->delete();

        return to_route('purchases.index')->with('status', '下書き伝票を削除しました。');
    }

    public function show(Purchase $purchase): View
    {
        // 閲覧権限を確認し、仕入先・担当者・明細を読み込んで詳細画面へ渡す。
        $this->authorize('view', $purchase);

        return view('purchases.show', [
            'purchase' => $purchase->load(['supplier', 'creator', 'confirmer', 'canceller', 'items.product']),
        ]);
    }

    private function formData(): array
    {
        // 仕入伝票フォームで使用する有効な仕入先と商品を取得する。
        return [
            'suppliers' => Supplier::active()->orderBy('name')->get(),
            'products' => Product::active()->orderBy('code')->get(),
        ];
    }

    private function generateUniquePurchaseNumber(): string
    {
        // 日付とランダム文字列から、重複しない仕入伝票番号を生成する。
        do {
            $purchaseNumber = 'PUR-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
        } while (Purchase::query()->where('purchase_number', $purchaseNumber)->exists());

        return $purchaseNumber;
    }

    public function confirm(Purchase $purchase): RedirectResponse
    {
        // 確定権限と現在状態を確認し、在庫加算・移動履歴・確定情報を一括更新する。
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
        // 管理者権限と取消理由を確認し、在庫を逆仕訳して伝票を取消済みにする。
        $this->authorize('cancel', $purchase);
        $validatedCancellationData = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($purchase, $validatedCancellationData): void {
            $purchase = $this->lockedConfirmedPurchase($purchase);
            $this->reversePurchaseStock($purchase, 'purchase_cancel');
            $purchase->update([
                'status' => Purchase::STATUS_CANCELLED,
                'cancellation_reason' => $validatedCancellationData['reason'],
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id(),
            ]);
        }, attempts: 3);

        return to_route('purchases.show', $purchase)->with('status', '仕入伝票を取消しました。');
    }

    public function correct(Purchase $purchase): RedirectResponse
    {
        // 管理者権限を確認し、在庫を逆仕訳して確定済み伝票を編集可能な下書きへ戻す。
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
        // 対象伝票を行ロック付きで再取得し、確定済みであることを保証する。
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
        // 商品ごとに在庫数量と平均原価を戻し、取消・訂正の在庫移動履歴を記録する。
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
                return $item->quantity * $this->convertToCents($item->unit_price);
            });
            $remainingCost = max(
                0,
                ($stock->quantity * $this->convertToCents($stock->average_cost)) - $purchaseCost,
            );
            $remainingAverageCost = $remainingQuantity === 0
                ? 0
                : $this->calculateRoundedUnitAmount($remainingCost, $remainingQuantity);

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
        // 仕入数量を在庫へ加算し、移動平均原価と仕入の在庫移動履歴を更新する。
        $purchaseQuantity = $items->sum('quantity');
        $purchaseCost = $items->sum(function ($item): int {
            return $item->quantity * $this->convertToCents($item->unit_price);
        });
        $currentCost = $stock->quantity * $this->convertToCents($stock->average_cost);
        $newQuantity = $stock->quantity + $purchaseQuantity;
        $newAverageCost = $this->calculateRoundedUnitAmount($currentCost + $purchaseCost, $newQuantity);

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

    private function convertToCents(string|float|int $monetaryAmount): int
    {
        // 金額を浮動小数点誤差なく計算するため、円単位の値を整数の銭へ変換する。
        return (int) round((float) $monetaryAmount * self::CURRENCY_FACTOR, 0, PHP_ROUND_HALF_UP);
    }

    private function calculateRoundedUnitAmount(int $totalAmountInCents, int $quantity): int
    {
        // 整数除算を使い、数量あたりの金額を四捨五入する。
        return intdiv($totalAmountInCents + intdiv($quantity, 2), $quantity);
    }
}
