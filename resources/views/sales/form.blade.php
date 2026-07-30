@extends('layouts.app')

@php($isEditing = isset($sale))
@section('title', $isEditing ? '販売伝票編集' : '販売伝票登録')

@section('content')
    @php($items = old('items', $isEditing ? $sale->items->toArray() : [['product_id' => '', 'quantity' => 1, 'unit_price' => '']]))

    <section class="dashboard-main form-page">
        <div class="transaction-page-heading">
            <h1>{{ $isEditing ? '販売伝票編集' : '販売伝票登録' }}</h1>
            <p>顧客と販売日を選択してから、商品・数量・販売単価を明細へ入力してください。</p>
        </div>

        @if (session('status'))
            <p class="success-message">{{ session('status') }}</p>
        @endif

        <form class="transaction-form" action="{{ $isEditing ? route('sales.update', $sale) : route('sales.store') }}" method="post" data-sale-form>
            @csrf
            @if ($isEditing)
                @method('put')
            @endif

            <ol class="form-guide" aria-label="販売伝票の入力手順">
                <li><span>1</span>顧客と販売日を入力</li>
                <li><span>2</span>在庫を確認して明細を入力</li>
                <li><span>3</span>合計を確認して下書き保存</li>
            </ol>

            <section class="transaction-section" aria-labelledby="sale-header-title">
                <div class="transaction-section-heading">
                    <div>
                        <p class="section-step">STEP 1</p>
                        <h2 id="sale-header-title">伝票情報</h2>
                    </div>
                    <p>すべて必須項目です。</p>
                </div>

                <div class="transaction-header-grid">
                    <div class="form-group">
                        <label class="form-label" for="customer-id">顧客 <span class="required-mark">必須</span></label>
                        <select id="customer-id" class="form-control" name="customer_id" required>
                            <option value="">顧客を選択してください</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" @selected((string) $customer->id === (string) old('customer_id', $isEditing ? $sale->customer_id : null))>
                                    {{ $customer->code }} / {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="form-help">今回の商品を販売する顧客を選択してください。</p>
                        @error('customer_id')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="sale-date">販売日 <span class="required-mark">必須</span></label>
                        <input id="sale-date" class="form-control" name="sale_date" type="date" value="{{ old('sale_date', $isEditing ? $sale->sale_date->toDateString() : now()->toDateString()) }}" required>
                        <p class="form-help">実際に販売した日を入力してください。</p>
                        @error('sale_date')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </section>

            <section class="transaction-section" aria-labelledby="sale-items-title">
                <div class="transaction-section-heading">
                    <div>
                        <p class="section-step">STEP 2</p>
                        <h2 id="sale-items-title">販売明細</h2>
                    </div>
                    <p>表示される在庫数を確認し、数量が在庫を超えないよう入力してください。</p>
                </div>

                <div class="transaction-item-headings" aria-hidden="true">
                    <span>商品・現在庫 <b>必須</b></span>
                    <span>数量 <b>必須</b></span>
                    <span>販売単価 <b>必須</b></span>
                    <span>小計</span>
                    <span>操作</span>
                </div>

                <div class="transaction-items" data-sale-items>
                    @foreach ($items as $index => $item)
                        <div class="transaction-item" data-sale-item>
                            <label>
                                <span class="transaction-mobile-label">商品・現在庫</span>
                                <select class="form-control" name="items[{{ $index }}][product_id]" required>
                                    <option value="">商品を選択してください</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}" data-stock="{{ $product->stock?->quantity ?? 0 }}" @selected((string) $product->id === (string) ($item['product_id'] ?? ''))>
                                            {{ $product->code }} / {{ $product->name }}（在庫: {{ $product->stock?->quantity ?? 0 }}）
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                            <label>
                                <span class="transaction-mobile-label">数量</span>
                                <input class="form-control" name="items[{{ $index }}][quantity]" type="number" min="1" step="1" value="{{ $item['quantity'] ?? 1 }}" required>
                            </label>
                            <label>
                                <span class="transaction-mobile-label">販売単価</span>
                                <input class="form-control" name="items[{{ $index }}][unit_price]" type="number" min="0" step="1" placeholder="例: 150" value="{{ $item['unit_price'] ?? '' }}" required>
                            </label>
                            <div class="transaction-line-total">
                                <span>この明細の小計<small>数量 × 販売単価</small></span>
                                <output data-line-total>0.00 円</output>
                            </div>
                            <button class="action-button action-button-danger" type="button" data-remove-sale-item>削除</button>
                            @error("items.$index.product_id")
                                <p class="field-error transaction-item-error">{{ $message }}</p>
                            @enderror
                            @error("items.$index.quantity")
                                <p class="field-error transaction-item-error">{{ $message }}</p>
                            @enderror
                            @error("items.$index.unit_price")
                                <p class="field-error transaction-item-error">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>

                <button class="action-button" type="button" data-add-sale-item>＋ 別の商品を追加する</button>
                <p class="transaction-add-help">複数の商品を同じ販売伝票に登録するときに押してください。</p>
            </section>

            <section class="transaction-total" aria-label="伝票合計">
                <div>
                    <p>STEP 3</p>
                    <strong>伝票合計</strong>
                </div>
                <output data-sale-total>0.00 円</output>
            </section>

            <div class="form-actions">
                <a href="{{ route('sales.index') }}">一覧へ戻る</a>
                <button class="button button-inline" type="submit">{{ $isEditing ? '下書きを更新する' : '下書きとして登録する' }}</button>
            </div>
        </form>
    </section>
@endsection
