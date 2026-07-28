@extends('layouts.app')

@section('title', '商品管理')

@section('content')
    <section class="dashboard-main">
        <div class="page-heading">
            <h1>商品管理</h1>
            <a class="button button-link" href="{{ route('products.create') }}">商品登録</a>
        </div>
        @if (session('status'))
            <p class="success-message">{{ session('status') }}</p>
        @endif
        <form class="filter-form" action="{{ route('products.index') }}" method="get">
            <div class="form-group">
                <label class="form-label" for="keyword">商品コード・商品名</label>
                <input id="keyword" class="form-control" name="keyword" type="search" value="{{ request('keyword') }}">
            </div>
            <div class="form-group">
                <label class="form-label" for="category-id">カテゴリ</label>
                <select id="category-id" class="form-control" name="category_id">
                    <option value="">すべて</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) $category->id === request('category_id'))>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="is-active">状態</label>
                <select id="is-active" class="form-control" name="is_active">
                    <option value="">すべて</option>
                    <option value="1" @selected(request('is_active') === '1')>有効</option>
                    <option value="0" @selected(request('is_active') === '0')>無効</option>
                </select>
            </div>
            <button class="button button-inline" type="submit">検索</button>
        </form>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">商品コード</th>
                        <th scope="col">商品名</th>
                        <th scope="col">カテゴリ</th>
                        <th scope="col">在庫数</th>
                        <th scope="col">状態</th>
                        <th scope="col"><span class="sr-only">操作</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td><a href="{{ route('products.show', $product) }}">{{ $product->code }}</a></td>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->category->name }}</td>
                            <td>{{ $product->stock?->quantity ?? 0 }}</td>
                            <td>{{ $product->is_active ? '有効' : '無効' }}</td>
                            <td>
                                <a class="action-button" href="{{ route('products.edit', $product) }}">編集</a>
                                @if ($product->is_active)
                                    <form class="inline-form" action="{{ route('products.destroy', $product) }}" method="post">
                                        @csrf
                                        @method('delete')
                                        <button class="action-button action-button-danger" type="submit">無効化</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">商品が登録されていません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-pagination :paginator="$products" />
    </section>
@endsection
