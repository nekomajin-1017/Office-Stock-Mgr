@extends('layouts.app')

@section('stylesheet', 'css/shared/entity-index.css')


@section('title', '仕入先管理')

@section('content')
    <section class="dashboard-main">
        <div class="content-block page-heading">
            <h1 class="page-title">仕入先管理</h1>
            <a class="page-link button button-link" href="{{ route('suppliers.create') }}">仕入先登録</a>
        </div>
        @if (session('status'))
            <p class="text-content success-message">{{ session('status') }}</p>
        @endif
        <form class="form-container filter-form" action="{{ route('suppliers.index') }}" method="get">
            <div class="content-block form-group">
                <label class="field-label form-label" for="keyword">仕入先コード・名称</label>
                <input id="keyword" class="form-element form-control" name="keyword" type="search" value="{{ request('keyword') }}">
            </div>
            <div class="content-block form-group">
                <label class="field-label form-label" for="is-active">状態</label>
                <select id="is-active" class="form-element form-control" name="is_active">
                    <option value="">すべて</option>
                    <option value="1" @selected(request('is_active') === '1')>有効</option>
                    <option value="0" @selected(request('is_active') === '0')>無効</option>
                </select>
            </div>
            <button class="form-element button button-inline" type="submit">検索</button>
        </form>
        <div class="content-block table-wrapper">
            <table class="data-table">
                <thead>
                    <tr class="table-row">
                        <th class="table-heading" scope="col">仕入先コード</th>
                        <th class="table-heading" scope="col">名称</th>
                        <th class="table-heading" scope="col">連絡先</th>
                        <th class="table-heading" scope="col">状態</th>
                        <th class="table-heading" scope="col"><span class="text-span sr-only">操作</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($suppliers as $supplier)
                        <tr class="table-row">
                            <td class="table-cell">{{ $supplier->code }}</td>
                            <td class="table-cell">{{ $supplier->name }}</td>
                            <td class="table-cell">{{ $supplier->contact_person }}<br>{{ $supplier->phone }}<br>{{ $supplier->email }}</td>
                            <td class="table-cell">{{ $supplier->is_active ? '有効' : '無効' }}</td>
                            <td class="table-cell">
                                <a class="page-link action-button" href="{{ route('suppliers.edit', $supplier) }}">編集</a>
                                <form class="form-container inline-form" action="{{ route('suppliers.toggle-status', $supplier) }}" method="post">
                                    @csrf
                                    @method('patch')
                                    <button @class(['form-element','action-button', 'action-button-danger' => $supplier->is_active]) type="submit">{{ $supplier->is_active ? '無効化' : '再有効化' }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr class="table-row">
                            <td class="table-cell" colspan="5">仕入先が登録されていません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-pagination :paginator="$suppliers" />
    </section>
@endsection
