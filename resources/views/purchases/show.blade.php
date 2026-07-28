@extends('layouts.app')

@section('title', '仕入伝票詳細')

@section('content')
  <section class="dashboard-main">
    <div class="page-heading">
      <h1>仕入伝票詳細</h1>
      @if ($purchase->isDraft())
        <form action="{{ route('purchases.confirm', $purchase) }}" method="post" onsubmit="return confirm('この仕入伝票を確定します。確定後は変更できません。');">
          @csrf
          <button class="action-button" type="submit">仕入を確定する</button>
        </form>
      @endif
    </div>

    @if (session('status'))
      <p class="success-message">{{ session('status') }}</p>
    @endif

    @error('purchase')
      <p class="field-error">{{ $message }}</p>
    @enderror

    <dl class="detail-list">
      <div>
        <dt>伝票番号</dt>
        <dd>{{ $purchase->purchase_number }}</dd>
      </div>
      <div>
        <dt>仕入先</dt>
        <dd>{{ $purchase->supplier->name }}</dd>
      </div>
      <div>
        <dt>仕入日</dt>
        <dd>{{ $purchase->purchase_date->format('Y年m月d日') }}</dd>
      </div>
      <div>
        <dt>状態</dt>
        <dd>{{ $purchase->isDraft() ? '下書き' : '確定済み' }}</dd>
      </div>
      @if ($purchase->confirmed_at)
        <div>
          <dt>確定日時</dt>
          <dd>{{ $purchase->confirmed_at->format('Y年m月d日 H:i') }}</dd>
        </div>
        <div>
          <dt>確定者</dt>
          <dd>{{ $purchase->confirmer?->name }}</dd>
        </div>
      @endif
    </dl>

    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th scope="col">商品</th>
            <th scope="col">数量</th>
            <th scope="col">仕入単価</th>
            <th scope="col">小計</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($purchase->items as $item)
            <tr>
              <td>{{ $item->product->name }}</td>
              <td>{{ number_format($item->quantity) }}</td>
              <td>{{ number_format((float) $item->unit_price, 2) }} 円</td>
              <td>{{ number_format((float) $item->subtotal, 2) }} 円</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <p>伝票合計: {{ number_format((float) $purchase->total_amount, 2) }} 円</p>
    <p><a href="{{ route('purchases.index') }}">一覧へ戻る</a></p>
  </section>
@endsection
