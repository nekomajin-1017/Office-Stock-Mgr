@extends('layouts.app')

@section('title', '在庫一覧')

@section('content')
    <section class="dashboard-main">
        <div class="page-heading">
            <h1>在庫一覧</h1>
            <p class="inventory-total">在庫評価額合計: {{ number_format($totalInventoryValue, 2) }} 円</p>
        </div>

        <form class="filter-form" action="{{ route('stocks.index') }}" method="get">
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
            <label class="checkbox-label" for="shortage-only">
                <input id="shortage-only" name="shortage_only" type="checkbox" value="1" @checked(request()->boolean('shortage_only'))>
                在庫不足のみ
            </label>
            <button class="button button-inline" type="submit">検索</button>
        </form>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">商品コード</th>
                        <th scope="col">商品名</th>
                        <th scope="col">カテゴリ</th>
                        <th scope="col">現在庫数</th>
                        <th scope="col">平均原価</th>
                        <th scope="col">在庫評価額</th>
                        <th scope="col">発注基準数</th>
                        <th scope="col">発注要否</th>
                        <th scope="col"><span class="sr-only">操作</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stocks as $stock)
                        @php($isShortage = $stock->quantity <= $stock->product->reorder_level)
                        <tr @class(['is-shortage' => $isShortage])>
                            <td>{{ $stock->product->code }}</td>
                            <td>{{ $stock->product->name }}</td>
                            <td>{{ $stock->product->category->name }}</td>
                            <td>{{ number_format($stock->quantity) }} {{ $stock->product->unit }}</td>
                            <td>{{ number_format((float) $stock->average_cost, 2) }} 円</td>
                            <td>{{ number_format($stock->inventoryValue(), 2) }} 円</td>
                            <td>{{ number_format($stock->product->reorder_level) }} {{ $stock->product->unit }}</td>
                            <td>{{ $isShortage ? '要発注' : '不要' }}</td>
                            <td><a class="action-button" href="{{ route('stocks.movements', $stock->product) }}">履歴</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">該当する在庫はありません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-pagination :paginator="$stocks" />
    </section>
@endsection
