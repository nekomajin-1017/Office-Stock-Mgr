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
            <x-contact-form-fields :model="$supplier" entity-label="仕入先" />
            <div class="form-actions">
                <a href="{{ route('suppliers.index') }}">一覧へ戻る</a>
                <button class="button button-inline" type="submit">{{ $isEditing ? '更新する' : '登録する' }}</button>
            </div>
        </form>
    </section>
@endsection
