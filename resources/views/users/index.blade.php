@extends('layouts.app')

@section('stylesheet', 'css/users/index.css')


@section('title', 'ユーザー管理')

@section('content')
    <section class="dashboard-main">
        <div class="content-block page-heading">
            <h1 class="page-title">ユーザー管理</h1>
            <a class="page-link button button-link" href="{{ route('users.create') }}">ユーザー登録</a>
        </div>
        @if (session('status'))
            <p class="text-content success-message">{{ session('status') }}</p>
        @endif
        <div class="content-block table-wrapper">
            <table class="data-table">
                <thead>
                    <tr class="table-row">
                        <th class="table-heading" scope="col">名前</th>
                        <th class="table-heading" scope="col">メールアドレス</th>
                        <th class="table-heading" scope="col">権限</th>
                        <th class="table-heading" scope="col">状態</th>
                        <th class="table-heading" scope="col"><span class="text-span sr-only">操作</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr class="table-row">
                            <td class="table-cell">{{ $user->name }}</td>
                            <td class="table-cell">{{ $user->email }}</td>
                            <td class="table-cell">{{ $user->isAdmin() ? '管理者' : '一般ユーザー' }}</td>
                            <td class="table-cell">{{ $user->is_active ? '有効' : '無効' }}</td>
                            <td class="table-cell"><a class="page-link action-button" href="{{ route('users.edit', $user) }}">編集</a></td>
                        </tr>
                    @empty
                        <tr class="table-row">
                            <td class="table-cell" colspan="5">ユーザーが登録されていません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
