<div class="table-responsive">
    <table class="table table-sm table-bordered table-hover mb-0 dat-do-tuyen-duong-table" id="datDoTuyenDuongTable">
        <thead class="thead-light">
            <tr>
                <th style="width: 3rem;">#</th>
                <th style="min-width: 16rem;">Thông tin phiên</th>
                <th class="col-ban-do">Bản đồ GPS</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                @php
                    $start = $item->ThoiGianBatDauPhienHoc;
                    $end = $item->ThoiGianKetThucPhienHoc;
                @endphp
                <tr data-phien-id="{{ $item->Id }}">
                    <td>{{ ($items->firstItem() ?? 0) + $loop->index }}</td>
                    <td class="col-thong-tin">
                        <div><code>{{ $item->MaPhienHoc ?: '—' }}</code></div>
                        <div class="mt-1">
                            <span class="text-muted small">HV:</span>
                            {{ $item->HoTenHocVien ?: '—' }}
                            <span class="cell-sub d-inline"><code>{{ $item->MaHocVien ?: '—' }}</code></span>
                        </div>
                        <div class="mt-1">
                            <span class="text-muted small">GV:</span>
                            {{ $item->HoTenGiaoVien ?: '—' }}
                            <span class="cell-sub d-inline"><code>{{ $item->MaGiaoVien ?: '—' }}</code></span>
                        </div>
                        <div class="mt-1">
                            <span class="text-muted small">Xe:</span> {{ $item->BienSoXe ?: '—' }}
                        </div>
                        <div class="mt-1 col-thoi-gian">
                            @if ($start || $end)
                                <div><span class="text-muted small">Bắt đầu:</span> {{ $start?->format('d/m/Y H:i') ?? '—' }}</div>
                                <div><span class="text-muted small">Kết thúc:</span> {{ $end?->format('d/m/Y H:i') ?? '—' }}</div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                        <div class="mt-2 col-tuyen-tom-tat text-muted small"></div>
                    </td>
                    <td class="col-ban-do p-2">
                        <div class="dat-gps-map-wrap">
                            <div class="dat-gps-map-placeholder text-muted small py-5 text-center">
                                Chưa dò GPS
                            </div>
                            <div class="dat-gps-map d-none" id="dat-gps-map-{{ $item->Id }}"></div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center py-4 text-muted">
                        Không có phiên theo bộ lọc đã chọn.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($items->hasPages())
    <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
        <div class="text-muted small mb-2 mb-md-0">
            Trang {{ $items->currentPage() }}/{{ max($items->lastPage(), 1) }}
            · {{ number_format($items->total()) }} phiên
        </div>
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
    </div>
@endif
