@extends('layouts.app')

@section('stylesheet', 'css/stocks/index.css')


@section('title', '在庫一覧')

@section('content')
    <section class="dashboard-main">
        <div class="content-block page-heading">
            <h1 class="page-title">在庫一覧</h1>
            <p class="text-content inventory-total">在庫評価額合計: {{ number_format($totalInventoryValue, 2) }} 円</p>
        </div>

        <form class="form-container filter-form" action="{{ route('stocks.index') }}" method="get">
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
            <label class="field-label checkbox-label" for="shortage-only">
                <input class="form-element" id="shortage-only" name="shortage_only" type="checkbox" value="1" @checked(request()->boolean('shortage_only'))>
                在庫不足のみ
            </label>
            <button class="form-element button button-inline" type="submit">検索</button>
        </form>

        <div class="content-block table-wrapper">
            <table class="data-table">
                <thead>
                    <tr class="table-row">
                        <th class="table-heading" scope="col">商品コード</th>
                        <th class="table-heading" scope="col">商品名</th>
                        <th class="table-heading" scope="col">カテゴリ</th>
                        <th class="table-heading" scope="col">現在庫数</th>
                        <th class="table-heading" scope="col">平均原価</th>
                        <th class="table-heading" scope="col">在庫評価額</th>
                        <th class="table-heading" scope="col">発注基準数</th>
                        <th class="table-heading" scope="col">発注要否</th>
                        <th class="table-heading" scope="col"><span class="text-span sr-only">操作</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($stocks as $stock)
                        @php($isShortage = $stock->quantity <= $stock->product->reorder_level)
                        <tr @class(['table-row','is-shortage' => $isShortage])>
                            <td class="table-cell">{{ $stock->product->code }}</td>
                            <td class="table-cell">{{ $stock->product->name }}</td>
                            <td class="table-cell">{{ $stock->product->category->name }}</td>
                            <td class="table-cell">{{ number_format($stock->quantity) }} {{ $stock->product->unit }}</td>
                            <td class="table-cell">{{ number_format((float) $stock->average_cost, 2) }} 円</td>
                            <td class="table-cell">{{ number_format($stock->inventoryValue(), 2) }} 円</td>
                            <td class="table-cell">{{ number_format($stock->product->reorder_level) }} {{ $stock->product->unit }}</td>
                            <td class="table-cell">{{ $isShortage ? '要発注' : '不要' }}</td>
                            <td class="table-cell"><a class="page-link action-button" href="{{ route('stocks.movements', $stock->product) }}">履歴</a></td>
                        </tr>
                    @empty
                        <tr class="table-row">
                            <td class="table-cell" colspan="9">該当する在庫はありません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-pagination :paginator="$stocks" />
    </section>
@endsection
