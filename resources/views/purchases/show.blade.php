@extends('layouts.app')

@section('stylesheet', 'css/shared/transaction-show.css')


@section('title', '仕入伝票詳細')

@section('content')
    <section class="dashboard-main">
        <div class="content-block page-heading">
            <h1 class="page-title">仕入伝票詳細</h1>
            @if ($purchase->isDraft())
                <div class="content-block detail-actions">
                    <a class="page-link action-button" href="{{ route('purchases.edit', $purchase) }}">編集</a>
                    <form class="form-container" action="{{ route('purchases.destroy', $purchase) }}" method="post" onsubmit="return confirm('この下書き伝票を削除します。よろしいですか？');">
                        @csrf
                        @method('delete')
                        <button class="form-element action-button action-button-danger" type="submit">削除</button>
                    </form>
                    <form class="form-container" action="{{ route('purchases.confirm', $purchase) }}" method="post" onsubmit="return confirm('この仕入伝票を確定します。');">
                        @csrf
                        <button class="form-element action-button" type="submit">仕入を確定する</button>
                    </form>
                </div>
            @elseif ($purchase->isConfirmed() && auth()->user()->isAdmin())
                <div class="content-block detail-actions">
                    <form class="form-container" action="{{ route('purchases.correct', $purchase) }}" method="post" onsubmit="return confirm('確定を解除して在庫を戻し、訂正画面へ進みます。よろしいですか？');">
                        @csrf
                        <button class="form-element action-button" type="submit">訂正する</button>
                    </form>
                    <form class="form-container cancel-form" action="{{ route('purchases.cancel', $purchase) }}" method="post" onsubmit="return confirm('この仕入伝票を取り消します。よろしいですか？');">
                        @csrf
                        <label class="field-label form-label" for="purchase-cancellation-reason">取消理由</label>
                        <input id="purchase-cancellation-reason" class="form-element form-control" name="reason" maxlength="255" required>
                        <button class="form-element action-button action-button-danger" type="submit">取り消す</button>
                    </form>
                </div>
            @endif
        </div>

        @if (session('status'))
            <p class="text-content success-message">{{ session('status') }}</p>
        @endif

        @error('purchase')
            <p class="text-content field-error">{{ $message }}</p>
        @enderror

        <dl class="detail-list">
            <div class="content-block">
                <dt class="detail-term">伝票番号</dt>
                <dd class="detail-description">{{ $purchase->purchase_number }}</dd>
            </div>
            <div class="content-block">
                <dt class="detail-term">仕入先</dt>
                <dd class="detail-description">{{ $purchase->supplier->name }}</dd>
            </div>
            <div class="content-block">
                <dt class="detail-term">仕入日</dt>
                <dd class="detail-description">{{ $purchase->purchase_date->format('Y年m月d日') }}</dd>
            </div>
            <div class="content-block">
                <dt class="detail-term">状態</dt>
                <dd class="detail-description">{{ $purchase->statusLabel() }}</dd>
            </div>
            @if ($purchase->confirmed_at)
                <div class="content-block">
                    <dt class="detail-term">確定日時</dt>
                    <dd class="detail-description">{{ $purchase->confirmed_at->format('Y年m月d日 H:i') }}</dd>
                </div>
                <div class="content-block">
                    <dt class="detail-term">確定者</dt>
                    <dd class="detail-description">{{ $purchase->confirmer?->name }}</dd>
                </div>
            @endif
            @if ($purchase->isCancelled())
                <div class="content-block">
                    <dt class="detail-term">取消日時</dt>
                    <dd class="detail-description">{{ $purchase->cancelled_at?->format('Y年m月d日 H:i') }}</dd>
                </div>
                <div class="content-block">
                    <dt class="detail-term">取消者</dt>
                    <dd class="detail-description">{{ $purchase->canceller?->name }}</dd>
                </div>
                <div class="content-block">
                    <dt class="detail-term">取消理由</dt>
                    <dd class="detail-description">{{ $purchase->cancellation_reason }}</dd>
                </div>
            @endif
        </dl>

        <div class="content-block table-wrapper">
            <table class="data-table">
                <thead>
                    <tr class="table-row">
                        <th class="table-heading" scope="col">商品</th>
                        <th class="table-heading" scope="col">数量</th>
                        <th class="table-heading" scope="col">仕入単価</th>
                        <th class="table-heading" scope="col">小計</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($purchase->items as $item)
                        <tr class="table-row">
                            <td class="table-cell">{{ $item->product->name }}</td>
                            <td class="table-cell">{{ number_format($item->quantity) }}</td>
                            <td class="table-cell">{{ number_format((float) $item->unit_price) }} 円</td>
                            <td class="table-cell">{{ number_format((float) $item->subtotal) }} 円</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="text-content">伝票合計: {{ number_format((float) $purchase->total_amount) }} 円</p>
        <p class="text-content"><a class="page-link" href="{{ route('purchases.index') }}">一覧へ戻る</a></p>
    </section>
@endsection
