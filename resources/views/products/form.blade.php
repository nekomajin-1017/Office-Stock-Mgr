@extends('layouts.app')

@section('stylesheet', 'css/products/form.css')


@php
    $isEditing = $product->exists;
@endphp

@section('title', $isEditing ? '商品編集' : '商品登録')

@section('content')
    <section class="dashboard-main form-page">
        <h1 class="page-title">{{ $isEditing ? '商品編集' : '商品登録' }}</h1>
        <form class="form-container product-form" action="{{ $isEditing ? route('products.update', $product) : route('products.store') }}" method="post">
            @csrf
            @if ($isEditing)
                @method('put')
            @endif
            <x-form-field name="code" label="商品コード" :value="$product->code" required autofocus />
            <x-form-field name="name" label="商品名" :value="$product->name" required />
            <div class="content-block form-group"><label class="field-label form-label" for="supplier-id">仕入先</label><select id="supplier-id" class="form-element form-control" name="supplier_id" required><option value="">選択してください</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected((string)old('supplier_id',$product->supplier_id)===(string)$supplier->id)>{{ $supplier->name }}</option>@endforeach</select>@error('supplier_id')<p class="text-content field-error">{{ $message }}</p>@enderror</div>
            <div class="content-block form-group">
                <label class="field-label form-label" for="category-id">カテゴリ</label>
                <select id="category-id" class="form-element form-control" name="category_id" required>
                    <option value="">選択してください</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id) === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('category_id')
                    <p class="text-content field-error">{{ $message }}</p>
                @enderror
            </div>
            <x-form-field name="unit" label="単位" :value="$product->unit ?: '個'" required />
            <x-form-field name="standard_sale_price" type="number" label="標準販売価格" :value="$product->standard_sale_price" min="0" step="0.01" required />
            <x-form-field name="reorder_level" type="number" label="発注点" :value="$product->reorder_level" min="0" step="1" required />
            <div class="content-block form-group">
                <label class="field-label form-label" for="description">説明</label>
                <textarea id="description" class="form-element textarea-control form-control" name="description" rows="4">{{ old('description', $product->description) }}</textarea>
                @error('description')
                    <p class="text-content field-error">{{ $message }}</p>
                @enderror
            </div>
            <div class="content-block form-group">
                <label class="field-label form-label" for="is-active">状態</label>
                <select id="is-active" class="form-element form-control" name="is_active" required>
                    <option value="1" @selected(old('is_active', $product->exists ? (int) $product->is_active : 1) == 1)>有効</option>
                    <option value="0" @selected(old('is_active', $product->exists ? (int) $product->is_active : 1) == 0)>無効</option>
                </select>
                @error('is_active')
                    <p class="text-content field-error">{{ $message }}</p>
                @enderror
            </div>
            <div class="content-block form-actions">
                <a class="page-link" href="{{ route('products.index') }}">一覧へ戻る</a>
                <button class="form-element button button-inline" type="submit">{{ $isEditing ? '更新する' : '登録する' }}</button>
            </div>
        </form>
    </section>
@endsection
