@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Phân công từ lịch PMGPLX')

@section('content')
    <div class="card card-panel">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span>Phân công từ lịch PMGPLX (đối chiếu)</span>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ $tongHopUrl ?? route('daotao.pdt.phan-cong-dao-tao.danh-sach-tu-lich-tong-hop') }}" class="btn btn-sm btn-outline-success">Tổng hợp khoá · xe</a>
                <a href="{{ $compareManualUrl }}" class="btn btn-sm btn-outline-primary">← Phân công nhập tay</a>
                <a href="{{ route('pmgplx.lich.gv.index') }}" class="btn btn-sm btn-outline-secondary">Lịch giáo viên</a>
                <a href="{{ route('pmgplx.lich.xe.index') }}" class="btn btn-sm btn-outline-secondary">Lịch xe tập</a>
            </div>
        </div>
        <div class="card-body">
            <p class="small text-muted mb-3">
                Dữ liệu gộp từ <strong>Lịch giáo viên</strong> (dòng loại <code>LT</code>, tương ứng GVLT)
                và <strong>Lịch xe tập</strong> (thực hành / GVTH), cùng bộ lọc với danh sách phân công nhập tay
                để đối chiếu khoá · giáo viên · thời gian · xe.
            </p>

            @include('DaoTao.phan-cong-dao-tao.partials.loc-tu-lich-pmgplx', [
                'locAction' => route('daotao.pdt.phan-cong-dao-tao.danh-sach-tu-lich'),
                'showLoai' => true,
            ])
            <p class="small text-muted mb-0 mt-1">
                Lịch xe TH: khoá <strong>B01</strong> giữ xe tự động (B11); khoá khác ẩn xe tự động trên lịch (danh mục PMGPLX).
            </p>

            <div class="table-responsive mt-3">
                <table class="table table-sm table-bordered table-striped table-hover table-data mb-0">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Khoá</th>
                            <th>Giáo viên</th>
                            <th>Thời gian</th>
                            <th>Biển số xe</th>
                            <th>Nội dung giảng dạy</th>
                            <th>Nguồn</th>
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
                                <td>
                                    {{ $row['ho_ten_gv'] ?: '—' }}
                                    @if (! empty($row['ma_gv']))
                                        <div class="small text-muted">{{ $row['ma_gv'] }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if ($row['tu_ngay'] && $row['den_ngay'])
                                        {{ $row['tu_ngay']->format('d/m/Y') }} – {{ $row['den_ngay']->format('d/m/Y') }}
                                    @elseif ($row['tu_ngay'])
                                        {{ $row['tu_ngay']->format('d/m/Y') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $row['bien_so'] !== '' ? $row['bien_so'] : '—' }}</td>
                                <td>
                                    @if (! empty($row['loai_giang_day']))
                                        {{ \App\Models\DaoTao\PhanCongDaoTao::loaiGiangDayLabel($row['loai_giang_day']) }}
                                        <span class="text-muted small">({{ $row['loai_giang_day'] === 'ly_thuyet' ? 'GVLT' : 'GVTH' }})</span>
                                        @if (($row['noi_dung'] ?? '') !== '—')
                                            <div class="small">{{ $row['noi_dung'] }}</div>
                                        @endif
                                    @else
                                        {{ $row['noi_dung'] ?? '—' }}
                                    @endif
                                </td>
                                <td class="text-nowrap small">
                                    @if (($row['nguon'] ?? '') === 'lich_gv')
                                        <a href="{{ route('pmgplx.lich.gv.index', array_filter(['ma_kh' => $row['ma_kh'] ?? '', 'ma_gv' => $row['ma_gv'] ?? ''])) }}">Lịch GV</a>
                                    @else
                                        <a href="{{ route('pmgplx.lich.xe.index', array_filter(['ma_kh' => $row['ma_kh'] ?? '', 'bien_so_xe' => $row['bien_so'] ?? '', 'ma_gv' => $row['ma_gv'] ?? ''])) }}">Lịch xe</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">Không có dòng lịch phù hợp bộ lọc</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($items->hasPages())
                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                    <div class="text-muted small">
                        Trang {{ $items->currentPage() }}/{{ max($items->lastPage(), 1) }}
                        · {{ number_format($items->total()) }} dòng
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
