<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    private const CUSTOMERS_PER_PAGE = 10;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Customer::class);

        $customers = Customer::query()
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
            ->paginate(self::CUSTOMERS_PER_PAGE)
            ->withQueryString();

        return view('customers.index', compact('customers'));
    }

    public function create(): View
    {
        $this->authorize('create', Customer::class);

        return view('customers.form', ['customer' => new Customer]);
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        Customer::create($request->validated());

        return to_route('customers.index')->with('status', '顧客を登録しました。');
    }

    public function edit(Customer $customer): View
    {
        $this->authorize('update', $customer);

        return view('customers.form', compact('customer'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return to_route('customers.index')->with('status', '顧客情報を更新しました。');
    }

    public function toggleStatus(Customer $customer): RedirectResponse
    {
        $this->authorize('update', $customer);

        $customer->update(['is_active' => ! $customer->is_active]);

        $message = $customer->is_active ? '顧客を再有効化しました。' : '顧客を無効化しました。';

        return to_route('customers.index')->with('status', $message);
    }
}
