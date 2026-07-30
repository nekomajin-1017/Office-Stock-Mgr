<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    private const SUPPLIERS_PER_PAGE = 10;

    public function index(Request $request): View
    {
        // 権限確認後、キーワードと有効状態で仕入先を絞り込み、コード順でページ表示する。
        $this->authorize('viewAny', Supplier::class);

        $suppliers = Supplier::query()
            ->when($request->filled('keyword'), function (Builder $query) use ($request): void {
                $keyword = '%'.$request->string('keyword')->toString().'%';

                $query->where(function (Builder $query) use ($keyword): void {
                    $query->where('code', 'like', $keyword)
                        ->orWhere('name', 'like', $keyword);
                });
            })
            ->when($request->input('is_active') !== null && $request->input('is_active') !== '', function (Builder $query) use ($request): void {
                $query->where('is_active', $request->boolean('is_active'));
            })
            ->orderBy('code')
            ->paginate(self::SUPPLIERS_PER_PAGE)
            ->withQueryString();

        return view('suppliers.index', compact('suppliers'));
    }

    public function create(): View
    {
        // 仕入先の登録権限を確認し、空の仕入先モデルを登録画面へ渡す。
        $this->authorize('create', Supplier::class);

        return view('suppliers.form', ['supplier' => new Supplier]);
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        // 検証済み入力値から仕入先を登録し、一覧画面へ戻す。
        Supplier::create($request->validated());

        return to_route('suppliers.index')->with('status', '仕入先を登録しました。');
    }

    public function edit(Supplier $supplier): View
    {
        // 対象仕入先の更新権限を確認し、現在値を編集画面へ渡す。
        $this->authorize('update', $supplier);

        return view('suppliers.form', compact('supplier'));
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        // 対象仕入先を検証済み入力値で更新し、一覧画面へ戻す。
        $supplier->update($request->validated());

        return to_route('suppliers.index')->with('status', '仕入先情報を更新しました。');
    }

    public function toggleStatus(Supplier $supplier): RedirectResponse
    {
        // 更新権限を確認し、有効・無効を反転して結果に応じたメッセージを返す。
        $this->authorize('update', $supplier);

        $supplier->update(['is_active' => ! $supplier->is_active]);

        $message = $supplier->is_active ? '仕入先を再有効化しました。' : '仕入先を無効化しました。';

        return to_route('suppliers.index')->with('status', $message);
    }
}
