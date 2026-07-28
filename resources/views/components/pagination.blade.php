@props(['paginator'])

@php
    $startPage = max(1, $paginator->currentPage() - 2);
    $endPage = min($paginator->lastPage(), $paginator->currentPage() + 2);
@endphp

<nav class="pagination" aria-label="ページネーション">
    <p class="pagination-summary">{{ number_format($paginator->total()) }}件中{{ number_format($paginator->firstItem() ?? 0) }}〜{{ number_format($paginator->lastItem() ?? 0) }}件目を表示</p>
    @if ($paginator->hasPages())
        <div class="pagination-links">
            @if ($paginator->onFirstPage())
                <span class="pagination-button is-disabled" aria-disabled="true">前へ</span>
            @else
                <a class="pagination-button" href="{{ $paginator->previousPageUrl() }}" rel="prev">前へ</a>
            @endif

            @if ($startPage > 1)
                <a class="pagination-button" href="{{ $paginator->url(1) }}">1</a>
                @if ($startPage > 2)
                    <span class="pagination-ellipsis" aria-hidden="true">…</span>
                @endif
            @endif

            @foreach (range($startPage, $endPage) as $page)
                @if ($page === $paginator->currentPage())
                    <span class="pagination-button is-current" aria-current="page">{{ $page }}</span>
                @else
                    <a class="pagination-button" href="{{ $paginator->url($page) }}">{{ $page }}</a>
                @endif
            @endforeach

            @if ($endPage < $paginator->lastPage())
                @if ($endPage < $paginator->lastPage() - 1)
                    <span class="pagination-ellipsis" aria-hidden="true">…</span>
                @endif
                <a class="pagination-button" href="{{ $paginator->url($paginator->lastPage()) }}">{{ $paginator->lastPage() }}</a>
            @endif

            @if ($paginator->hasMorePages())
                <a class="pagination-button" href="{{ $paginator->nextPageUrl() }}" rel="next">次へ</a>
            @else
                <span class="pagination-button is-disabled" aria-disabled="true">次へ</span>
            @endif
        </div>
    @endif
</nav>
