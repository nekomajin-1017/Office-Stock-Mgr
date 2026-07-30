@extends('layouts.app')

@section('title', '販売伝票詳細')

@section('content')
    <section class="dashboard-main">
        <div class="page-heading">
            <h1>販売伝票詳細</h1>
            @if ($sale->isDraft())
                <div class="detail-actions">
                    <a class="action-button" href="{{ route('sales.edit', $sale) }}">編集</a>
                    <form action="{{ route('sales.destroy', $sale) }}" method="post" onsubmit="return confirm('この下書き伝票を削除します。よろしいですか？');">
                        @csrf
                        @method('delete')
                        <button class="action-button action-button-danger" type="submit">削除</button>
                    </form>
                    <form action="{{ route('sales.confirm', $sale) }}" method="post" onsubmit="return confirm('販売伝票を確定します。');">
                        @csrf
                        <button class="action-button" type="submit">販売を確定する</button>
                    </form>
                </div>
            @elseif ($sale->isConfirmed())
                <div class="detail-actions">
                    <a class="action-button" href="{{ route('sales.delivery-note', $sale) }}">納品書PDFを出力</a>
                    @if (auth()->user()->isAdmin())
                        <form action="{{ route('sales.correct', $sale) }}" method="post" onsubmit="return confirm('確定を解除して在庫を戻し、訂正画面へ進みます。よろしいですか？');">
                            @csrf
                            <button class="action-button" type="submit">訂正する</button>
                        </form>
                        <form class="cancel-form" action="{{ route('sales.cancel', $sale) }}" method="post" onsubmit="return confirm('この販売伝票を取り消します。よろしいですか？');">
                            @csrf
                            <label class="form-label" for="sale-cancellation-reason">取消理由</label>
                            <input id="sale-cancellation-reason" class="form-control" name="reason" maxlength="255" required>
                            <button class="action-button action-button-danger" type="submit">取り消す</button>
                        </form>
                    @endif
                </div>
            @endif
        </div>

        @if (session('status'))
            <p class="success-message">{{ session('status') }}</p>
        @endif

        @error('sale')
            <p class="field-error">{{ $message }}</p>
        @enderror

        <dl class="detail-list">
            <div><dt>伝票番号</dt><dd>{{ $sale->sale_number }}</dd></div>
            <div><dt>顧客</dt><dd>{{ $sale->customer->name }}</dd></div>
            <div><dt>販売日</dt><dd>{{ $sale->sale_date->format('Y/m/d') }}</dd></div>
            <div><dt>状態</dt><dd>{{ $sale->statusLabel() }}</dd></div>
            @if ($sale->confirmed_at)
                <div><dt>確定日時</dt><dd>{{ $sale->confirmed_at->format('Y年m月d日 H:i') }}</dd></div>
                <div><dt>確定者</dt><dd>{{ $sale->confirmer?->name }}</dd></div>
            @endif
            @if ($sale->isCancelled())
                <div><dt>取消日時</dt><dd>{{ $sale->cancelled_at?->format('Y年m月d日 H:i') }}</dd></div>
                <div><dt>取消者</dt><dd>{{ $sale->canceller?->name }}</dd></div>
                <div><dt>取消理由</dt><dd>{{ $sale->cancellation_reason }}</dd></div>
            @endif
        </dl>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr><th>商品</th><th>数量</th><th>販売単価</th><th>小計</th></tr>
                </thead>
                <tbody>
                    @foreach ($sale->items as $item)
                        <tr>
                            <td>{{ $item->product->name }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ number_format((float) $item->unit_price) }} 円</td>
                            <td>{{ number_format((float) $item->subtotal) }} 円</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p>伝票合計: {{ number_format((float) $sale->total_amount) }} 円</p>
        <a href="{{ route('sales.index') }}">一覧へ戻る</a>
    </section>
@endsection
