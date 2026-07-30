@extends('layouts.app')

@section('title', '仕入伝票詳細')

@section('content')
    <section class="dashboard-main">
        <div class="page-heading">
            <h1>仕入伝票詳細</h1>
            @if ($purchase->isDraft())
                <div class="detail-actions">
                    <a class="action-button" href="{{ route('purchases.edit', $purchase) }}">編集</a>
                    <form action="{{ route('purchases.destroy', $purchase) }}" method="post" onsubmit="return confirm('この下書き伝票を削除します。よろしいですか？');">
                        @csrf
                        @method('delete')
                        <button class="action-button action-button-danger" type="submit">削除</button>
                    </form>
                    <form action="{{ route('purchases.confirm', $purchase) }}" method="post" onsubmit="return confirm('この仕入伝票を確定します。');">
                        @csrf
                        <button class="action-button" type="submit">仕入を確定する</button>
                    </form>
                </div>
            @elseif ($purchase->isConfirmed() && auth()->user()->isAdmin())
                <div class="detail-actions">
                    <form action="{{ route('purchases.correct', $purchase) }}" method="post" onsubmit="return confirm('確定を解除して在庫を戻し、訂正画面へ進みます。よろしいですか？');">
                        @csrf
                        <button class="action-button" type="submit">訂正する</button>
                    </form>
                    <form class="cancel-form" action="{{ route('purchases.cancel', $purchase) }}" method="post" onsubmit="return confirm('この仕入伝票を取り消します。よろしいですか？');">
                        @csrf
                        <label class="form-label" for="purchase-cancellation-reason">取消理由</label>
                        <input id="purchase-cancellation-reason" class="form-control" name="reason" maxlength="255" required>
                        <button class="action-button action-button-danger" type="submit">取り消す</button>
                    </form>
                </div>
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
                <dd>{{ $purchase->statusLabel() }}</dd>
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
            @if ($purchase->isCancelled())
                <div>
                    <dt>取消日時</dt>
                    <dd>{{ $purchase->cancelled_at?->format('Y年m月d日 H:i') }}</dd>
                </div>
                <div>
                    <dt>取消者</dt>
                    <dd>{{ $purchase->canceller?->name }}</dd>
                </div>
                <div>
                    <dt>取消理由</dt>
                    <dd>{{ $purchase->cancellation_reason }}</dd>
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
                            <td>{{ number_format((float) $item->unit_price) }} 円</td>
                            <td>{{ number_format((float) $item->subtotal) }} 円</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p>伝票合計: {{ number_format((float) $purchase->total_amount) }} 円</p>
        <p><a href="{{ route('purchases.index') }}">一覧へ戻る</a></p>
    </section>
@endsection
