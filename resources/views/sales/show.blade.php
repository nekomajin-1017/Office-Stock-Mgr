@extends('layouts.app')

@section('stylesheet', 'css/shared/transaction-show.css')


@section('title', '販売伝票詳細')

@section('content')
    <section class="dashboard-main">
        <div class="content-block page-heading">
            <h1 class="page-title">販売伝票詳細</h1>
            @if ($sale->isDraft())
                <div class="content-block detail-actions">
                    <a class="page-link action-button" href="{{ route('sales.edit', $sale) }}">編集</a>
                    <form class="form-container" action="{{ route('sales.destroy', $sale) }}" method="post" onsubmit="return confirm('この下書き伝票を削除します。よろしいですか？');">
                        @csrf
                        @method('delete')
                        <button class="form-element action-button action-button-danger" type="submit">削除</button>
                    </form>
                    <form class="form-container" action="{{ route('sales.confirm', $sale) }}" method="post" onsubmit="return confirm('販売伝票を確定します。');">
                        @csrf
                        <button class="form-element action-button" type="submit">販売を確定する</button>
                    </form>
                </div>
            @elseif ($sale->isConfirmed())
                <div class="content-block detail-actions">
                    <a class="page-link action-button" href="{{ route('sales.delivery-note', $sale) }}">納品書PDFを出力</a>
                    @if (auth()->user()->isAdmin())
                        <form class="form-container" action="{{ route('sales.correct', $sale) }}" method="post" onsubmit="return confirm('確定を解除して在庫を戻し、訂正画面へ進みます。よろしいですか？');">
                            @csrf
                            <button class="form-element action-button" type="submit">訂正する</button>
                        </form>
                        <form class="form-container cancel-form" action="{{ route('sales.cancel', $sale) }}" method="post" onsubmit="return confirm('この販売伝票を取り消します。よろしいですか？');">
                            @csrf
                            <label class="field-label form-label" for="sale-cancellation-reason">取消理由</label>
                            <input id="sale-cancellation-reason" class="form-element form-control" name="reason" maxlength="255" required>
                            <button class="form-element action-button action-button-danger" type="submit">取り消す</button>
                        </form>
                    @endif
                </div>
            @endif
        </div>

        @if (session('status'))
            <p class="text-content success-message">{{ session('status') }}</p>
        @endif

        @error('sale')
            <p class="text-content field-error">{{ $message }}</p>
        @enderror

        <dl class="detail-list">
            <div class="content-block"><dt class="detail-term">伝票番号</dt><dd class="detail-description">{{ $sale->sale_number }}</dd></div>
            <div class="content-block"><dt class="detail-term">顧客</dt><dd class="detail-description">{{ $sale->customer->name }}</dd></div>
            <div class="content-block"><dt class="detail-term">販売日</dt><dd class="detail-description">{{ $sale->sale_date->format('Y/m/d') }}</dd></div>
            <div class="content-block"><dt class="detail-term">状態</dt><dd class="detail-description">{{ $sale->statusLabel() }}</dd></div>
            @if ($sale->confirmed_at)
                <div class="content-block"><dt class="detail-term">確定日時</dt><dd class="detail-description">{{ $sale->confirmed_at->format('Y年m月d日 H:i') }}</dd></div>
                <div class="content-block"><dt class="detail-term">確定者</dt><dd class="detail-description">{{ $sale->confirmer?->name }}</dd></div>
            @endif
            @if ($sale->isCancelled())
                <div class="content-block"><dt class="detail-term">取消日時</dt><dd class="detail-description">{{ $sale->cancelled_at?->format('Y年m月d日 H:i') }}</dd></div>
                <div class="content-block"><dt class="detail-term">取消者</dt><dd class="detail-description">{{ $sale->canceller?->name }}</dd></div>
                <div class="content-block"><dt class="detail-term">取消理由</dt><dd class="detail-description">{{ $sale->cancellation_reason }}</dd></div>
            @endif
        </dl>

        <div class="content-block table-wrapper">
            <table class="data-table">
                <thead>
                    <tr class="table-row"><th class="table-heading">商品</th><th class="table-heading">数量</th><th class="table-heading">販売単価</th><th class="table-heading">小計</th></tr>
                </thead>
                <tbody>
                    @foreach ($sale->items as $item)
                        <tr class="table-row">
                            <td class="table-cell">{{ $item->product->name }}</td>
                            <td class="table-cell">{{ $item->quantity }}</td>
                            <td class="table-cell">{{ number_format((float) $item->unit_price) }} 円</td>
                            <td class="table-cell">{{ number_format((float) $item->subtotal) }} 円</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="text-content">伝票合計: {{ number_format((float) $sale->total_amount) }} 円</p>
        <a class="page-link" href="{{ route('sales.index') }}">一覧へ戻る</a>
    </section>
@endsection
