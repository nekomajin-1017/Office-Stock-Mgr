@extends('layouts.app')
@section('title','販売伝票詳細')
@section('content')
<section class="dashboard-main"><h1>販売伝票詳細</h1><dl class="detail-list"><div><dt>伝票番号</dt><dd>{{ $sale->sale_number }}</dd></div><div><dt>顧客</dt><dd>{{ $sale->customer->name }}</dd></div><div><dt>販売日</dt><dd>{{ $sale->sale_date->format('Y/m/d') }}</dd></div><div><dt>状態</dt><dd>{{ $sale->status }}</dd></div></dl><div class="table-wrapper"><table class="data-table"><thead><tr><th>商品</th><th>数量</th><th>販売単価</th><th>小計</th></tr></thead><tbody>@foreach($sale->items as $item)<tr><td>{{ $item->product->name }}</td><td>{{ $item->quantity }}</td><td>{{ number_format((float)$item->unit_price,2) }} 円</td><td>{{ number_format((float)$item->subtotal,2) }} 円</td></tr>@endforeach</tbody></table></div><p>伝票合計: {{ number_format((float)$sale->total_amount,2) }} 円</p><a href="{{ route('sales.index') }}">一覧へ戻る</a></section>
@endsection
