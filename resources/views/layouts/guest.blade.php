<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') | OfficeStockMgr</title>
    <link rel="stylesheet" href="{{ asset('css/layouts/guest.css') }}">
    @hasSection('stylesheet')
        <link rel="stylesheet" href="{{ asset(trim($__env->yieldContent('stylesheet'))) }}">
    @endif
    @vite('resources/js/app.js')
</head>

<body class="page-body guest-body">
    <main class="auth-main">
        <section class="auth-panel">
            <a class="page-link guest-brand" href="{{ route('products.index') }}">OfficeStockMgr</a>
            @yield('main')
        </section>
    </main>
</body>

</html>
