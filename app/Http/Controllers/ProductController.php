<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    private const PRODUCTS_PER_PAGE = 10;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        $latestPurchasePrice = DB::table('purchase_items')
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->whereColumn('purchase_items.product_id', 'products.id')
            ->where('purchases.status', 'confirmed')
            ->orderByDesc('purchases.purchase_date')
            ->orderByDesc('purchase_items.id')
            ->limit(1)
            ->select('purchase_items.unit_price');

        $products = Product::query()
            ->with(['category', 'stock'])
            ->select('products.*')
            ->selectSub($latestPurchasePrice, 'latest_purchase_price')
            ->when($request->filled('keyword'), function (Builder $query) use ($request): void {
                $keyword = '%'.$request->string('keyword')->toString().'%';

                $query->where(function (Builder $query) use ($keyword): void {
                    $query->where('code', 'like', $keyword)
                        ->orWhere('name', 'like', $keyword);
                });
            })
            ->when($request->filled('category_id'), function (Builder $query) use ($request): void {
                $query->where('category_id', $request->integer('category_id'));
            })
            ->when($request->input('is_active') !== null && $request->input('is_active') !== '', function (Builder $query) use ($request): void {
                $query->where('is_active', $request->boolean('is_active'));
            })
            ->orderBy('code')
            ->paginate(self::PRODUCTS_PER_PAGE)
            ->withQueryString();

        return view('products.index', [
            'products' => $products,
            'categories' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);

        return view('products.form', [
            'product' => new Product,
            'categories' => $this->categoriesForForm(),
            'suppliers' => Supplier::active()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $product = Product::create($request->validated());
            $product->stock()->create([
                'quantity' => 0,
                'average_cost' => 0,
            ]);
        });

        return to_route('products.index')->with('status', '商品を登録しました。');
    }

    public function show(Product $product): View
    {
        $this->authorize('view', $product);

        return view('products.show', [
            'product' => $product->load(['category', 'stock']),
        ]);
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        return view('products.form', [
            'product' => $product,
            'categories' => $this->categoriesForForm($product),
            'suppliers' => Supplier::active()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return to_route('products.index')->with('status', '商品情報を更新しました。');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $product->update(['is_active' => false]);

        return to_route('products.index')->with('status', '商品を無効化しました。');
    }

    private function categoriesForForm(?Product $product = null): Collection
    {
        return Category::query()
            ->where(function (Builder $query) use ($product): void {
                $query->where('is_active', true);

                if ($product) {
                    $query->orWhere($query->getModel()->getKeyName(), $product->category_id);
                }
            })
            ->orderBy('name')
            ->get();
    }
}
