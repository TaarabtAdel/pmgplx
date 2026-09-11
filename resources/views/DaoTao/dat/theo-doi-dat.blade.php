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
                            <a href="{{ route('daotao.pdt.dat.theo-doi') }}"
                               class="btn btn-sm btn-outline-secondary mb-2 mb-md-0">
                                Reset
                            </a>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-5">
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
                            <label class="small text-muted mb-1" for="filter_ngay">Chọn ngày</label>
                            <input type="date" name="ngay" id="filter_ngay"
                                   class="form-control form-control-sm"
                                   value="{{ $filters['ngay'] ?? '' }}">
                        </div>
                    </div>
                </div>
            </form>

            @if (($filters['ma_khoa_hoc'] ?? '') !== '' && ($filters['ngay'] ?? '') === '')
                <p class="text-muted mb-0">Chọn <strong>ngày</strong> rồi bấm <strong>Xem báo cáo</strong>.</p>
            @elseif ($canShowReport)
                <div class="mb-2 small">
                    <strong>Khóa:</strong>
                    {{ $tenKhoaHoc !== '' ? $tenKhoaHoc : $filters['ma_khoa_hoc'] }}
                    <span class="text-muted">({{ $filters['ma_khoa_hoc'] }})</span>
                    ·
                    <strong>Ngày:</strong> {{ $ngayHeading }}
                </div>

                @if ($groups === [])
                    <p class="text-muted mb-0">Không có phiên nào trong ngày đã chọn.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered dat-theo-doi-table mb-0">
                            <thead>
                                <tr>
                                    <th rowspan="2">STT</th>
                                    <th rowspan="2" class="col-ho-ten">Họ và tên học viên</th>
                                    <th rowspan="2" class="col-gvth">GVTH</th>
                                    <th rowspan="2">BKS</th>
                                    <th rowspan="2">Số giờ tự động<br>máy chủ ghi nhận</th>
                                    <th rowspan="2">Số km máy chủ<br>ghi nhận</th>
                                    <th rowspan="2">Chạy đêm</th>
                                    <th rowspan="2">Số KM</th>
                                    <th rowspan="2">Số giờ GVTH</th>
                                    <th rowspan="2">Số KM GVTH</th>
                                    <th colspan="3" class="dat-theo-doi-date-head">{{ $ngayHeading }}</th>
                                    <th rowspan="2" class="col-cung-duong">Cung đường theo<br>lịch giảng dạy</th>
                                    <th rowspan="2" class="col-cung-duong">Cung đường<br>giáo viên chạy</th>
                                </tr>
                                <tr>
                                    <th>Số giờ<br>trong ngày</th>
                                    <th>Số km<br>trong ngày</th>
                                    <th>Tổng số km<br>trong ngày</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($groups as $group)
                                    @php $rowspan = count($group['students']); @endphp
                                    @foreach ($group['students'] as $index => $student)
                                        <tr>
                                            <td>{{ $student['stt'] }}</td>
                                            <td class="col-ho-ten text-left">{{ $student['ho_ten'] }}</td>
                                            @if ($index === 0)
                                                <td class="col-gvth text-left" rowspan="{{ $rowspan }}">
                                                    {{ $group['ho_ten_giao_vien'] }}
                                                </td>
                                                <td rowspan="{{ $rowspan }}">{{ $group['bien_so_xe'] }}</td>
                                            @endif
                                            <td class="cell-server">{{ $student['gio_tu_dong'] }}</td>
                                            <td class="cell-server">{{ $student['km_may_chu'] }}</td>
                                            <td class="cell-server">{{ $student['chay_dem'] }}</td>
                                            <td class="cell-server cell-placeholder">{{ $student['so_km'] }}</td>
                                            @if ($index === 0)
                                                <td class="cell-gvth" rowspan="{{ $rowspan }}">{{ $group['gio_gvth'] }}</td>
                                                <td class="cell-gvth" rowspan="{{ $rowspan }}">{{ $group['km_gvth'] }}</td>
                                                <td class="cell-placeholder" rowspan="{{ $rowspan }}">{{ $group['gio_trong_ngay'] }}</td>
                                                <td class="cell-placeholder" rowspan="{{ $rowspan }}">{{ $group['km_trong_ngay'] }}</td>
                                                <td rowspan="{{ $rowspan }}">{{ $group['tong_km_ngay'] }}</td>
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
                        Các cột chưa có quy tắc tính hiển thị <strong>—</strong>.
                        Số liệu máy chủ lấy từ phiên DAT trong khóa và ngày đã chọn.
                    </p>
                @endif
            @else
                <p class="text-muted mb-0">Chọn <strong>khóa học</strong> và <strong>ngày</strong> để xem báo cáo theo dõi DAT.</p>
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
    }).on('change', function () {
        if (this.value) {
            $('#datTheoDoiFilterForm').trigger('submit');
        }
    });
</script>
@endpush
