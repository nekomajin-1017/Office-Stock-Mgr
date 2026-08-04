@extends('layouts.app')

@section('stylesheet', 'css/stocks/movements.css')


@section('title', '在庫移動履歴')

@section('content')
    <section class="dashboard-main">
        <div class="content-block page-heading">
            <div class="content-block">
                <h1 class="page-title">在庫移動履歴</h1>
                <p class="text-content page-description">{{ $product->code }} / {{ $product->name }}</p>
            </div>
            <a class="page-link action-button" href="{{ route('stocks.index') }}">在庫一覧へ戻る</a>
        </div>

        <div class="content-block table-wrapper">
            <table class="data-table">
                <thead>
                    <tr class="table-row">
                        <th class="table-heading" scope="col">処理日時</th>
                        <th class="table-heading" scope="col">入出庫区分</th>
                        <th class="table-heading" scope="col">数量変動</th>
                        <th class="table-heading" scope="col">変動後在庫数</th>
                        <th class="table-heading" scope="col">関連伝票番号</th>
                        <th class="table-heading" scope="col">担当者</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movements as $movement)
                        <tr class="table-row">
                            <td class="table-cell">{{ $movement->occurred_at->format('Y年m月d日 H:i') }}</td>
                            <td class="table-cell">{{ $movement->quantity_change >= 0 ? '入庫' : '出庫' }}</td>
                            <td class="table-cell">{{ $movement->quantity_change > 0 ? '+' : '' }}{{ number_format($movement->quantity_change) }}</td>
                            <td class="table-cell">{{ number_format($movement->quantity_after) }} {{ $product->unit }}</td>
                            <td class="table-cell">{{ $movement->referenceNumber() }}</td>
                            <td class="table-cell">{{ $movement->creator->name }}</td>
                        </tr>
                    @empty
                        <tr class="table-row">
                            <td class="table-cell" colspan="6">在庫移動履歴はありません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
