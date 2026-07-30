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

        $from = $request->string('from')->toString();
        $to = $request->string('to')->toString();
        $limit = min(
            max($request->integer('limit', self::DEFAULT_RANKING_LIMIT), 1),
            self::MAX_RANKING_LIMIT,
        );

        $salesTotals = $this->salesTotalsQuery($from, $to);
        $averageSalesQuantity = (float) (DB::query()
            ->fromSub($salesTotals, 'sales_totals')
            ->avg('sales_quantity') ?? 0);

        $aboveAverageProducts = Product::query()
            ->active()
            ->joinSub(clone $salesTotals, 'sales_totals', function ($join): void {
                $join->on('products.id', '=', 'sales_totals.product_id');
            })
            ->whereRaw(
                'CAST(sales_totals.sales_quantity AS DECIMAL(15, 4)) > ?',
                [$averageSalesQuantity],
            )
            ->select('products.*', 'sales_totals.sales_quantity')
            ->orderByDesc('sales_totals.sales_quantity')
            ->orderBy('products.code')
            ->get();

        $salesRanking = Product::query()
            ->active()
            ->joinSub(clone $salesTotals, 'sales_totals', function ($join): void {
                $join->on('products.id', '=', 'sales_totals.product_id');
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
            'aboveAverageProducts' => $aboveAverageProducts,
            'salesRanking' => $salesRanking,
            'from' => $from,
            'to' => $to,
            'limit' => $limit,
            'averageSalesQuantity' => $averageSalesQuantity,
        ]);
    }

    private function salesTotalsQuery(string $from, string $to)
    {
        // 確定済み販売明細を期間で絞り込み、商品別の販売数量と販売金額を集計する。
        $query = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'confirmed');

        if ($from !== '') {
            $query->whereDate('sales.sale_date', '>=', $from);
        }

        if ($to !== '') {
            $query->whereDate('sales.sale_date', '<=', $to);
        }

        return $query
            ->select('sale_items.product_id')
            ->selectRaw('SUM(sale_items.quantity) as sales_quantity')
            ->selectRaw('SUM(sale_items.subtotal) as sales_amount')
            ->groupBy('sale_items.product_id');
    }

    private function unsoldProducts()
    {
        // 確定済みの販売実績が存在しない有効商品を抽出する。
        return Product::query()
            ->active()
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
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
        // 商品ごとに直近の確定仕入明細を相関サブクエリで取得する。
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
        // 現在庫が発注点以下の商品を不足数の多い順で取得する。
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
