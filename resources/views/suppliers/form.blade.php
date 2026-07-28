@extends('layouts.app')

@php
  $isEditing = $supplier->exists;
@endphp

@section('title', $isEditing ? '仕入先編集' : '仕入先登録')

@section('content')
  <section class="dashboard-main form-page">
    <h1>{{ $isEditing ? '仕入先編集' : '仕入先登録' }}</h1>
    <form class="supplier-form" action="{{ $isEditing ? route('suppliers.update', $supplier) : route('suppliers.store') }}" method="post">
      @csrf
      @if ($isEditing)
        @method('put')
      @endif
      <x-form-field name="code" label="仕入先コード" :value="$supplier->code" required autofocus />
      <x-form-field name="name" label="仕入先名" :value="$supplier->name" required />
      <x-form-field name="postal_code" label="郵便番号" :value="$supplier->postal_code" />
      <x-form-field name="address" label="住所" :value="$supplier->address" />
      <x-form-field name="phone" label="電話番号" :value="$supplier->phone" />
      <x-form-field name="email" type="email" label="メールアドレス" :value="$supplier->email" />
      <x-form-field name="contact_person" label="担当者名" :value="$supplier->contact_person" />
      <div class="form-group">
        <label class="form-label" for="is-active">状態</label>
        <select id="is-active" class="form-control" name="is_active" required>
          <option value="1" @selected(old('is_active', $supplier->exists ? (int) $supplier->is_active : 1) == 1)>有効</option>
          <option value="0" @selected(old('is_active', $supplier->exists ? (int) $supplier->is_active : 1) == 0)>無効</option>
        </select>
        @error('is_active')
          <p class="field-error">{{ $message }}</p>
        @enderror
      </div>
      <div class="form-actions">
        <a href="{{ route('suppliers.index') }}">一覧へ戻る</a>
        <button class="button button-inline" type="submit">{{ $isEditing ? '更新する' : '登録する' }}</button>
      </div>
    </form>
  </section>
@endsection
