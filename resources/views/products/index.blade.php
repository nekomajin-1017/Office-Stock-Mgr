@extends('layouts.app')

@section('stylesheet', 'css/shared/entity-index.css')


@section('title', '商品管理')

@section('content')
    <section class="dashboard-main">
        <div class="content-block page-heading">
            <h1 class="page-title">商品管理</h1>
            <a class="page-link button button-link" href="{{ route('products.create') }}">商品登録</a>
        </div>
        @if (session('status'))
            <p class="text-content success-message">{{ session('status') }}</p>
        @endif
        <form class="form-container filter-form" action="{{ route('products.index') }}" method="get">
            <div class="content-block form-group">
                <label class="field-label form-label" for="keyword">商品コード・商品名</label>
                <input id="keyword" class="form-element form-control" name="keyword" type="search" value="{{ request('keyword') }}">
            </div>
            <div class="content-block form-group">
                <label class="field-label form-label" for="category-id">カテゴリ</label>
                <select id="category-id" class="form-element form-control" name="category_id">
                    <option value="">すべて</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) $category->id === request('category_id'))>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="content-block form-group">
                <label class="field-label form-label" for="is-active">状態</label>
                <select id="is-active" class="form-element form-control" name="is_active">
                    <option value="">すべて</option>
                    <option value="1" @selected(request('is_active') === '1')>有効</option>
                    <option value="0" @selected(request('is_active') === '0')>無効</option>
                </select>
            </div>
            <button class="form-element button button-inline" type="submit">検索</button>
        </form>
        <div class="content-block table-wrapper">
            <table class="data-table">
                <thead>
                    <tr class="table-row">
                        <th class="table-heading" scope="col">商品コード</th>
                        <th class="table-heading" scope="col">商品名</th>
                        <th class="table-heading" scope="col">カテゴリ</th>
                        <th class="table-heading" scope="col">在庫数</th>
                        <th class="table-heading" scope="col">状態</th>
                        <th class="table-heading" scope="col"><span class="text-span sr-only">操作</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr class="table-row">
                            <td class="table-cell"><a class="page-link" href="{{ route('products.show', $product) }}">{{ $product->code }}</a></td>
                            <td class="table-cell">{{ $product->name }}</td>
                            <td class="table-cell">{{ $product->category->name }}</td>
                            <td class="table-cell">{{ $product->stock?->quantity ?? 0 }}</td>
                            <td class="table-cell">{{ $product->is_active ? '有効' : '無効' }}</td>
                            <td class="table-cell">
                                <a class="page-link action-button" href="{{ route('products.edit', $product) }}">編集</a>
                                @if ($product->is_active)
                                    <form class="form-container inline-form" action="{{ route('products.destroy', $product) }}" method="post">
                                        @csrf
                                        @method('delete')
                                        <button class="form-element action-button action-button-danger" type="submit">無効化</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr class="table-row">
                            <td class="table-cell" colspan="6">商品が登録されていません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-pagination :paginator="$products" />
    </section>
@endsection
