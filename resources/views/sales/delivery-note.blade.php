<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>納品書 {{ $sale->sale_number }}</title>
    <link rel="stylesheet" href="{{ public_path('css/sales/delivery-note.css') }}">
</head>
<body class="page-body">
    <header class="document-header">
        <h1 class="page-title document-title">納品書</h1>
    </header>

    <table class="document-meta">
        <tr class="table-row">
            <td class="table-cell">
                <span class="text-span meta-label">納品先</span>
                <p class="text-content customer-name">{{ $sale->customer->name }} 様</p>
                @if ($sale->customer->postal_code || $sale->customer->address)
                    <div class="content-block">{{ $sale->customer->postal_code ? '〒'.$sale->customer->postal_code : '' }}</div>
                    <div class="content-block">{{ $sale->customer->address }}</div>
                @endif
            </td>
            <td class="table-cell document-number">
                <div class="content-block"><span class="text-span meta-label">伝票番号</span><br>{{ $sale->sale_number }}</div>
                <div class="content-block document-date"><span class="text-span meta-label">発行日</span><br>{{ $issuedAt->format('Y年m月d日') }}</div>
                <div class="content-block document-date"><span class="text-span meta-label">販売日</span><br>{{ $sale->sale_date->format('Y年m月d日') }}</div>
            </td>
        </tr>
    </table>

    <table class="item-table">
        <thead>
            <tr class="table-row">
                <th class="table-heading product-column">商品名</th>
                <th class="table-heading number quantity-column">数量</th>
                <th class="table-heading number price-column">販売単価</th>
                <th class="table-heading number subtotal-column">小計</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $item)
                <tr class="table-row">
                    <td class="table-cell">{{ $item->product->name }}</td>
                    <td class="table-cell number">{{ number_format($item->quantity) }}</td>
                    <td class="table-cell number">{{ number_format((float) $item->unit_price) }} 円</td>
                    <td class="table-cell number">{{ number_format((float) $item->subtotal) }} 円</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary">
        <tr class="table-row">
            <th class="table-heading">合計金額</th>
            <td class="table-cell">{{ number_format((float) $sale->total_amount) }} 円</td>
        </tr>
    </table>

    <section class="notes">
        <h2 class="section-title">備考</h2>
        <p class="text-content">上記のとおり納品いたしました。</p>
    </section>

    <footer class="document-footer">
        発行元: {{ config('app.name') }} | お問い合わせは担当者までご連絡ください。
    </footer>
</body>
</html>
