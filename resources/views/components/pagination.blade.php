@props(['items'])

@php
    $current = $items->currentPage();
    $last = $items->lastPage();
    $window = 1;
    $pages = collect();

    if ($last <= 7) {
        $pages = collect(range(1, $last));
    } else {
        $pages->push(1);
        if ($current - $window > 2) $pages->push('...');
        $start = max(2, $current - $window);
        $end = min($last - 1, $current + $window);
        foreach (range($start, $end) as $p) $pages->push($p);
        if ($current + $window < $last - 1) $pages->push('...');
        $pages->push($last);
    }

    $pages = $pages->unique()->values();
@endphp

<div class="d-flex flex-column-reverse flex-md-row flex-wrap justify-content-between align-items-center gap-3 py-3">
    <div>
        <small class="text-muted">
            Showing {{ $items->firstItem() }} to {{ $items->lastItem() }} of {{ $items->total() }} entries
        </small>
    </div>

    @if ($items->hasPages())
    <nav aria-label="Page navigation">
        <ul class="pagination pagination-sm mb-0">
            @if ($current > 1)
            <li class="page-item">
                <a class="page-link border-0 bg-light" href="#" wire:click.prevent="previousPage">
                    <x-lucide-chevron-left style="width: 14px; height: 14px;" />
                </a>
            </li>
            @else
            <li class="page-item disabled">
                <span class="page-link border-0 bg-light">
                    <x-lucide-chevron-left style="width: 14px; height: 14px;" />
                </span>
            </li>
            @endif

            @foreach ($pages as $page)
                @if ($page === '...')
                    <li class="page-item disabled">
                        <span class="page-link border-0 bg-light">...</span>
                    </li>
                @elseif ($page == $current)
                    <li class="page-item active" aria-current="page">
                        <span class="page-link bg-primary border-primary">{{ $page }}</span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link border-0 bg-light" href="#" wire:click.prevent="gotoPage({{ $page }})">{{ $page }}</a>
                    </li>
                @endif
            @endforeach

            @if ($current < $last)
            <li class="page-item">
                <a class="page-link border-0 bg-light" href="#" wire:click.prevent="nextPage">
                    <x-lucide-chevron-right style="width: 14px; height: 14px;" />
                </a>
            </li>
            @else
            <li class="page-item disabled">
                <span class="page-link border-0 bg-light">
                    <x-lucide-chevron-right style="width: 14px; height: 14px;" />
                </span>
            </li>
            @endif
        </ul>
    </nav>
    @endif
</div>
