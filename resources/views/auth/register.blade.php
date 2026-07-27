@extends('layouts.guest')

@section('title', '会員登録')

@section('main')
  <div class="form-card">
    <h1 class="form-title">会員登録</h1>
    <form class="auth-form" action="{{ route('register') }}" method="post" novalidate>
      @csrf
      <x-form-field name="name" label="ユーザー名" autocomplete="name" required autofocus />
      <x-form-field name="email" type="email" label="メールアドレス" autocomplete="email" required />
      <x-form-field name="password" type="password" label="パスワード" autocomplete="new-password"
        :use-old="false" required />
      <x-form-field name="password_confirmation" type="password" label="確認用パスワード" autocomplete="new-password"
        :use-old="false" required />
      <div class="auth-actions">
        <button class="button" type="submit">登録する</button>
      </div>
      <p class="link-center">
        <a class="auth-switch-link" href="{{ route('login') }}">ログインはこちら</a>
      </p>
    </form>
  </div>
@endsection
