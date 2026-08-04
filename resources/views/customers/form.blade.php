@extends('layouts.app')

@section('stylesheet', 'css/shared/contact-form.css')


@php
    $isEditing = $customer->exists;
@endphp

@section('title', $isEditing ? '顧客編集' : '顧客登録')

@section('content')
    <section class="dashboard-main form-page">
        <h1 class="page-title">{{ $isEditing ? '顧客編集' : '顧客登録' }}</h1>
        <form class="form-container customer-form" action="{{ $isEditing ? route('customers.update', $customer) : route('customers.store') }}" method="post">
            @csrf
            @if ($isEditing)
                @method('put')
            @endif
            <x-contact-form-fields :model="$customer" entity-label="顧客" />
            <div class="content-block form-actions">
                <a class="page-link" href="{{ route('customers.index') }}">一覧へ戻る</a>
                <button class="form-element button button-inline" type="submit">{{ $isEditing ? '更新する' : '登録する' }}</button>
            </div>
        </form>
    </section>
@endsection
