@extends('layouts.guest')

@section('title', 'ログイン')

@section('main')
  <div class="form-card">
    <h1 class="form-title">ログイン</h1>
    <form class="auth-form" action="{{ route('login') }}" method="post" novalidate>
      @csrf
      <x-form-field name="email" type="email" label="メールアドレス" autocomplete="email" required autofocus />
      <x-form-field name="password" type="password" label="パスワード" autocomplete="current-password"
        :use-old="false" required />
      <div class="auth-actions">
        <button class="button" type="submit">ログインする</button>
      </div>
      <p class="link-center">
        <a class="auth-switch-link" href="{{ route('register') }}">会員登録はこちら</a>
      </p>
    </form>
  </div>
@endsection
