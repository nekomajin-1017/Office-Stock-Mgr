<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'OfficeStockMgr') | OfficeStockMgr</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
  <header class="app-header">
    <a class="app-name" href="{{ route('dashboard') }}">OfficeStockMgr</a>
    <nav aria-label="メインナビゲーション">
      <ul class="app-nav">
        <li><a href="{{ route('dashboard') }}">ダッシュボード</a></li>
        <li><a href="{{ route('products.index') }}">商品管理</a></li>
        @can('viewAny', App\Models\User::class)
          <li><a href="{{ route('users.index') }}">ユーザー管理</a></li>
        @endcan
        @can('viewAny', App\Models\Category::class)
          <li><a href="{{ route('categories.index') }}">カテゴリ管理</a></li>
        @endcan
      </ul>
    </nav>
    <form action="{{ route('logout') }}" method="post">
      @csrf
      <button class="logout-button" type="submit">ログアウト</button>
    </form>
  </header>
  <main class="app-main">
    @yield('content')
  </main>
</body>

</html>
