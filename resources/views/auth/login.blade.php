@extends('layouts.app')

@section('css')
  <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endsection

@section('main')
  <div class="form-card">
    <h1 class="form-title">ログイン</h1>
    <form class="auth-form" action="{{ route('login') }}" method="post" novalidate>
      @csrf
      <x-form-field name="email" type="email" label="メールアドレス" />
      <x-form-field name="password" type="password" label="パスワード" :use-old="false" />
      <div class="auth-actions">
        <button class="button" type="submit">ログインする</button>
      </div>
      <p class="link-center"><a class="link-reset auth-switch-link" href="{{ route('register') }}">会員登録はこちら</a></p>
    </form>
  </div>
@endsection
