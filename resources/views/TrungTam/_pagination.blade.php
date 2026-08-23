@if ($items->hasPages() || $items->total() > 0)
    <div class="d-flex flex-wrap align-items-center justify-content-between mt-3 gap-2">
        <div class="text-muted small">
            Tổng <strong>{{ number_format($items->total()) }}</strong> bản ghi
            · Trang {{ $items->currentPage() }}/{{ max($items->lastPage(), 1) }}
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <form method="GET" action="{{ request()->url() }}" class="form-inline">
                @foreach (request()->except(['per_page', 'page']) as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <label class="mr-2 mb-0 small">Số dòng/trang</label>
                <select name="per_page" class="form-control form-control-sm" onchange="this.form.submit()">
                    @foreach ([20, 50, 100, 200] as $n)
                        <option value="{{ $n }}" @selected((int) ($filters['per_page'] ?? 50) === $n)>{{ $n }}</option>
                    @endforeach
                </select>
            </form>
            @if ($items->hasPages())
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item {{ $items->onFirstPage() ? 'disabled' : '' }}">
                            <a class="page-link" href="{{ $items->url(1) }}">«</a>
                        </li>
                        <li class="page-item {{ $items->onFirstPage() ? 'disabled' : '' }}">
                            <a class="page-link" href="{{ $items->previousPageUrl() }}">‹</a>
                        </li>
                        <li class="page-item active">
                            <span class="page-link">{{ $items->currentPage() }}</span>
                        </li>
                        <li class="page-item {{ $items->hasMorePages() ? '' : 'disabled' }}">
                            <a class="page-link" href="{{ $items->nextPageUrl() }}">›</a>
                        </li>
                        <li class="page-item {{ $items->hasMorePages() ? '' : 'disabled' }}">
                            <a class="page-link" href="{{ $items->url($items->lastPage()) }}">»</a>
                        </li>
                    </ul>
                </nav>
            @endif
        </div>
    </div>
@endif
