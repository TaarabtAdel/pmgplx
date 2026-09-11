@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Theo dõi DAT')

@push('styles')
<style>
    .dat-theo-doi-filter .form-group {
        margin-bottom: 0.75rem;
    }
    .dat-theo-doi-table {
        font-size: 0.8125rem;
    }
    .dat-theo-doi-table th,
    .dat-theo-doi-table td {
        vertical-align: middle;
        text-align: center;
        white-space: nowrap;
    }
    .dat-theo-doi-table thead th {
        background: #d9e8f7;
        color: #1a3a5c;
        font-weight: 600;
        border-color: #b8cfe6 !important;
    }
    .dat-theo-doi-table .col-ho-ten,
    .dat-theo-doi-table .col-gvth,
    .dat-theo-doi-table .col-cung-duong {
        text-align: left;
        white-space: normal;
    }
    .dat-theo-doi-table .cell-sub {
        display: block;
        font-size: 0.75rem;
        color: #6c757d;
        margin-top: 0.15rem;
    }
    .dat-theo-doi-table .cell-server {
        background: #e8f5e9;
    }
    .dat-theo-doi-table .cell-gvth {
        background: #fff;
    }
    .dat-theo-doi-table .cell-placeholder {
        color: #6c757d;
    }
    .dat-theo-doi-date-head {
        background: #eef4fb;
        font-weight: 600;
    }
    .dat-theo-doi-row-canh-bao {
        background: #fff3cd !important;
    }
    .dat-theo-doi-row-canh-bao .cell-server {
        background: #ffe69c;
    }
</style>
@endpush

@section('content')
    <div class="card card-panel mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <span>Theo dõi DAT</span>
            <div class="mt-1 mt-md-0">
                <a href="{{ route('daotao.pdt.dat.tong-hop-hoc-vien') }}" class="btn btn-sm btn-outline-secondary mr-1">
                    Tổng hợp học viên
                </a>
                <a href="{{ route('daotao.pdt.dat.quan-ly-phien') }}" class="btn btn-sm btn-navy">
                    Chi tiết phiên
                </a>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('daotao.pdt.dat.theo-doi') }}" class="mb-3" id="datTheoDoiFilterForm">
                <div class="border rounded p-3 bg-white dat-theo-doi-filter">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                        <strong class="small mb-2 mb-md-0">Chọn khóa và ngày theo dõi</strong>
                        <div class="d-flex flex-wrap">
                            <button type="submit" class="btn btn-sm btn-navy mr-2 mb-2 mb-md-0">Xem báo cáo</button>
                            @if (($filters['ma_khoa_hoc'] ?? '') !== '')
                                <a href="{{ route('daotao.pdt.dat.theo-doi.export', request()->query()) }}"
                                   class="btn btn-sm btn-outline-success mr-2 mb-2 mb-md-0">
                                    Xuất Excel
                                </a>
                            @endif
                            <a href="{{ route('daotao.pdt.dat.theo-doi') }}"
                               class="btn btn-sm btn-outline-secondary mb-2 mb-md-0">
                                Reset
                            </a>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label class="small text-muted mb-1" for="filter_ma_khoa_hoc">Chọn khóa</label>
                            <select name="ma_khoa_hoc" id="filter_ma_khoa_hoc" class="form-control form-control-sm" required>
                                <option value="">— Chọn khóa học —</option>
                                @foreach ($khoaHocOptions as $kh)
                                    <option value="{{ $kh->MaKhoaHoc }}"
                                            @selected(($filters['ma_khoa_hoc'] ?? '') === $kh->MaKhoaHoc)>
                                        @if (! empty($kh->TenKhoaHoc))
                                            {{ $kh->TenKhoaHoc }} ({{ $kh->MaKhoaHoc }})
                                        @else
                                            {{ $kh->MaKhoaHoc }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="small text-muted mb-1" for="filter_ma_giao_vien">Giáo viên</label>
                            <select name="ma_giao_vien" id="filter_ma_giao_vien"
                                    class="form-control form-control-sm"
                                    @disabled(($filters['ma_khoa_hoc'] ?? '') === '')>
                                <option value="">— Tất cả —</option>
                                @foreach ($giaoVienOptions as $maGv)
                                    @php
                                        $gv = $giaoVienNames[$maGv] ?? null;
                                        $tenGv = $gv ? trim(($gv->HoTenDem ?? '').' '.($gv->TenGV ?? '')) : '';
                                    @endphp
                                    <option value="{{ $maGv }}" @selected(($filters['ma_giao_vien'] ?? '') === $maGv)>
                                        @if ($tenGv !== '')
                                            {{ $tenGv }} ({{ $maGv }})
                                        @else
                                            {{ $maGv }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="small text-muted mb-1" for="filter_bien_so_xe">Biển số xe</label>
                            <select name="bien_so_xe" id="filter_bien_so_xe"
                                    class="form-control form-control-sm"
                                    @disabled(($filters['ma_khoa_hoc'] ?? '') === '')>
                                <option value="">— Tất cả —</option>
                                @foreach ($bienSoXeOptions as $xe)
                                    <option value="{{ $xe }}" @selected(($filters['bien_so_xe'] ?? '') === $xe)>
                                        {{ $xe }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label class="small text-muted mb-1" for="filter_ngay">Chọn ngày (tùy chọn)</label>
                            <input type="date" name="ngay" id="filter_ngay"
                                   class="form-control form-control-sm"
                                   value="{{ $filters['ngay'] ?? '' }}">
                            <small class="text-muted">Để trống = tổng toàn khóa. Chọn ngày để thêm cột chi tiết ngày.</small>
                        </div>
                        <div class="form-group col-md-4 d-flex align-items-end">
                            <input type="hidden" name="chi_cong_phien_dat" value="0">
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox"
                                       class="custom-control-input"
                                       name="chi_cong_phien_dat"
                                       id="filter_chi_cong_phien_dat"
                                       value="1"
                                       @checked($filters['chi_cong_phien_dat'] ?? true)>
                                <label class="custom-control-label small" for="filter_chi_cong_phien_dat">
                                    Chỉ cộng phiên đạt
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            @if ($canShowReport)
                <div class="mb-2 small">
                    <strong>Khóa:</strong>
                    {{ $tenKhoaHoc !== '' ? $tenKhoaHoc : $filters['ma_khoa_hoc'] }}
                    <span class="text-muted">({{ $filters['ma_khoa_hoc'] }})</span>
                    ·
                    <strong>Phạm vi:</strong>
                    @if ($hasNgayFilter)
                        Tổng toàn khóa + chi tiết ngày {{ $ngayHeading }}
                    @else
                        Tổng toàn khóa (tất cả ngày)
                    @endif
                    @if ($filters['chi_cong_phien_dat'] ?? true)
                        · <span class="badge badge-success">Chỉ phiên đạt</span>
                    @else
                        · <span class="badge badge-secondary">Tất cả phiên</span>
                    @endif
                    @if (($filters['ma_giao_vien'] ?? '') !== '')
                        @php
                            $maGvFilter = $filters['ma_giao_vien'];
                            $gvFilter = $giaoVienNames[$maGvFilter] ?? null;
                            $tenGvFilter = $gvFilter ? trim(($gvFilter->HoTenDem ?? '').' '.($gvFilter->TenGV ?? '')) : '';
                        @endphp
                        · <strong>GV:</strong>
                        @if ($tenGvFilter !== '')
                            {{ $tenGvFilter }} (<code>{{ $maGvFilter }}</code>)
                        @else
                            <code>{{ $maGvFilter }}</code>
                        @endif
                    @endif
                    @if (($filters['bien_so_xe'] ?? '') !== '')
                        · <strong>Xe:</strong> <code>{{ $filters['bien_so_xe'] }}</code>
                    @endif
                </div>

                @if ($groups === [])
                    <p class="text-muted mb-0">
                        @if (! ($hasPhanCong ?? false))
                            Chưa có phân công học viên cho khóa này.
                            <a href="{{ route('daotao.pdt.dat.phan-cong-hoc-vien', ['ma_khoa_hoc' => $filters['ma_khoa_hoc']]) }}">
                                Nhập phân công
                            </a>
                        @elseif (($filters['ma_giao_vien'] ?? '') !== '' || ($filters['bien_so_xe'] ?? '') !== '')
                            Không có phân công phù hợp bộ lọc giáo viên / xe đã chọn.
                        @else
                            Không có dữ liệu hiển thị.
                        @endif
                    </p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered dat-theo-doi-table mb-0">
                            <thead>
                                <tr>
                                    <th rowspan="2">STT</th>
                                    <th class="col-ho-ten">Họ và tên<br>học viên</th>
                                    <th class="col-gvth">GVTH</th>
                                    <th rowspan="2">BKS</th>
                                    <th rowspan="2">Số giờ tự động<br>máy chủ ghi nhận</th>
                                    <th rowspan="2">Số km tự động<br>ghi nhận</th>
                                    <th rowspan="2">Số giờ đêm<br>ghi nhận</th>
                                    <th rowspan="2">Số km đêm<br>ghi nhận</th>
                                    <th rowspan="2">Tổng<br>Số giờ GVTH</th>
                                    <th rowspan="2">Tổng<br>Số KM GVTH</th>
                                    @if ($hasNgayFilter)
                                        <th colspan="3" class="dat-theo-doi-date-head">{{ $ngayHeading }}</th>
                                    @endif
                                    <th rowspan="2" class="col-cung-duong">Cung đường theo<br>lịch giảng dạy</th>
                                    <th rowspan="2" class="col-cung-duong">Cung đường<br>giáo viên chạy</th>
                                </tr>
                                <tr>
                                    <th class="col-ho-ten">Mã học viên</th>
                                    <th class="col-gvth">Mã giáo viên</th>
                                    @if ($hasNgayFilter)
                                        <th>Số giờ<br>trong ngày</th>
                                        <th>Số km<br>trong ngày</th>
                                        <th>Tổng số km<br>trong ngày</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($groups as $group)
                                    @php $rowspan = count($group['students']); @endphp
                                    @foreach ($group['students'] as $index => $student)
                                        <tr @class(['dat-theo-doi-row-canh-bao' => ! empty($student['ngoai_phan_cong'])])>
                                            <td>{{ $student['stt'] }}</td>
                                            <td class="col-ho-ten text-left">
                                                <div>{{ $student['ho_ten'] }}</div>
                                                <span class="cell-sub"><code>{{ $student['ma_hoc_vien'] ?: '—' }}</code></span>
                                                @if (! empty($student['ngoai_phan_cong']))
                                                    <span class="badge badge-warning mt-1">Chưa phân công</span>
                                                @endif
                                            </td>
                                            @if ($index === 0)
                                                <td class="col-gvth text-left" rowspan="{{ $rowspan }}">
                                                    <div>{{ $group['ho_ten_giao_vien'] }}</div>
                                                    <span class="cell-sub"><code>{{ $group['ma_giao_vien'] ?: '—' }}</code></span>
                                                </td>
                                                <td rowspan="{{ $rowspan }}">{{ $group['bien_so_xe'] }}</td>
                                            @endif
                                            <td class="cell-server">{{ $student['gio_tu_dong'] }}</td>
                                            <td class="cell-server">{{ $student['km_may_chu'] }}</td>
                                            <td class="cell-server">{{ $student['chay_dem'] }}</td>
                                            <td class="cell-server @if($student['km_dem'] === '—') cell-placeholder @endif">{{ $student['km_dem'] }}</td>
                                            @if ($index === 0)
                                                <td class="cell-gvth" rowspan="{{ $rowspan }}">{{ $group['gio_gvth'] }}</td>
                                                <td class="cell-gvth" rowspan="{{ $rowspan }}">{{ $group['km_gvth'] }}</td>
                                            @endif
                                            @if ($hasNgayFilter)
                                                <td>{{ $student['gio_trong_ngay'] }}</td>
                                                <td>{{ $student['km_trong_ngay'] }}</td>
                                                @if ($index === 0)
                                                    <td rowspan="{{ $rowspan }}">{{ $group['tong_km_ngay'] }}</td>
                                                @endif
                                            @endif
                                            @if ($index === 0)
                                                <td class="col-cung-duong cell-placeholder" rowspan="{{ $rowspan }}">
                                                    {{ $group['cung_duong_lich'] }}
                                                </td>
                                                <td class="col-cung-duong cell-placeholder" rowspan="{{ $rowspan }}">
                                                    {{ $group['cung_duong_gv'] }}
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="small text-muted mt-2 mb-0">
                        @if ($filters['chi_cong_phien_dat'] ?? true)
                            <strong>Chỉ cộng phiên đạt:</strong> bỏ qua phiên có cảnh báo (cùng điều kiện trang Quản lý phiên).
                        @else
                            <strong>Tất cả phiên:</strong> cộng mọi phiên, kể cả phiên bị cảnh báo.
                        @endif
                        Các cột tự động, đêm và tổng GVTH = tổng <strong>toàn khóa</strong> (theo phiên HV).
                        <strong>Mã HV, Mã GV, BKS</strong> cố định theo
                        <a href="{{ route('daotao.pdt.dat.phan-cong-hoc-vien', ['ma_khoa_hoc' => $filters['ma_khoa_hoc']]) }}">phân công học viên</a>.
                        Dòng <span class="badge badge-warning">nền vàng</span> = HV có phiên với GV/xe nhóm nhưng không nằm trong phân công nhóm đó.
                        @if ($hasNgayFilter)
                            Cột ngày {{ $ngayHeading }} = giờ/km các phiên HV trong ngày đó.
                        @endif
                        Giờ/km tự động và đêm lấy từ phiên có <code>LaTuDong</code> / <code>LaBanDem</code> = 1.
                        Các cột chưa có quy tắc tính hiển thị <strong>—</strong>.
                    </p>
                @endif
            @else
                <p class="text-muted mb-0">Chọn <strong>khóa học</strong> để xem báo cáo theo dõi DAT.</p>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
    var datTheoDoiSkipSubmit = false;

    function datTheoDoiSubmitIfCourseSelected() {
        if (datTheoDoiSkipSubmit) {
            return;
        }
        if ($('#filter_ma_khoa_hoc').val()) {
            $('#datTheoDoiFilterForm').trigger('submit');
        }
    }

    function datTheoDoiInitSelect2($el, placeholder) {
        if ($el.prop('disabled')) {
            return;
        }

        $el.select2({
            theme: 'bootstrap4',
            placeholder: placeholder,
            allowClear: true,
            width: '100%',
            dropdownParent: $el.closest('.form-group'),
            language: {
                noResults: function () { return 'Không tìm thấy'; }
            }
        });
    }

    datTheoDoiInitSelect2($('#filter_ma_khoa_hoc'), 'Chọn khóa học...');

    $('#filter_ma_khoa_hoc').on('change', function () {
        if (this.value) {
            datTheoDoiSkipSubmit = true;
            $('#filter_ma_giao_vien, #filter_bien_so_xe').val(null).trigger('change');
            datTheoDoiSkipSubmit = false;
            datTheoDoiSubmitIfCourseSelected();
        }
    });

    datTheoDoiInitSelect2($('#filter_ma_giao_vien'), '— Tất cả giáo viên —');
    datTheoDoiInitSelect2($('#filter_bien_so_xe'), '— Tất cả xe —');

    $('#filter_ma_giao_vien, #filter_bien_so_xe').on('change', datTheoDoiSubmitIfCourseSelected);
    $('#filter_ngay, #filter_chi_cong_phien_dat').on('change', datTheoDoiSubmitIfCourseSelected);
</script>
@endpush
