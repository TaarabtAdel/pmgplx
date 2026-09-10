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
                <a href="{{ route('daotao.pdt.dat.dieu-kien-do-phien') }}" class="btn btn-sm btn-outline-secondary mr-1">
                    Điều kiện dò phiên
                </a>
                @if ($filters['ma_khoa_hoc'] !== '')
                    <a href="{{ route('daotao.pdt.dat.do-phien-lich-xe.export', request()->query()) }}"
                       class="btn btn-sm btn-outline-success mr-1">
                        Xuất Excel
                    </a>
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
                    Ghép phiên DAT với lịch xe tập PMGPLX theo:
                    <strong>mã khóa học</strong> · <strong>mã giáo viên</strong> · <strong>biển số xe</strong> · <strong>ngày</strong>
                    (ngày lấy từ TG bắt đầu phiên).
                </p>
                <p class="mb-0">
                    Một ngày có nhiều khung giờ → ưu tiên khung mà <strong>TG phiên nằm trọn trong lịch</strong>;
                    nếu không có thì chọn khung có TG bắt đầu gần nhất.
                    <strong>Hợp lệ</strong> khi khớp 4 trường và TG phiên nằm trong khung lịch
                    (cho phép sớm <strong>{{ $doPhienCauHinh->ChoPhepSomPhut }}</strong> phút,
                    muộn <strong>{{ $doPhienCauHinh->ChoPhepMuonPhut }}</strong> phút —
                    <a href="{{ route('daotao.pdt.dat.dieu-kien-do-phien') }}">cấu hình</a>).
                </p>
            </div>

            <form method="GET" action="{{ route('daotao.pdt.dat.do-phien-lich-xe') }}" class="mb-3">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-4">
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
                        <label class="small text-muted mb-1" for="filter_ma_giao_vien">Giáo viên</label>
                        <select name="ma_giao_vien" id="filter_ma_giao_vien" class="form-control form-control-sm"
                                @disabled($filters['ma_khoa_hoc'] === '')>
                            <option value="">— Tất cả —</option>
                            @foreach ($giaoVienOptions as $gv)
                                <option value="{{ $gv->MaGiaoVien }}"
                                        @selected($filters['ma_giao_vien'] === $gv->MaGiaoVien)>
                                    @if (! empty($gv->HoTenGiaoVien))
                                        {{ $gv->HoTenGiaoVien }} ({{ $gv->MaGiaoVien }})
                                    @else
                                        {{ $gv->MaGiaoVien }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label class="small text-muted mb-1" for="filter_bien_so_xe">Xe</label>
                        <select name="bien_so_xe" id="filter_bien_so_xe" class="form-control form-control-sm"
                                @disabled($filters['ma_khoa_hoc'] === '')>
                            <option value="">— Tất cả —</option>
                            @foreach ($xeOptions as $xe)
                                <option value="{{ $xe }}" @selected($filters['bien_so_xe'] === $xe)>
                                    {{ $xe }}
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
                </div>
                <div class="form-row align-items-end">
                    <div class="form-group col-md-2">
                        <label class="small text-muted mb-1">Từ ngày</label>
                        <input type="date" name="tu_ngay" class="form-control form-control-sm"
                               value="{{ $filters['tu_ngay'] }}"
                               @disabled($filters['ma_khoa_hoc'] === '')>
                    </div>
                    <div class="form-group col-md-2">
                        <label class="small text-muted mb-1">Đến ngày</label>
                        <input type="date" name="den_ngay" class="form-control form-control-sm"
                               value="{{ $filters['den_ngay'] }}"
                               @disabled($filters['ma_khoa_hoc'] === '')>
                    </div>
                    <div class="form-group col-md-8 mb-0">
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
                    @if ($filters['ma_giao_vien'] !== '')
                        <span class="mr-3">
                            GV: <strong>{{ $filters['ma_giao_vien'] }}</strong>
                        </span>
                    @endif
                    @if ($filters['bien_so_xe'] !== '')
                        <span class="mr-3">
                            Xe: <strong>{{ $filters['bien_so_xe'] }}</strong>
                        </span>
                    @endif
                    @if ($filters['tu_ngay'] !== '' || $filters['den_ngay'] !== '')
                        <span class="mr-3">
                            Ngày:
                            @if ($filters['tu_ngay'] !== '' && $filters['den_ngay'] !== '')
                                <strong>{{ $filters['tu_ngay'] }}</strong> → <strong>{{ $filters['den_ngay'] }}</strong>
                            @elseif ($filters['tu_ngay'] !== '')
                                từ <strong>{{ $filters['tu_ngay'] }}</strong>
                            @else
                                đến <strong>{{ $filters['den_ngay'] }}</strong>
                            @endif
                        </span>
                    @endif
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
                                <th>Giáo viên</th>
                                <th>Giáo viên (lịch)</th>
                                <th>Xe</th>
                                <th>TG bắt đầu</th>
                                <th>TG kết thúc</th>
                                <th>TG bắt đầu (lịch)</th>
                                <th>TG kết thúc (lịch)</th>
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
                                    $lich = $row->displaySchedule;
                                    $lichStart = $lich?->NgayBD;
                                    $lichEnd = $lich?->NgayKT;
                                @endphp
                                <tr @class(['dat-do-lich-row-canh-bao' => ! $row->valid])>
                                    <td>{{ ($items->firstItem() ?? 0) + $loop->index }}</td>
                                    <td><code>{{ $session->MaPhienHoc }}</code></td>
                                    <td>
                                        <div>{{ $session->HoTenHocVien ?? '—' }}</div>
                                        <small class="text-muted">{{ $session->MaHocVien ?? '' }}</small>
                                    </td>
                                    <td>
                                        <div>{{ $session->HoTenGiaoVien ?? '—' }}</div>
                                        <small class="text-muted">{{ $session->MaGiaoVien ?? '' }}</small>
                                    </td>
                                    <td>
                                        @if ($lich)
                                            <div>{{ $lich->TenGV ?: '—' }}</div>
                                            <small class="text-muted">{{ $lich->MaGV ?? '' }}</small>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $session->BienSoXe ?? '—' }}</td>
                                    <td>{{ $start?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td>{{ $end?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td>{{ $lichStart?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td>{{ $lichEnd?->format('d/m/Y H:i') ?? '—' }}</td>
                                    <td>
                                        @if ($row->valid)
                                            <span class="badge badge-success">Hợp lệ</span>
                                        @else
                                            <span class="badge badge-warning">Cảnh báo</span>
                                        @endif
                                    </td>
                                    <td class="small">
                                        @if ($row->valid)
                                            Khớp lịch xe tập
                                        @else
                                            {{ $row->message ?: '—' }}
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="text-center py-4 text-muted">
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

    $('#filter_ma_giao_vien').select2({
        theme: 'bootstrap4',
        placeholder: 'Tất cả giáo viên...',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#filter_ma_giao_vien').closest('.form-group'),
        language: {
            noResults: function () { return 'Không tìm thấy giáo viên'; }
        }
    });

    $('#filter_bien_so_xe').select2({
        theme: 'bootstrap4',
        placeholder: 'Tất cả xe...',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#filter_bien_so_xe').closest('.form-group'),
        language: {
            noResults: function () { return 'Không tìm thấy xe'; }
        }
    });
</script>
@endpush
