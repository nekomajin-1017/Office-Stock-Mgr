<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ダッシュボード | OfficeStockMgr</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
  <header class="app-header">
    <p class="app-name">OfficeStockMgr</p>
    <form action="{{ route('logout') }}" method="post">
      @csrf
      <button class="logout-button" type="submit">ログアウト</button>
    </form>
  </header>
  <main class="dashboard-main">
    <h1>ダッシュボード</h1>
    <p>{{ Auth::user()->name }} さん、ログインしました。</p>
  </main>
</body>

</html>
