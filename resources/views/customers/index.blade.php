@extends('layouts.app')

@section('title', '顧客管理')

@section('content')
    <section class="dashboard-main">
        <div class="page-heading">
            <h1>顧客管理</h1>
            <a class="button button-link" href="{{ route('customers.create') }}">顧客登録</a>
        </div>
        @if (session('status'))
            <p class="success-message">{{ session('status') }}</p>
        @endif
        <form class="filter-form" action="{{ route('customers.index') }}" method="get">
            <div class="form-group">
                <label class="form-label" for="keyword">顧客コード・名称</label>
                <input id="keyword" class="form-control" name="keyword" type="search" value="{{ request('keyword') }}">
            </div>
            <div class="form-group">
                <label class="form-label" for="is-active">状態</label>
                <select id="is-active" class="form-control" name="is_active">
                    <option value="">すべて</option>
                    <option value="1" @selected(request('is_active') === '1')>有効</option>
                    <option value="0" @selected(request('is_active') === '0')>無効</option>
                </select>
            </div>
            <button class="button button-inline" type="submit">検索</button>
        </form>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">顧客コード</th>
                        <th scope="col">名称</th>
                        <th scope="col">連絡先</th>
                        <th scope="col">状態</th>
                        <th scope="col"><span class="sr-only">操作</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr>
                            <td>{{ $customer->code }}</td>
                            <td>{{ $customer->name }}</td>
                            <td>{{ $customer->contact_person }}<br>{{ $customer->phone }}<br>{{ $customer->email }}</td>
                            <td>{{ $customer->is_active ? '有効' : '無効' }}</td>
                            <td>
                                <a class="action-button" href="{{ route('customers.edit', $customer) }}">編集</a>
                                <form class="inline-form" action="{{ route('customers.toggle-status', $customer) }}" method="post">
                                    @csrf
                                    @method('patch')
                                    <button @class(['action-button', 'action-button-danger' => $customer->is_active]) type="submit">{{ $customer->is_active ? '無効化' : '再有効化' }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">顧客が登録されていません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-pagination :paginator="$customers" />
    </section>
@endsection
