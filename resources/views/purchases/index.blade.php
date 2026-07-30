@extends('layouts.app')

@section('title', '仕入伝票一覧')

@section('content')
    <section class="dashboard-main">
        <div class="page-heading"><h1>仕入伝票一覧</h1><a class="button button-link" href="{{ route('purchases.create') }}">仕入伝票登録</a></div>
        <form class="filter-form" action="{{ route('purchases.index') }}" method="get">
            <div class="form-group"><label class="form-label" for="purchase-number">伝票番号</label><input id="purchase-number" class="form-control" name="purchase_number" value="{{ request('purchase_number') }}"></div>
            <div class="form-group"><label class="form-label" for="supplier-id">仕入先</label><select id="supplier-id" class="form-control" name="supplier_id"><option value="">すべて</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected((string) $supplier->id === request('supplier_id'))>{{ $supplier->name }}</option>@endforeach</select></div>
            <div class="form-group"><label class="form-label" for="status">状態</label><select id="status" class="form-control" name="status"><option value="">すべて</option><option value="draft" @selected(request('status') === 'draft')>下書き</option><option value="confirmed" @selected(request('status') === 'confirmed')>確定済み</option><option value="cancelled" @selected(request('status') === 'cancelled')>取消済み</option></select></div>
            <div class="form-group"><label class="form-label" for="date-from">仕入日（開始）</label><input id="date-from" class="form-control" name="date_from" type="date" value="{{ request('date_from') }}"></div>
            <div class="form-group"><label class="form-label" for="date-to">仕入日（終了）</label><input id="date-to" class="form-control" name="date_to" type="date" value="{{ request('date_to') }}"></div>
            <button class="button button-inline" type="submit">検索</button>
        </form>
        <div class="table-wrapper"><table class="data-table"><thead><tr><th>伝票番号</th><th>仕入日</th><th>仕入先</th><th>合計金額</th><th>状態</th><th>登録者</th><th></th></tr></thead><tbody>
            @forelse($purchases as $purchase)<tr><td>{{ $purchase->purchase_number }}</td><td>{{ $purchase->purchase_date->format('Y/m/d') }}</td><td>{{ $purchase->supplier->name }}</td><td>{{ number_format((float) $purchase->total_amount) }} 円</td><td>{{ $purchase->statusLabel() }}</td><td>{{ $purchase->creator->name }}</td><td><a class="action-button" href="{{ route('purchases.show', $purchase) }}">詳細</a></td></tr>
            @empty<tr><td colspan="7">仕入伝票がありません。</td></tr>@endforelse
        </tbody></table></div>
        <x-pagination :paginator="$purchases" />
    </section>
@endsection
