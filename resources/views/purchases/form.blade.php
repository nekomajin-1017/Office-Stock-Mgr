@extends('layouts.app')

@section('stylesheet', 'css/shared/transaction-form.css')


@php($isEditing = isset($purchase))
@section('title', $isEditing ? '仕入伝票編集' : '仕入伝票登録')

@section('content')
    @php($items = old('items', $isEditing ? $purchase->items->toArray() : [['product_id' => '', 'quantity' => 1, 'unit_price' => '']]))

    <section class="dashboard-main form-page">
        <div class="content-block transaction-page-heading">
            <h1 class="page-title">{{ $isEditing ? '仕入伝票編集' : '仕入伝票登録' }}</h1>
            <p class="text-content">仕入先と仕入日を選択してから、商品・数量・仕入単価を明細へ入力してください。</p>
        </div>

        @if (session('status'))
            <p class="text-content success-message">{{ session('status') }}</p>
        @endif

        <form class="form-container transaction-form" action="{{ $isEditing ? route('purchases.update', $purchase) : route('purchases.store') }}" method="post" data-purchase-form>
            @csrf
            @if ($isEditing)
                @method('put')
            @endif

            <ol class="form-guide" aria-label="仕入伝票の入力手順">
                <li class="list-item"><span class="text-span">1</span>仕入先と仕入日を入力</li>
                <li class="list-item"><span class="text-span">2</span>明細へ商品・数量・単価を入力</li>
                <li class="list-item"><span class="text-span">3</span>合計を確認して下書き保存</li>
            </ol>

            <section class="transaction-section" aria-labelledby="purchase-header-title">
                <div class="content-block transaction-section-heading">
                    <div class="content-block">
                        <p class="text-content section-step">STEP 1</p>
                        <h2 class="section-title" id="purchase-header-title">伝票情報</h2>
                    </div>
                    <p class="text-content">すべて必須項目です。</p>
                </div>

                <div class="content-block transaction-header-grid">
                    <div class="content-block form-group">
                        <label class="field-label form-label" for="supplier-id">仕入先 <span class="text-span required-mark">必須</span></label>
                        <select id="supplier-id" class="form-element form-control" name="supplier_id" required data-purchase-supplier>
                            <option value="">仕入先を選択してください</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected((string) $supplier->id === (string) old('supplier_id', $isEditing ? $purchase->supplier_id : null))>
                                    {{ $supplier->code }} / {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-content form-help">仕入先を選ぶと、その仕入先の商品だけを明細で選べます。</p>
                        @error('supplier_id')
                            <p class="text-content field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="content-block form-group">
                        <label class="field-label form-label" for="purchase-date">仕入日 <span class="text-span required-mark">必須</span></label>
                        <input id="purchase-date" class="form-element form-control" name="purchase_date" type="date" value="{{ old('purchase_date', $isEditing ? $purchase->purchase_date->toDateString() : now()->toDateString()) }}" required>
                        <p class="text-content form-help">実際に商品を受け取った日を入力してください。</p>
                        @error('purchase_date')
                            <p class="text-content field-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </section>

            <section class="transaction-section" aria-labelledby="purchase-items-title">
                <div class="content-block transaction-section-heading">
                    <div class="content-block">
                        <p class="text-content section-step">STEP 2</p>
                        <h2 class="section-title" id="purchase-items-title">仕入明細</h2>
                    </div>
                    <p class="text-content">数量は1以上、仕入単価は税抜の1個あたりの価格を入力します。</p>
                </div>

                <div class="content-block transaction-item-headings" aria-hidden="true">
                    <span class="text-span">商品 <b>必須</b></span>
                    <span class="text-span">数量 <b>必須</b></span>
                    <span class="text-span">仕入単価 <b>必須</b></span>
                    <span class="text-span">小計</span>
                    <span class="text-span">操作</span>
                </div>

                <div class="content-block transaction-items" data-purchase-items>
                    @foreach ($items as $index => $item)
                        <div class="content-block transaction-item" data-purchase-item>
                            <label class="field-label">
                                <span class="text-span transaction-mobile-label">商品</span>
                                <select class="form-element form-control" name="items[{{ $index }}][product_id]" required>
                                    <option value="">商品を選択してください</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}" data-supplier="{{ $product->supplier_id }}" @selected((string) $product->id === (string) ($item['product_id'] ?? ''))>
                                            {{ $product->code }} / {{ $product->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="field-label">
                                <span class="text-span transaction-mobile-label">数量</span>
                                <input class="form-element form-control" name="items[{{ $index }}][quantity]" type="number" min="1" step="1" value="{{ $item['quantity'] ?? 1 }}" required>
                            </label>
                            <label class="field-label">
                                <span class="text-span transaction-mobile-label">仕入単価</span>
                                <input class="form-element form-control" name="items[{{ $index }}][unit_price]" type="number" min="0" step="1" placeholder="例: 120" value="{{ $item['unit_price'] ?? '' }}" required>
                            </label>
                            <div class="content-block transaction-line-total">
                                <span class="text-span">この明細の小計<small class="small-text">数量 × 仕入単価</small></span>
                                <output class="output-value" data-line-total>0.00 円</output>
                            </div>
                            <button class="form-element action-button action-button-danger" type="button" data-remove-item>削除</button>
                            @error("items.$index.product_id")
                                <p class="text-content field-error transaction-item-error">{{ $message }}</p>
                            @enderror
                            @error("items.$index.quantity")
                                <p class="text-content field-error transaction-item-error">{{ $message }}</p>
                            @enderror
                            @error("items.$index.unit_price")
                                <p class="text-content field-error transaction-item-error">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>

                <button class="form-element action-button" type="button" data-add-item>＋ 別の商品を追加する</button>
                <p class="text-content transaction-add-help">複数の商品を同じ仕入伝票に登録するときに押してください。</p>
            </section>

            <section class="transaction-total" aria-label="伝票合計">
                <div class="content-block">
                    <p class="text-content">STEP 3</p>
                    <strong>伝票合計</strong>
                </div>
                <output class="output-value" data-purchase-total>0.00 円</output>
            </section>

            <div class="content-block form-actions">
                <a class="page-link" href="{{ route('purchases.index') }}">一覧へ戻る</a>
                <button class="form-element button button-inline" type="submit">{{ $isEditing ? '下書きを更新する' : '下書きとして登録する' }}</button>
            </div>
        </form>
    </section>

    <template data-purchase-template>
        <div class="content-block transaction-item" data-purchase-item>
            <label class="field-label">
                <span class="text-span transaction-mobile-label">商品</span>
                <select class="form-element form-control" name="items[__INDEX__][product_id]" required>
                    <option value="">商品を選択してください</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" data-supplier="{{ $product->supplier_id }}">{{ $product->code }} / {{ $product->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="field-label">
                <span class="text-span transaction-mobile-label">数量</span>
                <input class="form-element form-control" name="items[__INDEX__][quantity]" type="number" min="1" step="1" value="1" required>
            </label>
            <label class="field-label">
                <span class="text-span transaction-mobile-label">仕入単価</span>
                <input class="form-element form-control" name="items[__INDEX__][unit_price]" type="number" min="0" step="1" placeholder="例: 120" required>
            </label>
            <div class="content-block transaction-line-total">
                <span class="text-span">この明細の小計<small class="small-text">数量 × 仕入単価</small></span>
                <output class="output-value" data-line-total>0.00 円</output>
            </div>
            <button class="form-element action-button action-button-danger" type="button" data-remove-item>削除</button>
        </div>
    </template>
@endsection
