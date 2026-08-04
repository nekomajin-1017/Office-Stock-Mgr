<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'OfficeStockMgr') | OfficeStockMgr</title>
    <link rel="stylesheet" href="{{ asset(trim($__env->yieldContent('stylesheet', 'css/layouts/app.css'))) }}">
    @vite('resources/js/app.js')
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
</body>

</html>
