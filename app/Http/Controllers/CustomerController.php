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
        // 権限確認後、キーワードと有効状態で顧客を絞り込み、コード順でページ表示する。
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
        // 顧客の登録権限を確認し、空の顧客モデルを登録画面へ渡す。
        $this->authorize('create', Customer::class);

        return view('customers.form', ['customer' => new Customer]);
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        // 検証済み入力値から顧客を登録し、一覧画面へ戻す。
        Customer::create($request->validated());

        return to_route('customers.index')->with('status', '顧客を登録しました。');
    }

    public function edit(Customer $customer): View
    {
        // 対象顧客の更新権限を確認し、現在値を編集画面へ渡す。
        $this->authorize('update', $customer);

        return view('customers.form', compact('customer'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        // 対象顧客を検証済み入力値で更新し、一覧画面へ戻す。
        $customer->update($request->validated());

        return to_route('customers.index')->with('status', '顧客情報を更新しました。');
    }

    public function toggleStatus(Customer $customer): RedirectResponse
    {
        // 更新権限を確認し、有効・無効を反転して結果に応じたメッセージを返す。
        $this->authorize('update', $customer);

        $customer->update(['is_active' => ! $customer->is_active]);

        $message = $customer->is_active ? '顧客を再有効化しました。' : '顧客を無効化しました。';

        return to_route('customers.index')->with('status', $message);
    }
}
