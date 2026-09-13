@php
    $hideKhoaHocColumn = $hideKhoaHocColumn ?? false;
    $tableColspan = $hideKhoaHocColumn ? 5 : 6;
@endphp
<div class="table-responsive">
    <table class="table table-sm table-bordered table-hover mb-0">
        <thead class="thead-light">
            <tr>
                <th>#</th>
                <th>Mã phiên học</th>
                <th>Học viên</th>
                @unless ($hideKhoaHocColumn)
                    <th>Khóa học</th>
                @endunless
                <th>Giáo viên</th>
                <th>Xe</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                <tr>
                    <td>{{ ($items->firstItem() ?? 0) + $loop->index }}</td>
                    <td>{{ $item->MaPhienHoc ?: '—' }}</td>
                    <td>
                        @if (! empty($item->HoTenHocVien))
                            {{ $item->HoTenHocVien }}
                            @if (! empty($item->MaHocVien))
                                <span class="text-muted">({{ $item->MaHocVien }})</span>
                            @endif
                        @else
                            {{ $item->MaHocVien ?: '—' }}
                        @endif
                    </td>
                    @unless ($hideKhoaHocColumn)
                        <td>
                            @if (! empty($item->TenKhoaHoc))
                                {{ $item->TenKhoaHoc }}
                                @if (! empty($item->MaKhoaHoc))
                                    <span class="text-muted">({{ $item->MaKhoaHoc }})</span>
                                @endif
                            @else
                                {{ $item->MaKhoaHoc ?: '—' }}
                            @endif
                        </td>
                    @endunless
                    <td>
                        @if (! empty($item->HoTenGiaoVien))
                            {{ $item->HoTenGiaoVien }}
                            @if (! empty($item->MaGiaoVien))
                                <span class="text-muted">({{ $item->MaGiaoVien }})</span>
                            @endif
                        @else
                            {{ $item->MaGiaoVien ?: '—' }}
                        @endif
                    </td>
                    <td>{{ $item->BienSoXe ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $tableColspan }}" class="text-center py-4 text-muted">
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
