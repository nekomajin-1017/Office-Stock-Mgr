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
        // 権限確認後、検索条件で商品を絞り込み、カテゴリと在庫をまとめて一覧表示する。
        $this->authorize('viewAny', Product::class);

        $products = Product::query()
            ->with(['category', 'stock'])
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
        // 商品登録権限を確認し、有効なカテゴリ・仕入先を登録画面へ渡す。
        $this->authorize('create', Product::class);

        return view('products.form', [
            'product' => new Product,
            'categories' => $this->categoriesForForm(),
            'suppliers' => Supplier::active()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        // 商品と初期在庫を同一トランザクションで登録し、一覧画面へ戻す。
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
        // 閲覧権限を確認し、カテゴリと在庫を読み込んだ商品詳細を表示する。
        $this->authorize('view', $product);

        return view('products.show', [
            'product' => $product->load(['category', 'stock']),
        ]);
    }

    public function edit(Product $product): View
    {
        // 更新権限を確認し、現在値と選択肢を商品編集画面へ渡す。
        $this->authorize('update', $product);

        return view('products.form', [
            'product' => $product,
            'categories' => $this->categoriesForForm($product),
            'suppliers' => Supplier::active()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        // 対象商品を検証済み入力値で更新し、一覧画面へ戻す。
        $product->update($request->validated());

        return to_route('products.index')->with('status', '商品情報を更新しました。');
    }

    public function destroy(Product $product): RedirectResponse
    {
        // 削除権限を確認し、履歴を残すため物理削除せず商品を無効化する。
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
