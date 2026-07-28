<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class StockController extends Controller
{
  private const STOCKS_PER_PAGE = 10;

  public function index(Request $request): View
  {
    $this->authorize('viewAny', Stock::class);

    $stockQuery = Stock::query()
      ->with(['product.category'])
      ->when($request->filled('keyword'), function (Builder $query) use ($request): void {
        $keyword = '%'.$request->string('keyword')->toString().'%';

        $query->whereHas('product', function (Builder $query) use ($keyword): void {
          $query->where('code', 'like', $keyword)
            ->orWhere('name', 'like', $keyword);
        });
      })
      ->when($request->filled('category_id'), function (Builder $query) use ($request): void {
        $query->whereHas('product', function (Builder $query) use ($request): void {
          $query->where('category_id', $request->integer('category_id'));
        });
      })
      ->when($request->boolean('shortage_only'), function (Builder $query): void {
        $query->whereHas('product', function (Builder $query): void {
          $query->whereColumn('stocks.quantity', '<=', 'products.reorder_level');
        });
      });

    $totalInventoryValue = (float) (clone $stockQuery)
      ->selectRaw('COALESCE(SUM(quantity * average_cost), 0) AS total_inventory_value')
      ->value('total_inventory_value');

    $stocks = $stockQuery
      ->join('products', 'stocks.product_id', '=', 'products.id')
      ->select('stocks.*')
      ->orderBy('products.code')
      ->paginate(self::STOCKS_PER_PAGE)
      ->withQueryString();

    return view('stocks.index', [
      'stocks' => $stocks,
      'categories' => Category::query()->orderBy('name')->get(),
      'totalInventoryValue' => $totalInventoryValue,
    ]);
  }

  public function movements(Product $product): View
  {
    $this->authorize('view', $product);

    $movements = $product->stockMovements()
      ->with(['creator', 'reference'])
      ->orderBy('occurred_at')
      ->orderBy('id')
      ->get();
    $quantityAfter = 0;

    $movements->each(function ($movement) use (&$quantityAfter): void {
      $quantityAfter += $movement->quantity_change;
      $movement->setAttribute('quantity_after', $quantityAfter);
    });

    return view('stocks.movements', compact('product', 'movements'));
  }
}
