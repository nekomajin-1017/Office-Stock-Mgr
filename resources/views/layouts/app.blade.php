<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'OfficeStockMgr') | OfficeStockMgr</title>
    <link rel="stylesheet" href="{{ asset('css/layouts/app.css') }}">
    @hasSection('stylesheet')
        <link rel="stylesheet" href="{{ asset(trim($__env->yieldContent('stylesheet'))) }}">
    @endif
</head>

<body class="page-body app-body">
    <div class="content-block app-shell">
        <header class="app-header">
            <a class="page-link app-brand" href="{{ route('products.index') }}">
                <span class="text-span app-brand-mark" aria-hidden="true">OS</span>
                <span class="text-span">
                    <span class="text-span app-brand-name">OfficeStockMgr</span>
                    <span class="text-span app-brand-caption">在庫・仕入管理</span>
                </span>
            </a>
            <div class="content-block app-account">
                <span class="text-span app-account-name">{{ auth()->user()->name }}</span>
                <form class="form-container" action="{{ route('logout') }}" method="post">
                    @csrf
                    <button class="form-element logout-button" type="submit">ログアウト</button>
                </form>
            </div>
        </header>
        <aside class="page-link app-sidebar">
            <nav aria-label="メインナビゲーション">
                <p class="text-content app-nav-label">業務メニュー</p>
                <ul class="app-nav">
                    <li class="list-item">
                        <a @class(['page-link','app-nav-link', 'is-active' => request()->routeIs('products.*')]) href="{{ route('products.index') }}">
                            商品管理
                        </a>
                    </li>
                    <li class="list-item">
                        <a @class(['page-link','app-nav-link', 'is-active' => request()->routeIs('suppliers.*')]) href="{{ route('suppliers.index') }}">
                            仕入先管理
                        </a>
                    </li>
                    <li class="list-item">
                        <a @class(['page-link','app-nav-link', 'is-active' => request()->routeIs('purchases.*')]) href="{{ route('purchases.index') }}">
                            仕入管理
                        </a>
                    </li>
                    <li class="list-item">
                        <a @class(['page-link','app-nav-link', 'is-active' => request()->routeIs('sales.*')]) href="{{ route('sales.index') }}">
                            販売管理
                        </a>
                    </li>
                    <li class="list-item">
                        <a @class(['page-link','app-nav-link', 'is-active' => request()->routeIs('customers.*')]) href="{{ route('customers.index') }}">
                            顧客管理
                        </a>
                    </li>
                    <li class="list-item">
                        <a @class(['page-link','app-nav-link', 'is-active' => request()->routeIs('stocks.*')]) href="{{ route('stocks.index') }}">
                            在庫管理
                        </a>
                    </li>
                    <li class="list-item">
                        <a @class(['page-link','app-nav-link', 'is-active' => request()->routeIs('reports.*')]) href="{{ route('reports.index') }}">
                            レポート
                        </a>
                    </li>
                    @can('viewAny', App\Models\Category::class)
                        <li class="list-item">
                            <a @class(['page-link','app-nav-link', 'is-active' => request()->routeIs('categories.*')]) href="{{ route('categories.index') }}">
                                カテゴリ管理
                            </a>
                        </li>
                    @endcan
                    @can('viewAny', App\Models\User::class)
                        <li class="list-item">
                            <a @class(['page-link','app-nav-link', 'is-active' => request()->routeIs('users.*')]) href="{{ route('users.index') }}">
                                ユーザー管理
                            </a>
                        </li>
                    @endcan
                </ul>
            </nav>
        </aside>
        <main class="app-main">
            @yield('content')
        </main>
    </div>
    <script>
        const initializeLineItemForm = ({
            formSelector,
            itemsSelector,
            itemSelector,
            totalSelector,
            addSelector,
            removeSelector,
            addItem,
            afterChange = () => {},
        }) => {
            const form = document.querySelector(formSelector);

            if (!form) return;

            const items = form.querySelector(itemsSelector);
            const total = form.querySelector(totalSelector);

            const updateTotals = () => {
                let formTotal = 0;

                items.querySelectorAll(itemSelector).forEach((item) => {
                    const quantity = Number(item.querySelector('[name$="[quantity]"]').value) || 0;
                    const unitPrice = Number(item.querySelector('[name$="[unit_price]"]').value) || 0;
                    const lineTotal = quantity * unitPrice;

                    item.querySelector('[data-line-total]').textContent = `${lineTotal.toFixed(0)} 円`;
                    formTotal += lineTotal;
                });

                total.textContent = `${formTotal.toFixed(0)} 円`;
            };

            form.addEventListener('input', updateTotals);
            form.addEventListener('click', (event) => {
                const addButton = event.target.closest(addSelector);
                const removeButton = event.target.closest(removeSelector);

                if (addButton) addItem(items);
                if (removeButton && items.querySelectorAll(itemSelector).length > 1) {
                    removeButton.closest(itemSelector).remove();
                }

                afterChange();
                updateTotals();
            });

            afterChange();
            updateTotals();
        };

        const purchaseForm = document.querySelector('[data-purchase-form]');

        if (purchaseForm) {
            const supplier = purchaseForm.querySelector('[data-purchase-supplier]');
            const template = document.querySelector('[data-purchase-template]');

            const filterProductsBySupplier = () => {
                const supplierId = supplier.value;

                purchaseForm.querySelectorAll('[name$="[product_id]"]').forEach((select) => {
                    [...select.options].forEach((option) => {
                        if (!option.dataset.supplier) return;

                        option.hidden = supplierId !== '' && option.dataset.supplier !== supplierId;
                    });

                    if (
                        select.selectedOptions[0]?.dataset.supplier &&
                        select.selectedOptions[0].dataset.supplier !== supplierId
                    ) {
                        select.value = '';
                    }
                });
            };

            initializeLineItemForm({
                formSelector: '[data-purchase-form]',
                itemsSelector: '[data-purchase-items]',
                itemSelector: '[data-purchase-item]',
                totalSelector: '[data-purchase-total]',
                addSelector: '[data-add-item]',
                removeSelector: '[data-remove-item]',
                addItem: (items) => {
                    const index = items.querySelectorAll('[data-purchase-item]').length;
                    items.insertAdjacentHTML(
                        'beforeend',
                        template.innerHTML.replaceAll('__INDEX__', index),
                    );
                },
                afterChange: filterProductsBySupplier,
            });

            supplier.addEventListener('change', filterProductsBySupplier);
        }

        initializeLineItemForm({
            formSelector: '[data-sale-form]',
            itemsSelector: '[data-sale-items]',
            itemSelector: '[data-sale-item]',
            totalSelector: '[data-sale-total]',
            addSelector: '[data-add-sale-item]',
            removeSelector: '[data-remove-sale-item]',
            addItem: (items) => {
                const item = items.firstElementChild.cloneNode(true);
                const index = items.children.length;

                item.querySelectorAll('[name]').forEach((input) => {
                    input.name = input.name.replace(/items\[\d+]/, `items[${index}]`);
                    input.value = input.tagName === 'INPUT' && input.name.endsWith('[quantity]') ? 1 : '';
                });

                items.append(item);
            },
        });
    </script>
</body>

</html>
