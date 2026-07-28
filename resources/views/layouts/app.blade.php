<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'OfficeStockMgr') | OfficeStockMgr</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="app-body">
  <div class="app-shell">
    <header class="app-header">
      <a class="app-brand" href="{{ route('products.index') }}">
        <span class="app-brand-mark" aria-hidden="true">OS</span>
        <span>
          <span class="app-brand-name">OfficeStockMgr</span>
          <span class="app-brand-caption">在庫・仕入管理</span>
        </span>
      </a>
      <div class="app-account">
        <span class="app-account-name">{{ auth()->user()->name }}</span>
        <form action="{{ route('logout') }}" method="post">
          @csrf
          <button class="logout-button" type="submit">ログアウト</button>
        </form>
      </div>
    </header>
    <aside class="app-sidebar">
      <nav aria-label="メインナビゲーション">
        <p class="app-nav-label">業務メニュー</p>
        <ul class="app-nav">
          <li>
            <a @class(['app-nav-link', 'is-active' => request()->routeIs('products.*')]) href="{{ route('products.index') }}">
              <span class="app-nav-icon" aria-hidden="true">商品</span>
              商品管理
            </a>
          </li>
          <li>
            <a @class(['app-nav-link', 'is-active' => request()->routeIs('suppliers.*')]) href="{{ route('suppliers.index') }}">
              <span class="app-nav-icon" aria-hidden="true">仕入</span>
              仕入先管理
            </a>
          </li>
          <li>
            <a @class(['app-nav-link', 'is-active' => request()->routeIs('purchases.*')]) href="{{ route('purchases.index') }}">
              <span class="app-nav-icon" aria-hidden="true">伝票</span>
              仕入管理
            </a>
          </li>
          <li>
            <a @class(['app-nav-link', 'is-active' => request()->routeIs('customers.*')]) href="{{ route('customers.index') }}">
              <span class="app-nav-icon" aria-hidden="true">顧客</span>
              顧客管理
            </a>
          </li>
          <li>
            <a @class(['app-nav-link', 'is-active' => request()->routeIs('stocks.*')]) href="{{ route('stocks.index') }}">
              <span class="app-nav-icon" aria-hidden="true">在庫</span>
              在庫管理
            </a>
          </li>
          @can('viewAny', App\Models\Category::class)
            <li>
              <a @class(['app-nav-link', 'is-active' => request()->routeIs('categories.*')]) href="{{ route('categories.index') }}">
                <span class="app-nav-icon" aria-hidden="true">分</span>
                カテゴリ管理
              </a>
            </li>
          @endcan
          @can('viewAny', App\Models\User::class)
            <li>
              <a @class(['app-nav-link', 'is-active' => request()->routeIs('users.*')]) href="{{ route('users.index') }}">
                <span class="app-nav-icon" aria-hidden="true">人</span>
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
