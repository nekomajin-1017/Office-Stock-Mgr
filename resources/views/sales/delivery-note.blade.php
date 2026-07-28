<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>納品書 {{ $sale->sale_number }}</title>
    <style>
        @font-face {
            font-family: "IPAGothic";
            font-style: normal;
            font-weight: normal;
            src: url("file:///usr/share/fonts/opentype/ipafont-gothic/ipag.ttf") format("truetype");
        }

        @page {
            margin: 16mm 15mm 18mm;
        }

        * { box-sizing: border-box; }

        body {
            color: #172033;
            font-family: "IPAGothic", sans-serif;
            font-size: 10pt;
            line-height: 1.55;
        }

        .document-header { padding-bottom: 12px; border-bottom: 2px solid #1d4ed8; }
        .document-title { margin: 0; color: #172033; font-size: 24pt; font-weight: normal; letter-spacing: 0.12em; }
        .document-meta { width: 100%; margin-top: 14px; border-collapse: collapse; }
        .document-meta td { width: 50%; vertical-align: top; }
        .meta-label { color: #64748b; font-size: 8.5pt; }
        .customer-name { margin: 6px 0 0; font-size: 15pt; }
        .document-number { text-align: right; }
        .item-table { width: 100%; margin-top: 28px; border-collapse: collapse; }
        .item-table th, .item-table td { padding: 9px 8px; border: 1px solid #cbd5e1; }
        .item-table th { color: #334155; font-size: 9pt; font-weight: normal; text-align: left; background: #eff6ff; }
        .item-table .number { text-align: right; }
        .summary { width: 42%; margin: 18px 0 0 auto; border-collapse: collapse; }
        .summary th, .summary td { padding: 9px 10px; border: 1px solid #94a3b8; }
        .summary th { font-weight: normal; text-align: left; background: #f8fafc; }
        .summary td { font-size: 13pt; font-weight: normal; text-align: right; }
        .notes { margin-top: 32px; }
        .notes h2 { padding-bottom: 5px; margin: 0 0 8px; font-size: 10pt; font-weight: normal; border-bottom: 1px solid #cbd5e1; }
        .notes p { min-height: 32px; margin: 0; color: #475569; }
        .document-footer { position: fixed; right: 0; bottom: -10mm; left: 0; color: #64748b; font-size: 8pt; text-align: center; }
    </style>
</head>
<body>
    <header class="document-header">
        <h1 class="document-title">納品書</h1>
    </header>

    <table class="document-meta">
        <tr>
            <td>
                <span class="meta-label">納品先</span>
                <p class="customer-name">{{ $sale->customer->name }} 様</p>
                @if ($sale->customer->postal_code || $sale->customer->address)
                    <div>{{ $sale->customer->postal_code ? '〒'.$sale->customer->postal_code : '' }}</div>
                    <div>{{ $sale->customer->address }}</div>
                @endif
            </td>
            <td class="document-number">
                <div><span class="meta-label">伝票番号</span><br>{{ $sale->sale_number }}</div>
                <div style="margin-top: 8px;"><span class="meta-label">発行日</span><br>{{ $issuedAt->format('Y年m月d日') }}</div>
                <div style="margin-top: 8px;"><span class="meta-label">販売日</span><br>{{ $sale->sale_date->format('Y年m月d日') }}</div>
            </td>
        </tr>
    </table>

    <table class="item-table">
        <thead>
            <tr>
                <th style="width: 48%;">商品名</th>
                <th class="number" style="width: 14%;">数量</th>
                <th class="number" style="width: 19%;">販売単価</th>
                <th class="number" style="width: 19%;">小計</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $item)
                <tr>
                    <td>{{ $item->product->name }}</td>
                    <td class="number">{{ number_format($item->quantity) }}</td>
                    <td class="number">{{ number_format((float) $item->unit_price) }} 円</td>
                    <td class="number">{{ number_format((float) $item->subtotal) }} 円</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary">
        <tr>
            <th>合計金額</th>
            <td>{{ number_format((float) $sale->total_amount) }} 円</td>
        </tr>
    </table>

    <section class="notes">
        <h2>備考</h2>
        <p>上記のとおり納品いたしました。</p>
    </section>

    <footer class="document-footer">
        発行元: {{ config('app.name') }} | お問い合わせは担当者までご連絡ください。
    </footer>
</body>
</html>
