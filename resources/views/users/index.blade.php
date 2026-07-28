@extends('layouts.app')

@section('title', 'ユーザー管理')

@section('content')
    <section class="dashboard-main">
        <div class="page-heading">
            <h1>ユーザー管理</h1>
            <a class="button button-link" href="{{ route('users.create') }}">ユーザー登録</a>
        </div>
        @if (session('status'))
            <p class="success-message">{{ session('status') }}</p>
        @endif
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">名前</th>
                        <th scope="col">メールアドレス</th>
                        <th scope="col">権限</th>
                        <th scope="col">状態</th>
                        <th scope="col"><span class="sr-only">操作</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->isAdmin() ? '管理者' : '一般ユーザー' }}</td>
                            <td>{{ $user->is_active ? '有効' : '無効' }}</td>
                            <td><a class="action-button" href="{{ route('users.edit', $user) }}">編集</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">ユーザーが登録されていません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
