@extends('layouts.guest')

@section('stylesheet', 'css/shared/auth.css')


@section('title', 'ログイン')

@section('main')
    <div class="content-block form-card">
        <h1 class="page-title form-title">ログイン</h1>
        <form class="form-container auth-form" action="{{ route('login') }}" method="post" novalidate>
            @csrf
            <x-form-field name="email" type="email" label="メールアドレス" autocomplete="email" required autofocus />
            <x-form-field name="password" type="password" label="パスワード" autocomplete="current-password"
                :use-old="false" required />
            <div class="content-block auth-actions">
                <button class="form-element button" type="submit">ログインする</button>
            </div>
            <p class="text-content link-center">
                <a class="page-link auth-switch-link" href="{{ route('register') }}">会員登録はこちら</a>
            </p>
        </form>
    </div>
@endsection
