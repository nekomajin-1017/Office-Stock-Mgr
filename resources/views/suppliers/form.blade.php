@extends('layouts.app')

@section('stylesheet', 'css/shared/contact-form.css')


@php
    $isEditing = $supplier->exists;
@endphp

@section('title', $isEditing ? '仕入先編集' : '仕入先登録')

@section('content')
    <section class="dashboard-main form-page">
        <h1 class="page-title">{{ $isEditing ? '仕入先編集' : '仕入先登録' }}</h1>
        <form class="form-container supplier-form" action="{{ $isEditing ? route('suppliers.update', $supplier) : route('suppliers.store') }}" method="post">
            @csrf
            @if ($isEditing)
                @method('put')
            @endif
            <x-contact-form-fields :model="$supplier" entity-label="仕入先" />
            <div class="content-block form-actions">
                <a class="page-link" href="{{ route('suppliers.index') }}">一覧へ戻る</a>
                <button class="form-element button button-inline" type="submit">{{ $isEditing ? '更新する' : '登録する' }}</button>
            </div>
        </form>
    </section>
@endsection
