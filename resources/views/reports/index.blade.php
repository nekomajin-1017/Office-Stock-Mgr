@extends('layouts.app')

@section('title', 'レポート')

@section('content')
    <main class="dashboard-main report-page">
        <div class="page-heading">
            <div>
                <h1>レポート</h1>
                <p class="page-description">確定済みの仕入・販売データと現在庫を集計しています。</p>
            </div>
        </div>

        <form method="get" class="filter-form report-filter">
            <label class="form-group">
                <span class="form-label">開始日</span>
                <input class="form-control" name="from" type="date" value="{{ $startDate }}">
            </label>
            <label class="form-group">
                <span class="form-label">終了日</span>
                <input class="form-control" name="to" type="date" value="{{ $endDate }}">
            </label>
            <label class="form-group">
                <span class="form-label">ランキング表示件数</span>
                <input class="form-control" name="limit" type="number" min="1" max="100" value="{{ $limit }}">
            </label>
            <button type="submit" class="button">条件を反映</button>
        </form>

        <p class="report-period">販売集計期間: {{ $startDate ?: '開始日指定なし' }} 〜 {{ $endDate ?: '終了日指定なし' }}</p>

        <section class="report-section">
            <div class="report-section-heading">
                <div>
                    <h2>未販売商品</h2>
                    <p>確定済みの販売明細が一度もない有効商品です。</p>
                </div>
            </div>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr><th>商品コード</th><th>商品名</th></tr></thead>
                    <tbody>
                        @forelse ($unsoldProducts as $product)
                            <tr><td>{{ $product->code }}</td><td>{{ $product->name }}</td></tr>
                        @empty
                            <tr><td colspan="2">該当する商品はありません。</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="report-section">
            <div class="report-section-heading">
                <div>
                    <h2>最新仕入単価</h2>
                    <p>商品ごとの直近の確定仕入単価です。仕入履歴がない商品も表示します。</p>
                </div>
            </div>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr><th>商品コード</th><th>商品名</th><th>最新仕入単価</th></tr></thead>
                    <tbody>
                        @forelse ($latestPurchaseProducts as $product)
                            <tr>
                                <td>{{ $product->code }}</td>
                                <td>{{ $product->name }}</td>
                                <td>{{ $product->latest_purchase_price === null ? '仕入履歴なし' : number_format((float) $product->latest_purchase_price).' 円' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3">商品データがありません。</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="report-section">
            <div class="report-section-heading">
                <div>
                    <h2>在庫不足商品</h2>
                    <p>現在庫数が発注基準数以下の商品です。</p>
                </div>
            </div>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr><th>商品コード</th><th>商品名</th><th>発注基準数</th><th>不足数</th></tr></thead>
                    <tbody>
                        @forelse ($shortageProducts as $product)
                            <tr>
                                <td>{{ $product->code }}</td>
                                <td>{{ $product->name }}</td>
                                <td>{{ number_format($product->reorder_level) }}</td>
                                <td>不足 {{ number_format($product->shortage_quantity) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4">在庫不足の商品はありません。</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="report-section">
            <div class="report-section-heading">
                <div>
                    <h2>平均販売数を上回る商品</h2>
                    <p>商品別販売数量の平均は {{ number_format($averageSalesQuantity, 2) }} 個です。</p>
                </div>
            </div>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr><th>商品コード</th><th>商品名</th><th>販売数量</th></tr></thead>
                    <tbody>
                        @forelse ($aboveAverageProducts as $product)
                            <tr><td>{{ $product->code }}</td><td>{{ $product->name }}</td><td>{{ number_format($product->sales_quantity) }}</td></tr>
                        @empty
                            <tr><td colspan="3">平均販売数を上回る商品はありません。</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="report-section">
            <div class="report-section-heading">
                <div>
                    <h2>商品別販売ランキング</h2>
                    <p>販売数量の降順です。同数の場合は商品コード順に並べます。</p>
                </div>
            </div>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr><th>順位</th><th>商品コード</th><th>商品名</th><th>販売数量</th><th>販売金額</th></tr></thead>
                    <tbody>
                        @forelse ($salesRanking as $product)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $product->code }}</td>
                                <td>{{ $product->name }}</td>
                                <td>{{ number_format($product->sales_quantity) }}</td>
                                <td>{{ number_format((float) $product->sales_amount) }} 円</td>
                            </tr>
                        @empty
                            <tr><td colspan="5">対象期間の販売実績はありません。</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
@endsection
