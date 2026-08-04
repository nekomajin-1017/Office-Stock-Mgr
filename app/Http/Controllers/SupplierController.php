<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FiltersContacts;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    use FiltersContacts;

    private const SUPPLIERS_PER_PAGE = 10;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Supplier::class);

        $suppliers = $this->applyContactFilters(Supplier::query(), $request)
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

        $message = $this->toggleContactStatus($supplier)
            ? '仕入先を再有効化しました。'
            : '仕入先を無効化しました。';

        return to_route('suppliers.index')->with('status', $message);
    }
}
