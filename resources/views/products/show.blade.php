@extends('layouts.app')

@section('stylesheet', 'css/products/show.css')


@section('title', '商品詳細')

@section('content')
    @php
        $stock = $product->stock;
        $quantity = $stock?->quantity ?? 0;
        $averageCost = $stock?->average_cost ?? 0;
        $inventoryValue = $stock?->inventoryValue() ?? 0;
    @endphp
    <section class="dashboard-main">
        <div class="content-block page-heading">
            <h1 class="page-title">商品詳細</h1>
            <a class="page-link action-button" href="{{ route('products.edit', $product) }}">編集する</a>
        </div>
        <dl class="detail-list">
            <div class="content-block">
                <dt class="detail-term">商品コード</dt>
                <dd class="detail-description">{{ $product->code }}</dd>
            </div>
            <div class="content-block">
                <dt class="detail-term">商品名</dt>
                <dd class="detail-description">{{ $product->name }}</dd>
            </div>
            <div class="content-block">
                <dt class="detail-term">カテゴリ</dt>
                <dd class="detail-description">{{ $product->category->name }}</dd>
            </div>
            <div class="content-block">
                <dt class="detail-term">現在庫</dt>
                <dd class="detail-description">{{ number_format($quantity) }}</dd>
            </div>
            <div class="content-block">
                <dt class="detail-term">平均仕入単価</dt>
                <dd class="detail-description">{{ number_format((float) $averageCost, 2) }} 円</dd>
            </div>
            <div class="content-block">
                <dt class="detail-term">在庫評価額</dt>
                <dd class="detail-description">{{ number_format($inventoryValue, 2) }} 円</dd>
            </div>
            <div class="content-block">
                <dt class="detail-term">状態</dt>
                <dd class="detail-description">{{ $product->is_active ? '有効' : '無効' }}</dd>
            </div>
        </dl>
        <p class="text-content"><a class="page-link" href="{{ route('products.index') }}">一覧へ戻る</a></p>
    </section>
@endsection
