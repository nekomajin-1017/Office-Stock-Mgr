@extends('layouts.app')

@section('title', '商品詳細')

@section('content')
    @php
        $stock = $product->stock;
        $quantity = $stock?->quantity ?? 0;
        $averageCost = $stock?->average_cost ?? 0;
        $inventoryValue = $stock?->inventoryValue() ?? 0;
    @endphp
    <section class="dashboard-main">
        <div class="page-heading">
            <h1>商品詳細</h1>
            <a class="action-button" href="{{ route('products.edit', $product) }}">編集する</a>
        </div>
        <dl class="detail-list">
            <div>
                <dt>商品コード</dt>
                <dd>{{ $product->code }}</dd>
            </div>
            <div>
                <dt>商品名</dt>
                <dd>{{ $product->name }}</dd>
            </div>
            <div>
                <dt>カテゴリ</dt>
                <dd>{{ $product->category->name }}</dd>
            </div>
            <div>
                <dt>現在庫</dt>
                <dd>{{ number_format($quantity) }}</dd>
            </div>
            <div>
                <dt>平均仕入単価</dt>
                <dd>{{ number_format((float) $averageCost, 2) }} 円</dd>
            </div>
            <div>
                <dt>在庫評価額</dt>
                <dd>{{ number_format($inventoryValue, 2) }} 円</dd>
            </div>
            <div>
                <dt>状態</dt>
                <dd>{{ $product->is_active ? '有効' : '無効' }}</dd>
            </div>
        </dl>
        <p><a href="{{ route('products.index') }}">一覧へ戻る</a></p>
    </section>
@endsection
