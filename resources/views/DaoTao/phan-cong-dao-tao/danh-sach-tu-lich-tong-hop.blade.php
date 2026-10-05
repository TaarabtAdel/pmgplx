@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Tổng hợp phân công theo khoá · xe')

@section('content')
    <div class="card card-panel">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span>Tổng hợp theo khoá · xe (lịch xe PMGPLX)</span>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ $exportUrl }}" class="btn btn-sm btn-success">Xuất Excel</a>
                <a href="{{ $detailUrl }}" class="btn btn-sm btn-outline-primary">← Chi tiết từng dòng</a>
                <a href="{{ $lichXeUrl ?? route('pmgplx.lich.xe.index') }}" class="btn btn-sm btn-outline-secondary">Màn lịch xe tập</a>
            </div>
        </div>
        <div class="card-body">
            <p class="small text-muted mb-3">
                Dữ liệu từ lịch xe tập (<code>KhoaHoc_XeTap</code>, cùng
                <a href="{{ route('pmgplx.lich.xe.index') }}">/pmgplx/lich/xe-tap</a>).
                Một dòng = khoá + biển số xe; cột Giáo viên A–B (nếu >2 GV, ghi thêm ở cột B).
                Tạm thời: <strong>tên khoá</strong> có <strong>B01</strong> → khoá tự động (giữ xe B11); khoá khác ẩn xe tự động (danh mục xe).
            </p>

            @include('DaoTao.phan-cong-dao-tao.partials.loc-tu-lich-pmgplx', [
                'locAction' => route('daotao.pdt.phan-cong-dao-tao.danh-sach-tu-lich-tong-hop'),
                'showLoai' => false,
            ])

            <div class="table-responsive mt-3">
                <table class="table table-sm table-bordered table-striped table-hover table-data mb-0">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Khoá</th>
                            <th>Biển số xe</th>
                            <th>TG bắt đầu</th>
                            <th>TG kết thúc</th>
                            @foreach (['A', 'B'] as $letter)
                                <th>Giáo viên {{ $letter }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $index => $row)
                            <tr>
                                <td>{{ $items->firstItem() + $index }}</td>
                                <td>
                                    <strong>{{ $row['ten_khoa'] ?? '—' }}</strong>
                                    @if (! empty($row['ma_kh']))
                                        <div class="small text-muted">{{ $row['ma_kh'] }}</div>
                                    @endif
                                </td>
                                <td><strong>{{ ($row['bien_so'] ?? '') !== '' ? $row['bien_so'] : '—' }}</strong></td>
                                <td class="text-nowrap">
                                    @include('DaoTao.phan-cong-dao-tao.partials.thoi-gian-lich-pmgplx', [
                                        'tu' => $row['tu_ngay'] ?? null,
                                        'mode' => 'start',
                                    ])
                                </td>
                                <td class="text-nowrap">
                                    @include('DaoTao.phan-cong-dao-tao.partials.thoi-gian-lich-pmgplx', [
                                        'den' => $row['den_ngay'] ?? null,
                                        'mode' => 'end',
                                    ])
                                </td>
                                @php
                                    $gvs = array_slice($row['giao_viens'] ?? [], 0, 2);
                                    $gvExtra = max(0, count($row['giao_viens'] ?? []) - 2);
                                @endphp
                                @for ($i = 0; $i < 2; $i++)
                                    <td>
                                        @if (isset($gvs[$i]))
                                            {{ $gvs[$i]['ho_ten'] }}
                                            @if ($gvs[$i]['ma_gv'] ?? '')
                                                <div class="small text-muted">{{ $gvs[$i]['ma_gv'] }}</div>
                                            @endif
                                            @if ($i === 1 && $gvExtra > 0)
                                                <div class="small text-warning">+{{ $gvExtra }} GV</div>
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                @endfor
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">Không có dữ liệu</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($items->hasPages())
                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                    <div class="text-muted small">
                        Trang {{ $items->currentPage() }}/{{ max($items->lastPage(), 1) }}
                        · {{ number_format($items->total()) }} dòng (khoá · xe)
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
        </div>
    </div>
@endsection

@push('scripts')
    @include('DaoTao.phan-cong-dao-tao.partials.scripts-loc-tu-lich-pmgplx')
@endpush
