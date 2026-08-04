@extends('layouts.app')

@section('stylesheet', 'css/users/form.css')


@php
    $isEditing = $user->exists;
@endphp

@section('title', $isEditing ? 'ユーザー編集' : 'ユーザー登録')

@section('content')
    <section class="dashboard-main form-page">
        <h1 class="page-title">{{ $isEditing ? 'ユーザー編集' : 'ユーザー登録' }}</h1>
        <form class="form-container user-form" action="{{ $isEditing ? route('users.update', $user) : route('users.store') }}" method="post">
            @csrf
            @if ($isEditing)
                @method('put')
            @endif
            <x-form-field name="name" label="名前" :value="$user->name" required autofocus />
            <x-form-field name="email" type="email" label="メールアドレス" :value="$user->email" required />
            <x-form-field name="password" type="password" :label="$isEditing ? 'パスワード（変更する場合のみ）' : 'パスワード'" :use-old="false" :required="! $isEditing" />
            <x-form-field name="password_confirmation" type="password" label="パスワード（確認）" :use-old="false" :required="! $isEditing" />
            <div class="content-block form-group">
                <label class="field-label form-label" for="role">権限</label>
                <select id="role" class="form-element form-control" name="role" required>
                    <option value="{{ \App\Models\User::ROLE_USER }}" @selected(old('role', $user->role ?: \App\Models\User::ROLE_USER) === \App\Models\User::ROLE_USER)>一般ユーザー</option>
                    <option value="{{ \App\Models\User::ROLE_ADMIN }}" @selected(old('role', $user->role) === \App\Models\User::ROLE_ADMIN)>管理者</option>
                </select>
                @error('role')
                    <p class="text-content field-error">{{ $message }}</p>
                @enderror
            </div>
            <div class="content-block form-group">
                <label class="field-label form-label" for="is_active">状態</label>
                <select id="is_active" class="form-element form-control" name="is_active" required>
                    <option value="1" @selected(old('is_active', $user->exists ? (int) $user->is_active : 1) == 1)>有効</option>
                    <option value="0" @selected(old('is_active', $user->exists ? (int) $user->is_active : 1) == 0)>無効</option>
                </select>
                @error('is_active')
                    <p class="text-content field-error">{{ $message }}</p>
                @enderror
            </div>
            <div class="content-block form-actions">
                <a class="page-link" href="{{ route('users.index') }}">一覧へ戻る</a>
                <button class="form-element button button-inline" type="submit">{{ $isEditing ? '更新する' : '登録する' }}</button>
            </div>
        </form>
    </section>
@endsection
