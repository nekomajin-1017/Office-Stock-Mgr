<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Free Market App</title>
  <link rel="stylesheet" href="{{ asset('css/common.css') }}">
  <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
  @livewireStyles
  @yield('css')
</head>

<body class="app-body">
  <header class="header">
    <div class="header-inner">
      <a class="link-reset" href="{{ route('home') }}">
        <img class="logo-img img-fluid" src="{{ asset('img/mainLogo.png') }}" alt="COACHTECH">
      </a>
      @unless (request()->routeIs('verification.notice'))
        <div class="search-bar">
          <form action="{{ route('home') }}" method="get">
            <input class="search-input" type="text" name="keyword" placeholder="何をお探しですか？"
              value="{{ request('keyword', '') }}">
            @if (request('tab') === 'mylist')
              <input type="hidden" name="tab" value="mylist">
            @endif
          </form>
        </div>
        <ul class="header-actions">
          @if (Auth::check())
            <li>
              <form action="{{ route('logout') }}" method="post">
                @csrf
                <button class="header-button" type="submit">ログアウト</button>
              </form>
            </li>
          @else
            <li><a class="header-link link-reset" href="{{ route('login') }}">ログイン</a></li>
          @endif
          <li><a class="header-link link-reset" href="{{ route('mypage') }}">マイページ</a></li>
          <li><a class="header-link header-link--sell link-reset" href="{{ route('sell.show') }}">出品</a></li>
        </ul>
      @endunless
      @yield('header')
    </div>
  </header>
  <main class="main">
    @yield('main')
  </main>

  @livewireScripts
</body>

</html>
