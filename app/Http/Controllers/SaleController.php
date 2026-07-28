<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Http\Requests\StoreSaleRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

  public function create(): View { $this->authorize('create', Sale::class); return view('sales.form', ['customers' => Customer::active()->get(), 'products' => Product::active()->with('stock')->get()]); }
  public function store(StoreSaleRequest $request): RedirectResponse
  {
    $this->authorize('create', Sale::class);
    $data = $request->validated();
    $products = Product::query()->with('stock')->whereIn('id', collect($data['items'])->pluck('product_id'))->get()->keyBy('id');

    foreach ($data['items'] as $index => $item) {
      $stock = $products[$item['product_id']]->stock;
      if (! $stock || $stock->quantity < $item['quantity']) return back()->withErrors(["items.$index.quantity" => '在庫数が不足しています。'])->withInput();
    }

    $sale = DB::transaction(function () use ($data): Sale {
      $items = collect($data['items'])->map(fn ($item) => [...$item, 'cost_unit_price' => 0, 'subtotal' => $item['quantity'] * $item['unit_price'], 'cost_amount' => 0, 'tax_amount' => 0]);
      $subtotal = $items->sum('subtotal');
      $sale = Sale::create(['sale_number' => 'SAL-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)), 'customer_id' => $data['customer_id'], 'sale_date' => $data['sale_date'], 'status' => 'draft', 'subtotal' => $subtotal, 'tax_amount' => 0, 'total_amount' => $subtotal, 'created_by' => auth()->id()]);
      $sale->items()->createMany($items->all());
      return $sale;
    });
    return to_route('sales.show', $sale)->with('status', '販売伝票を下書き登録しました。');
  }
  public function show(Sale $sale): View { $this->authorize('view', $sale); return view('sales.show', ['sale' => $sale->load(['customer', 'creator', 'items.product'])]); }
}
