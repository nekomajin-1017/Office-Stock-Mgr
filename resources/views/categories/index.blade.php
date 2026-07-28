@extends('layouts.app')

@section('title', 'カテゴリ管理')

@section('content')
    <section class="dashboard-main">
        <h1 class="category-page-title">カテゴリ管理</h1>
        @if (session('status'))
            <p class="success-message">{{ session('status') }}</p>
        @endif
        <section class="category-registration" aria-labelledby="category-registration-title">
            <h2 id="category-registration-title">新規登録</h2>
            <form class="category-form category-form--inline" action="{{ route('categories.store') }}" method="post">
                @csrf
                <x-form-field name="name" label="カテゴリ名" required />
                <div class="form-group">
                    <label class="form-label" for="new-is-active">状態</label>
                    <select id="new-is-active" class="form-control" name="is_active" required>
                        <option value="1" @selected(old('is_active', '1') === '1')>有効</option>
                        <option value="0" @selected(old('is_active') === '0')>無効</option>
                    </select>
                    @error('is_active')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </div>
                <button class="button button-inline" type="submit">登録する</button>
            </form>
        </section>
        <section aria-labelledby="category-list-title">
            <h2 id="category-list-title">カテゴリ一覧</h2>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">カテゴリ名</th>
                            <th scope="col">状態</th>
                            <th scope="col"><span class="sr-only">操作</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $category)
                            <tr>
                                <td>{{ $category->name }}</td>
                                <td>{{ $category->is_active ? '有効' : '無効' }}</td>
                                <td>
                                    <details>
                                        <summary class="action-button">編集</summary>
                                        <form class="category-form" action="{{ route('categories.update', $category) }}" method="post">
                                            @csrf
                                            @method('put')
                                            <x-form-field id="name-{{ $category->id }}" name="name" label="カテゴリ名" :value="$category->name" required />
                                            <div class="form-group">
                                                <label class="form-label" for="is-active-{{ $category->id }}">状態</label>
                                                <select id="is-active-{{ $category->id }}" class="form-control" name="is_active" required>
                                                    <option value="1" @selected($category->is_active)>有効</option>
                                                    <option value="0" @selected(! $category->is_active)>無効</option>
                                                </select>
                                            </div>
                                            <button class="button button-inline" type="submit">更新する</button>
                                        </form>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3">カテゴリが登録されていません。</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </section>
@endsection
