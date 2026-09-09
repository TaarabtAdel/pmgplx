@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Dò phiên với lịch xe')

@push('styles')
<style>
    .dat-do-lich-row-canh-bao {
        background-color: #fff8e1;
    }
</style>
@endpush

@section('content')
    <div class="card card-panel mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <span>Dò phiên với lịch xe</span>
            <div class="mt-1 mt-md-0">
                <a href="{{ route('daotao.pdt.dat.quan-ly-phien') }}" class="btn btn-sm btn-outline-secondary mr-1">
                    Chi tiết phiên
                </a>
                @if ($filters['ma_khoa_hoc'] !== '')
                    <a href="{{ route('pmgplx.lich.xe.index', ['ma_kh' => $filters['ma_khoa_hoc']]) }}"
                       class="btn btn-sm btn-navy" target="_blank" rel="noopener">
                        Lịch xe tập (khóa này)
                    </a>
                @endif
            </div>
        </div>
        <div class="card-body">
            <div class="border rounded p-3 bg-light small mb-3">
                <p class="mb-2">
                    Chọn <strong>mã khóa</strong> để lấy phiên DAT và so với
                    <a href="{{ route('pmgplx.lich.xe.index') }}">lịch sử dụng xe tập</a>
                    cùng khóa (<strong>Mã KH</strong>, <strong>xe</strong>, <strong>thời gian bắt đầu / kết thúc</strong>).
                </p>
                <p class="mb-0">
                    <strong>Hợp lệ:</strong> khung giờ phiên nằm trong một dòng lịch xe (cùng khóa, cùng biển số).
                    <strong>Cảnh báo:</strong> không khớp lịch hoặc nằm ngoài khung giờ đã đặt.
                </p>
            </div>

            <form method="GET" action="{{ route('daotao.pdt.dat.do-phien-lich-xe') }}" class="mb-3">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-5">
                        <label class="small text-muted mb-1" for="filter_ma_khoa_hoc">Mã khóa học</label>
                        <select name="ma_khoa_hoc" id="filter_ma_khoa_hoc" class="form-control form-control-sm" required>
                            <option value="">— Chọn khóa —</option>
                            @foreach ($khoaHocOptions as $kh)
                                <option value="{{ $kh->MaKhoaHoc }}"
                                        @selected($filters['ma_khoa_hoc'] === $kh->MaKhoaHoc)>
                                    @if (! empty($kh->TenKhoaHoc))
                                        {{ $kh->TenKhoaHoc }} ({{ $kh->MaKhoaHoc }})
                                    @else
                                        {{ $kh->MaKhoaHoc }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label class="small text-muted mb-1" for="filter_ket_qua">Kết quả dò</label>
                        <select name="ket_qua" id="filter_ket_qua" class="form-control form-control-sm">
                            <option value="" @selected($filters['ket_qua'] === '')>— Tất cả —</option>
                            <option value="hop_le" @selected($filters['ket_qua'] === 'hop_le')>Hợp lệ</option>
                            <option value="canh_bao" @selected($filters['ket_qua'] === 'canh_bao')>Cảnh báo</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <button type="submit" class="btn btn-sm btn-navy mr-2">Dò</button>
                        <a href="{{ route('daotao.pdt.dat.do-phien-lich-xe') }}" class="btn btn-sm btn-outline-secondary">
                            Reset
                        </a>
                    </div>
                </div>
            </form>

            @if ($filters['ma_khoa_hoc'] !== '')
                <div class="d-flex flex-wrap mb-3 small">
                    <span class="mr-3">
                        Khóa: <strong>{{ $filters['ma_khoa_hoc'] }}</strong>
                    </span>
                    <span class="mr-3">
                        Lịch xe tập: <strong>{{ number_format($scheduleCount) }}</strong> dòng
                    </span>
                    <span class="mr-3">
                        Phiên: <strong>{{ number_format($stats['total']) }}</strong>
                        · Hợp lệ: <strong class="text-success">{{ number_format($stats['hop_le']) }}</strong>
                        · Cảnh báo: <strong class="text-warning">{{ number_format($stats['canh_bao']) }}</strong>
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>Mã phiên</th>
                                <th>Học viên</th>
                                <th>Xe</th>
                                <th>TG bắt đầu</th>
                                <th>TG kết thúc</th>
                                <th>Kết quả</th>
                                <th>Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $row)
                                @php
                                    $session = $row->session;
                                    $start = $session->ThoiGianBatDauPhienHoc;
                                    $end = $session->ThoiGianKetThucPhienHoc;
                                @endphp
                                <tr @class(['dat-do-lich-row-canh-bao' => ! $row->valid])>
                                    <td>{{ ($items->firstItem() ?? 0) + $loop->index }}</td>
                                    <td><code>{{ $session->MaPhienHoc }}</code></td>
                                    <td>
                                        <div>{{ $session->HoTenHocVien ?? '—' }}</div>
                                        <small class="text-muted">{{ $session->MaHocVien ?? '' }}</small>
                                    </td>
                                    <td>{{ $session->BienSoXe ?? '—' }}</td>
                                    <td>{{ $start?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td>{{ $end?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td>
                                        @if ($row->valid)
                                            <span class="badge badge-success">Hợp lệ</span>
                                        @else
                                            <span class="badge badge-warning">Cảnh báo</span>
                                        @endif
                                    </td>
                                    <td class="small">
                                        @if ($row->valid && $row->matched)
                                            Khớp lịch
                                            {{ $row->matched->NgayBD?->format('d/m/Y H:i') }}
                                            →
                                            {{ $row->matched->NgayKT?->format('d/m/Y H:i') }}
                                        @else
                                            {{ $row->message ?: '—' }}
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
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
            @else
                <p class="text-muted mb-0">Chọn mã khóa và bấm <strong>Dò</strong> để bắt đầu.</p>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $('#filter_ma_khoa_hoc').select2({
        theme: 'bootstrap4',
        placeholder: 'Chọn khóa học...',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#filter_ma_khoa_hoc').closest('.form-group'),
        language: {
            noResults: function () { return 'Không tìm thấy khóa học'; }
        }
    });
</script>
@endpush
