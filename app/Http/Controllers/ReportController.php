<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    private const DEFAULT_RANKING_LIMIT = 10;

    private const MAX_RANKING_LIMIT = 100;

    public function index(Request $request): View
    {
        // 権限確認と検索条件の正規化後、各集計クエリを実行してレポート画面へ渡す。
        $this->authorize('viewAny', Product::class);

        $startDate = $request->string('from')->toString();
        $endDate = $request->string('to')->toString();
        $limit = min(
            max($request->integer('limit', self::DEFAULT_RANKING_LIMIT), 1),
            self::MAX_RANKING_LIMIT,
        );

        $salesTotals = $this->buildSalesTotalsQuery($startDate, $endDate);
        $purchaseSummary = $this->purchaseSummary($startDate, $endDate);
        $salesSummary = DB::query()
            ->fromSub(clone $salesTotals, 'sales_totals')
            ->selectRaw('COALESCE(SUM(sales_quantity), 0) as total_quantity')
            ->selectRaw('COALESCE(SUM(sales_amount), 0) as total_amount')
            ->first();

        // 商品別の確定済み販売数量から平均を算出する。販売実績がない商品は平均計算の対象外とする。
        $averageSalesQuantity = (float) (DB::query()
            ->fromSub($salesTotals, 'sales_totals')
            ->avg('sales_quantity') ?? 0);

        // 期間内の販売数量・販売金額を商品別に集計し、販売数量の上位から指定件数を取得する。
        $salesRanking = Product::query()
            ->active()
            ->joinSub(clone $salesTotals, 'sales_totals', function ($salesTotalsJoin): void {
                $salesTotalsJoin->on('products.id', '=', 'sales_totals.product_id');
            })
            ->select('products.*', 'sales_totals.sales_quantity', 'sales_totals.sales_amount')
            ->orderByDesc('sales_totals.sales_quantity')
            ->orderBy('products.code')
            ->limit($limit)
            ->get();

        return view('reports.index', [
            'unsoldProducts' => $this->unsoldProducts(),
            'latestPurchaseProducts' => $this->latestPurchaseProducts(),
            'shortageProducts' => $this->shortageProducts(),
            'salesRanking' => $salesRanking,
            'purchaseSummary' => $purchaseSummary,
            'salesSummary' => $salesSummary,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'limit' => $limit,
            'averageSalesQuantity' => $averageSalesQuantity,
        ]);
    }

    private function buildSalesTotalsQuery(string $startDate, string $endDate)
    {
        // 確定済み販売明細を対象期間で絞り込み、商品別の販売数量と販売金額を集計する。
        $query = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'confirmed');

        if ($startDate !== '') {
            $query->whereDate('sales.sale_date', '>=', $startDate);
        }

        if ($endDate !== '') {
            $query->whereDate('sales.sale_date', '<=', $endDate);
        }

        return $query
            ->select('sale_items.product_id')
            ->selectRaw('SUM(sale_items.quantity) as sales_quantity')
            ->selectRaw('SUM(sale_items.subtotal) as sales_amount')
            ->groupBy('sale_items.product_id');
    }

    private function purchaseSummary(string $startDate, string $endDate): object
    {
        $query = DB::table('purchase_items')
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->where('purchases.status', 'confirmed');

        if ($startDate !== '') {
            $query->whereDate('purchases.purchase_date', '>=', $startDate);
        }

        if ($endDate !== '') {
            $query->whereDate('purchases.purchase_date', '<=', $endDate);
        }

        return $query
            ->selectRaw('COALESCE(SUM(purchase_items.quantity), 0) as total_quantity')
            ->selectRaw('COALESCE(SUM(purchase_items.subtotal), 0) as total_amount')
            ->first();
    }

    private function unsoldProducts()
    {
        // NOT EXISTSで確定済み販売明細が存在しない有効商品だけを抽出する。
        // 下書き・取消済みの販売伝票は販売実績に含めない。
        return Product::query()
            ->active()
            ->whereNotExists(function ($confirmedSalesQuery): void {
                $confirmedSalesQuery->selectRaw('1')
                    ->from('sale_items')
                    ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                    ->whereColumn('sale_items.product_id', 'products.id')
                    ->where('sales.status', 'confirmed');
            })
            ->orderBy('code')
            ->get();
    }

    private function latestPurchaseProducts()
    {
        // 相関サブクエリで商品ごとの確定仕入を仕入日・明細IDの降順に並べ、先頭の単価を取得する。
        // 同日に複数の仕入明細がある場合は、明細IDが大きい方を最新として扱う。
        $latestPurchasePrice = DB::table('purchase_items')
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->whereColumn('purchase_items.product_id', 'products.id')
            ->where('purchases.status', 'confirmed')
            ->orderByDesc('purchases.purchase_date')
            ->orderByDesc('purchase_items.id')
            ->limit(1)
            ->select('purchase_items.unit_price');

        return Product::query()
            ->active()
            ->select('products.*')
            ->selectSub($latestPurchasePrice, 'latest_purchase_price')
            ->orderBy('code')
            ->get();
    }

    private function shortageProducts()
    {
        // 商品と在庫をLEFT JOINし、在庫レコードがない商品はCOALESCEで現在庫数を0として扱う。
        // 現在庫数が発注基準数以下の商品について、基準数との差分を不足数として算出する。
        return Product::query()
            ->active()
            ->leftJoin('stocks', 'stocks.product_id', '=', 'products.id')
            ->whereRaw('COALESCE(stocks.quantity, 0) <= products.reorder_level')
            ->select('products.*')
            ->selectRaw('products.reorder_level - COALESCE(stocks.quantity, 0) as shortage_quantity')
            ->orderByDesc('shortage_quantity')
            ->orderBy('products.code')
            ->get();
    }
}
