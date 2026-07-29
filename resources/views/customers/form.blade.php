@extends('layouts.app')

@php
    $isEditing = $customer->exists;
@endphp

@section('title', $isEditing ? '顧客編集' : '顧客登録')

@section('content')
    <section class="dashboard-main form-page">
        <h1>{{ $isEditing ? '顧客編集' : '顧客登録' }}</h1>
        <form class="customer-form" action="{{ $isEditing ? route('customers.update', $customer) : route('customers.store') }}" method="post">
            @csrf
            @if ($isEditing)
                @method('put')
            @endif
            <x-contact-form-fields :model="$customer" entity-label="顧客" />
            <div class="form-actions">
                <a href="{{ route('customers.index') }}">一覧へ戻る</a>
                <button class="button button-inline" type="submit">{{ $isEditing ? '更新する' : '登録する' }}</button>
            </div>
        </form>
    </section>
@endsection
