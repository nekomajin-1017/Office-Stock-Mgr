@extends('layouts.app')

@section('stylesheet', 'css/categories/index.css')


@section('title', 'カテゴリ管理')

@section('content')
    <section class="dashboard-main">
        <h1 class="page-title category-page-title">カテゴリ管理</h1>
        @if (session('status'))
            <p class="text-content success-message">{{ session('status') }}</p>
        @endif
        <section class="category-registration" aria-labelledby="category-registration-title">
            <h2 class="section-title" id="category-registration-title">新規登録</h2>
            <form class="form-container category-form category-form--inline" action="{{ route('categories.store') }}" method="post">
                @csrf
                <x-form-field name="name" label="カテゴリ名" required />
                <div class="content-block form-group">
                    <label class="field-label form-label" for="new-is-active">状態</label>
                    <select id="new-is-active" class="form-element form-control" name="is_active" required>
                        <option value="1" @selected(old('is_active', '1') === '1')>有効</option>
                        <option value="0" @selected(old('is_active') === '0')>無効</option>
                    </select>
                    @error('is_active')
                        <p class="text-content field-error">{{ $message }}</p>
                    @enderror
                </div>
                <button class="form-element button button-inline" type="submit">登録する</button>
            </form>
        </section>
        <section aria-labelledby="category-list-title">
            <h2 class="section-title" id="category-list-title">カテゴリ一覧</h2>
            <div class="content-block table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr class="table-row">
                            <th class="table-heading" scope="col">カテゴリ名</th>
                            <th class="table-heading" scope="col">状態</th>
                            <th class="table-heading" scope="col"><span class="text-span sr-only">操作</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $category)
                            <tr class="table-row">
                                <td class="table-cell">{{ $category->name }}</td>
                                <td class="table-cell">{{ $category->is_active ? '有効' : '無効' }}</td>
                                <td class="table-cell">
                                    <details>
                                        <summary class="summary-toggle action-button">編集</summary>
                                        <form class="form-container category-form" action="{{ route('categories.update', $category) }}" method="post">
                                            @csrf
                                            @method('put')
                                            <x-form-field id="name-{{ $category->id }}" name="name" label="カテゴリ名" :value="$category->name" required />
                                            <div class="content-block form-group">
                                                <label class="field-label form-label" for="is-active-{{ $category->id }}">状態</label>
                                                <select class="form-element form-control" id="is-active-{{ $category->id }}" name="is_active" required>
                                                    <option value="1" @selected($category->is_active)>有効</option>
                                                    <option value="0" @selected(! $category->is_active)>無効</option>
                                                </select>
                                            </div>
                                            <button class="form-element button button-inline" type="submit">更新する</button>
                                        </form>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr class="table-row">
                                <td class="table-cell" colspan="3">カテゴリが登録されていません。</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </section>
@endsection
