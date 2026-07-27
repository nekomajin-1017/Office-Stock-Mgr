<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title') | OfficeStockMgr</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="guest-body">
  <main class="auth-main">
    @yield('main')
  </main>
</body>

</html>
