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
        $this->authorize('create', Supplier::class);

        return view('suppliers.form', ['supplier' => new Supplier]);
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        Supplier::create($request->validated());

        return to_route('suppliers.index')->with('status', '仕入先を登録しました。');
    }

    public function edit(Supplier $supplier): View
    {
        $this->authorize('update', $supplier);

        return view('suppliers.form', compact('supplier'));
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

        return to_route('suppliers.index')->with('status', '仕入先情報を更新しました。');
    }

    public function toggleStatus(Supplier $supplier): RedirectResponse
    {
        $this->authorize('update', $supplier);

        $supplier->update(['is_active' => ! $supplier->is_active]);

        $message = $supplier->is_active ? '仕入先を再有効化しました。' : '仕入先を無効化しました。';

        return to_route('suppliers.index')->with('status', $message);
    }
}
