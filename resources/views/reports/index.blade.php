@extends('layouts.app')

@section('stylesheet', 'css/reports/index.css')


@section('title', 'レポート')

@section('content')
    <main class="dashboard-main report-page">
        <div class="content-block page-heading">
            <div class="content-block">
                <h1 class="page-title">レポート</h1>
                <p class="text-content page-description">確定済みの仕入・販売データと現在庫を集計しています。</p>
            </div>
        </div>

        <form method="get" class="form-container filter-form report-filter">
            <label class="field-label form-group">
                <span class="text-span form-label">開始日</span>
                <input class="form-element form-control" name="from" type="date" value="{{ $startDate }}">
            </label>
            <label class="field-label form-group">
                <span class="text-span form-label">終了日</span>
                <input class="form-element form-control" name="to" type="date" value="{{ $endDate }}">
            </label>
            <label class="field-label form-group">
                <span class="text-span form-label">ランキング表示件数</span>
                <input class="form-element form-control" name="limit" type="number" min="1" max="100" value="{{ $limit }}">
            </label>
            <button type="submit" class="form-element button">条件を反映</button>
        </form>

        <p class="text-content report-period">販売集計期間: {{ $startDate ?: '開始日指定なし' }} 〜 {{ $endDate ?: '終了日指定なし' }}</p>

        <section class="report-section">
            <div class="content-block report-section-heading">
                <div class="content-block">
                    <h2 class="section-title">未販売商品</h2>
                    <p class="text-content">確定済みの販売明細が一度もない有効商品です。</p>
                </div>
            </div>
            <div class="content-block table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr class="table-row">
                            <th class="table-heading">商品コード</th>
                            <th class="table-heading">商品名</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($unsoldProducts as $product)
                            <tr class="table-row">
                                <td class="table-cell">{{ $product->code }}</td>
                                <td class="table-cell">{{ $product->name }}</td>
                            </tr>
                        @empty
                            <tr class="table-row">
                                <td class="table-cell" colspan="2">該当する商品はありません。</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="report-section">
            <div class="content-block report-section-heading">
                <div class="content-block">
                    <h2 class="section-title">最新仕入単価</h2>
                    <p class="text-content">商品ごとの直近の確定仕入単価です。仕入履歴がない商品も表示します。</p>
                </div>
            </div>
            <div class="content-block table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr class="table-row">
                            <th class="table-heading">商品コード</th>
                            <th class="table-heading">商品名</th>
                            <th class="table-heading">最新仕入単価</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($latestPurchaseProducts as $product)
                            <tr class="table-row">
                                <td class="table-cell">{{ $product->code }}</td>
                                <td class="table-cell">{{ $product->name }}</td>
                                <td class="table-cell">{{ $product->latest_purchase_price === null ? '仕入履歴なし' : number_format((float) $product->latest_purchase_price).' 円' }}</td>
                            </tr>
                        @empty
                            <tr class="table-row">
                                <td class="table-cell" colspan="3">商品データがありません。</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="report-section">
            <div class="content-block report-section-heading">
                <div class="content-block">
                    <h2 class="section-title">在庫不足商品</h2>
                    <p class="text-content">現在庫数が発注基準数以下の商品です。</p>
                </div>
            </div>
            <div class="content-block table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr class="table-row">
                            <th class="table-heading">商品コード</th>
                            <th class="table-heading">商品名</th>
                            <th class="table-heading">発注基準数</th>
                            <th class="table-heading">不足数</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($shortageProducts as $product)
                            <tr class="table-row">
                                <td class="table-cell">{{ $product->code }}</td>
                                <td class="table-cell">{{ $product->name }}</td>
                                <td class="table-cell">{{ number_format($product->reorder_level) }}</td>
                                <td class="table-cell">不足 {{ number_format($product->shortage_quantity) }}</td>
                            </tr>
                        @empty
                            <tr class="table-row">
                                <td class="table-cell" colspan="4">在庫不足の商品はありません。</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="report-section">
            <div class="content-block report-section-heading">
                <div class="content-block">
                    <h2 class="section-title">平均販売数を上回る商品</h2>
                    <p class="text-content">商品別販売数量の平均は {{ number_format($averageSalesQuantity, 2) }} 個です。</p>
                </div>
            </div>
            <div class="content-block table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr class="table-row">
                            <th class="table-heading">商品コード</th>
                            <th class="table-heading">商品名</th>
                            <th class="table-heading">販売数量</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($aboveAverageProducts as $product)
                            <tr class="table-row">
                                <td class="table-cell">{{ $product->code }}</td>
                                <td class="table-cell">{{ $product->name }}</td>
                                <td class="table-cell">{{ number_format($product->sales_quantity) }}</td>
                            </tr>
                        @empty
                            <tr class="table-row">
                                <td class="table-cell" colspan="3">平均販売数を上回る商品はありません。</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="report-section">
            <div class="content-block report-section-heading">
                <div class="content-block">
                    <h2 class="section-title">商品別販売ランキング</h2>
                    <p class="text-content">販売数量の降順です。同数の場合は商品コード順に並べます。</p>
                </div>
            </div>
            <div class="content-block table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr class="table-row">
                            <th class="table-heading">順位</th>
                            <th class="table-heading">商品コード</th>
                            <th class="table-heading">商品名</th>
                            <th class="table-heading">販売数量</th>
                            <th class="table-heading">販売金額</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($salesRanking as $product)
                            <tr class="table-row">
                                <td class="table-cell">{{ $loop->iteration }}</td>
                                <td class="table-cell">{{ $product->code }}</td>
                                <td class="table-cell">{{ $product->name }}</td>
                                <td class="table-cell">{{ number_format($product->sales_quantity) }}</td>
                                <td class="table-cell">{{ number_format((float) $product->sales_amount) }} 円</td>
                            </tr>
                        @empty
                            <tr class="table-row">
                                <td class="table-cell" colspan="5">対象期間の販売実績はありません。</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>
@endsection
