@extends('layouts.app')

@section('stylesheet', 'css/shared/transaction-index.css')


@section('title', '仕入伝票一覧')

@section('content')
    <section class="dashboard-main">
        <div class="content-block page-heading"><h1 class="page-title">仕入伝票一覧</h1><a class="page-link button button-link" href="{{ route('purchases.create') }}">仕入伝票登録</a></div>
        <form class="form-container filter-form" action="{{ route('purchases.index') }}" method="get">
            <div class="content-block form-group"><label class="field-label form-label" for="purchase-number">伝票番号</label><input id="purchase-number" class="form-element form-control" name="purchase_number" value="{{ request('purchase_number') }}"></div>
            <div class="content-block form-group"><label class="field-label form-label" for="supplier-id">仕入先</label><select id="supplier-id" class="form-element form-control" name="supplier_id"><option value="">すべて</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected((string) $supplier->id === request('supplier_id'))>{{ $supplier->name }}</option>@endforeach</select></div>
            <div class="content-block form-group"><label class="field-label form-label" for="status">状態</label><select id="status" class="form-element form-control" name="status"><option value="">すべて</option><option value="draft" @selected(request('status') === 'draft')>下書き</option><option value="confirmed" @selected(request('status') === 'confirmed')>確定済み</option><option value="cancelled" @selected(request('status') === 'cancelled')>取消済み</option></select></div>
            <div class="content-block form-group"><label class="field-label form-label" for="date-from">仕入日（開始）</label><input id="date-from" class="form-element form-control" name="date_from" type="date" value="{{ request('date_from') }}"></div>
            <div class="content-block form-group"><label class="field-label form-label" for="date-to">仕入日（終了）</label><input id="date-to" class="form-element form-control" name="date_to" type="date" value="{{ request('date_to') }}"></div>
            <button class="form-element button button-inline" type="submit">検索</button>
        </form>
        <div class="content-block table-wrapper"><table class="data-table"><thead><tr class="table-row"><th class="table-heading">伝票番号</th><th class="table-heading">仕入日</th><th class="table-heading">仕入先</th><th class="table-heading">合計金額</th><th class="table-heading">状態</th><th class="table-heading">登録者</th><th class="table-heading"></th></tr></thead><tbody>
            @forelse($purchases as $purchase)<tr class="table-row"><td class="table-cell">{{ $purchase->purchase_number }}</td><td class="table-cell">{{ $purchase->purchase_date->format('Y/m/d') }}</td><td class="table-cell">{{ $purchase->supplier->name }}</td><td class="table-cell">{{ number_format((float) $purchase->total_amount) }} 円</td><td class="table-cell">{{ $purchase->statusLabel() }}</td><td class="table-cell">{{ $purchase->creator->name }}</td><td class="table-cell"><a class="page-link action-button" href="{{ route('purchases.show', $purchase) }}">詳細</a></td></tr>
            @empty<tr class="table-row"><td class="table-cell" colspan="7">仕入伝票がありません。</td></tr>@endforelse
        </tbody></table></div>
        <x-pagination :paginator="$purchases" />
    </section>
@endsection
