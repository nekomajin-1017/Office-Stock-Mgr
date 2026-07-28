@extends('layouts.app')

@section('title', '在庫移動履歴')

@section('content')
  <section class="dashboard-main">
    <div class="page-heading">
      <div>
        <h1>在庫移動履歴</h1>
        <p class="page-description">{{ $product->code }} / {{ $product->name }}</p>
      </div>
      <a class="action-button" href="{{ route('stocks.index') }}">在庫一覧へ戻る</a>
    </div>

    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th scope="col">処理日時</th>
            <th scope="col">入出庫区分</th>
            <th scope="col">数量変動</th>
            <th scope="col">変動後在庫数</th>
            <th scope="col">関連伝票番号</th>
            <th scope="col">担当者</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($movements as $movement)
            <tr>
              <td>{{ $movement->occurred_at->format('Y年m月d日 H:i') }}</td>
              <td>{{ $movement->quantity_change >= 0 ? '入庫' : '出庫' }}</td>
              <td>{{ $movement->quantity_change > 0 ? '+' : '' }}{{ number_format($movement->quantity_change) }}</td>
              <td>{{ number_format($movement->quantity_after) }} {{ $product->unit }}</td>
              <td>{{ $movement->referenceNumber() }}</td>
              <td>{{ $movement->creator->name }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="6">在庫移動履歴はありません。</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>
@endsection
