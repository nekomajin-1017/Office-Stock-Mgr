@extends('layouts.app')

@php($isEditing = isset($purchase))
@section('title', $isEditing ? '仕入伝票編集' : '仕入伝票登録')

@section('content')
    @php($items = old('items', $isEditing ? $purchase->items->toArray() : [['product_id' => '', 'quantity' => 1, 'unit_price' => '']]))

    <section class="dashboard-main form-page">
        <div class="transaction-page-heading">
            <h1>{{ $isEditing ? '仕入伝票編集' : '仕入伝票登録' }}</h1>
            <p>仕入先と仕入日を選択してから、商品・数量・仕入単価を明細へ入力してください。</p>
        </div>

        <form class="transaction-form" action="{{ $isEditing ? route('purchases.update', $purchase) : route('purchases.store') }}" method="post" data-purchase-form>
            @csrf
            @if ($isEditing)
                @method('put')
            @endif

            <ol class="form-guide" aria-label="仕入伝票の入力手順">
                <li><span>1</span>仕入先と仕入日を入力</li>
                <li><span>2</span>明細へ商品・数量・単価を入力</li>
                <li><span>3</span>合計を確認して下書き保存</li>
            </ol>

            <section class="transaction-section" aria-labelledby="purchase-header-title">
                <div class="transaction-section-heading">
                    <div>
                        <p class="section-step">STEP 1</p>
                        <h2 id="purchase-header-title">伝票情報</h2>
                    </div>
                    <p>すべて必須項目です。</p>
                </div>

                <div class="transaction-header-grid">
                    <div class="form-group">
                        <label class="form-label" for="supplier-id">仕入先 <span class="required-mark">必須</span></label>
                        <select id="supplier-id" class="form-control" name="supplier_id" required data-purchase-supplier>
                            <option value="">仕入先を選択してください</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected((string) $supplier->id === old('supplier_id', $isEditing ? $purchase->supplier_id : null))>
                                    {{ $supplier->code }} / {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="form-help">仕入先を選ぶと、その仕入先の商品だけを明細で選べます。</p>
                        @error('supplier_id')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="purchase-date">仕入日 <span class="required-mark">必須</span></label>
                        <input id="purchase-date" class="form-control" name="purchase_date" type="date" value="{{ old('purchase_date', $isEditing ? $purchase->purchase_date->toDateString() : now()->toDateString()) }}" required>
                        <p class="form-help">実際に商品を受け取った日を入力してください。</p>
                        @error('purchase_date')
                            <p class="field-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </section>

            <section class="transaction-section" aria-labelledby="purchase-items-title">
                <div class="transaction-section-heading">
                    <div>
                        <p class="section-step">STEP 2</p>
                        <h2 id="purchase-items-title">仕入明細</h2>
                    </div>
                    <p>数量は1以上、仕入単価は税抜の1個あたりの価格を入力します。</p>
                </div>

                <div class="transaction-item-headings" aria-hidden="true">
                    <span>商品 <b>必須</b></span>
                    <span>数量 <b>必須</b></span>
                    <span>仕入単価 <b>必須</b></span>
                    <span>小計</span>
                    <span>操作</span>
                </div>

                <div class="transaction-items" data-purchase-items>
                    @foreach ($items as $index => $item)
                        <div class="transaction-item" data-purchase-item>
                            <label>
                                <span class="transaction-mobile-label">商品</span>
                                <select class="form-control" name="items[{{ $index }}][product_id]" required>
                                    <option value="">商品を選択してください</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}" data-supplier="{{ $product->supplier_id }}" @selected((string) $product->id === (string) ($item['product_id'] ?? ''))>
                                            {{ $product->code }} / {{ $product->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                            <label>
                                <span class="transaction-mobile-label">数量</span>
                                <input class="form-control" name="items[{{ $index }}][quantity]" type="number" min="1" step="1" value="{{ $item['quantity'] ?? 1 }}" required>
                            </label>
                            <label>
                                <span class="transaction-mobile-label">仕入単価</span>
                                <input class="form-control" name="items[{{ $index }}][unit_price]" type="number" min="0" step="1" placeholder="例: 120" value="{{ $item['unit_price'] ?? '' }}" required>
                            </label>
                            <div class="transaction-line-total">
                                <span>この明細の小計<small>数量 × 仕入単価</small></span>
                                <output data-line-total>0.00 円</output>
                            </div>
                            <button class="action-button action-button-danger" type="button" data-remove-item>削除</button>
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

                <button class="action-button" type="button" data-add-item>＋ 別の商品を追加する</button>
                <p class="transaction-add-help">複数の商品を同じ仕入伝票に登録するときに押してください。</p>
            </section>

            <section class="transaction-total" aria-label="伝票合計">
                <div>
                    <p>STEP 3</p>
                    <strong>伝票合計</strong>
                </div>
                <output data-purchase-total>0.00 円</output>
            </section>

            <div class="form-actions">
                <a href="{{ route('purchases.index') }}">一覧へ戻る</a>
                <button class="button button-inline" type="submit">{{ $isEditing ? '下書きを更新する' : '下書きとして登録する' }}</button>
            </div>
        </form>
    </section>

    <template data-purchase-template>
        <div class="transaction-item" data-purchase-item>
            <label>
                <span class="transaction-mobile-label">商品</span>
                <select class="form-control" name="items[__INDEX__][product_id]" required>
                    <option value="">商品を選択してください</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" data-supplier="{{ $product->supplier_id }}">{{ $product->code }} / {{ $product->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="transaction-mobile-label">数量</span>
                <input class="form-control" name="items[__INDEX__][quantity]" type="number" min="1" step="1" value="1" required>
            </label>
            <label>
                <span class="transaction-mobile-label">仕入単価</span>
                <input class="form-control" name="items[__INDEX__][unit_price]" type="number" min="0" step="1" placeholder="例: 120" required>
            </label>
            <div class="transaction-line-total">
                <span>この明細の小計<small>数量 × 仕入単価</small></span>
                <output data-line-total>0.00 円</output>
            </div>
            <button class="action-button action-button-danger" type="button" data-remove-item>削除</button>
        </div>
    </template>
@endsection
