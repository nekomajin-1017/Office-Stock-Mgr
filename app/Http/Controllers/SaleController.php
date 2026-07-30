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
        // 権限確認後、検索条件で販売伝票を絞り込み、関連情報と一緒にページ表示する。
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
        // 登録権限を確認し、有効な顧客・商品を販売伝票登録画面へ渡す。
        $this->authorize('create', Sale::class);

        return view('sales.form', $this->formData());
    }

    public function store(StoreSaleRequest $request): RedirectResponse
    {
        // 入力時点の在庫を確認し、金額計算済みの販売伝票と明細を下書き登録する。
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
        // 閲覧権限を確認し、顧客・担当者・明細を読み込んで詳細画面へ渡す。
        $this->authorize('view', $sale);

        return view('sales.show', [
            'sale' => $sale->load(['customer', 'creator', 'confirmer', 'canceller', 'items.product']),
        ]);
    }

    public function deliveryNote(Sale $sale): Response
    {
        // 確定済み伝票であることを確認し、顧客・明細を使って納品書PDFを生成する。
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
        // 下書きの更新権限を確認し、既存明細と選択肢を編集画面へ渡す。
        $this->authorize('update', $sale);

        return view('sales.form', $this->formData() + ['sale' => $sale->load('items')]);
    }

    public function update(UpdateSaleRequest $request, Sale $sale): RedirectResponse
    {
        // 在庫確認と金額再計算後、伝票更新と明細の入れ替えを一括実行する。
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
        // 下書きの削除権限を確認して伝票を削除し、一覧画面へ戻す。
        $this->authorize('delete', $sale);

        DB::transaction(function () use ($sale): void {
            $sale->delete();
        });

        return to_route('sales.index')->with('status', '下書き伝票を削除しました。');
    }

    private function formData(): array
    {
        // 販売伝票フォームで使用する有効な顧客と在庫情報付き商品を取得する。
        return [
            'customers' => Customer::active()->orderBy('name')->get(),
            'products' => Product::active()->with('stock')->orderBy('code')->get(),
        ];
    }

    private function ensureSufficientStock(array $items): void
    {
        // 商品ごとの現在庫と入力数量を比較し、不足している明細へエラーを設定する。
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
        // 入力明細へ原価・小計・税額の初期値を付加し、保存可能な配列へ変換する。
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
        // 確定権限と現在状態を確認し、在庫減算・移動履歴・確定情報を一括更新する。
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
        // 管理者権限と取消理由を確認し、販売数量を在庫へ戻して伝票を取消済みにする。
        $this->authorize('cancel', $sale);
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($sale, $data): void {
            $sale = $this->lockedConfirmedSale($sale);
            $this->reverseSaleStock($sale, 'sale_cancel');
            $sale->update([
                'status' => Sale::STATUS_CANCELLED,
                'cancellation_reason' => $data['reason'],
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id(),
            ]);
        }, attempts: 3);

        return to_route('sales.show', $sale)->with('status', '販売伝票を取消しました。');
    }

    public function correct(Sale $sale): RedirectResponse
    {
        // 管理者権限を確認し、販売数量を在庫へ戻して確定済み伝票を下書きへ戻す。
        $this->authorize('correct', $sale);

        DB::transaction(function () use ($sale): void {
            $sale = $this->lockedConfirmedSale($sale);
            $this->reverseSaleStock($sale, 'sale_correction');
            $sale->update([
                'status' => Sale::STATUS_DRAFT,
                'confirmed_at' => null,
                'confirmed_by' => null,
            ]);
        }, attempts: 3);

        return to_route('sales.edit', $sale)
            ->with('status', '確定を解除して在庫を戻しました。内容を訂正後、再度確定してください。');
    }

    private function lockedConfirmedSale(Sale $sale): Sale
    {
        // 対象伝票を行ロック付きで再取得し、確定済みであることを保証する。
        $lockedSale = Sale::query()
            ->lockForUpdate()
            ->with('items')
            ->findOrFail($sale->id);

        if (! $lockedSale->isConfirmed()) {
            throw ValidationException::withMessages([
                'sale' => '確定済み伝票のみ処理できます。',
            ]);
        }

        return $lockedSale;
    }

    private function reverseSaleStock(Sale $sale, string $movementType): void
    {
        // 商品ごとに販売数量を在庫へ戻し、取消・訂正の在庫移動履歴を記録する。
        $items = $sale->items->groupBy('product_id')->sortKeys();
        $stocks = Stock::query()
            ->whereIn('product_id', $items->keys())
            ->orderBy('product_id')
            ->lockForUpdate()
            ->get()
            ->keyBy('product_id');

        foreach ($items as $productId => $saleItems) {
            $stock = $stocks->get($productId);

            if (! $stock) {
                throw ValidationException::withMessages([
                    'sale' => '対象商品の在庫レコードが見つかりません。',
                ]);
            }

            $stock->increment('quantity', $saleItems->sum('quantity'));

            foreach ($saleItems as $item) {
                StockMovement::create([
                    'product_id' => $item->product_id,
                    'movement_type' => $movementType,
                    'reference_type' => Sale::class,
                    'reference_id' => $sale->id,
                    'quantity_change' => $item->quantity,
                    'unit_cost' => $item->cost_unit_price,
                    'occurred_at' => now(),
                    'created_by' => auth()->id(),
                ]);
            }
        }
    }
}
