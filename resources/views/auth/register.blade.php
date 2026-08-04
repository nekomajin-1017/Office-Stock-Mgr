@extends('layouts.guest')

@section('stylesheet', 'css/shared/auth.css')


@section('title', '会員登録')

@section('main')
    <div class="content-block form-card">
        <h1 class="page-title form-title">会員登録</h1>
        <form class="form-container auth-form" action="{{ route('register') }}" method="post" novalidate>
            @csrf
            <x-form-field name="name" label="ユーザー名" autocomplete="name" required autofocus />
            <x-form-field name="email" type="email" label="メールアドレス" autocomplete="email" required />
            <x-form-field name="password" type="password" label="パスワード" autocomplete="new-password"
                :use-old="false" required />
            <x-form-field name="password_confirmation" type="password" label="確認用パスワード" autocomplete="new-password"
                :use-old="false" required />
            <div class="content-block auth-actions">
                <button class="form-element button" type="submit">登録する</button>
            </div>
            <p class="text-content link-center">
                <a class="page-link auth-switch-link" href="{{ route('login') }}">ログインはこちら</a>
            </p>
        </form>
    </div>
@endsection
