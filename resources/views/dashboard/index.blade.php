@extends('layouts.app')

@section('title', 'ダッシュボード')

@section('content')
  <section class="dashboard-main">
    <h1>ダッシュボード</h1>
    <p>{{ auth()->user()->name }} さん、ログインしました。</p>
  </section>
@endsection
