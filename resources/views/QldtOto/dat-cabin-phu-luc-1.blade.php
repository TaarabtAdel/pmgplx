@extends('PMGPLX.layouts.quan-ly')

@section('title', $item['title'])

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-white border py-2 small mb-3">
            <li class="breadcrumb-item"><a href="{{ route('qldtoto.index') }}">QLĐT Ô TÔ</a></li>
            @foreach (array_slice($duongDan, 1) as $buoc)
                <li class="breadcrumb-item">{{ $buoc }}</li>
            @endforeach
            <li class="breadcrumb-item active" aria-current="page">{{ $item['title'] }}</li>
        </ol>
    </nav>

    <div class="card card-panel mb-3">
        <div class="card-header">{{ $item['title'] }}</div>
        <div class="card-body">
            <div class="alert alert-info small py-2 mb-3">
                Mẫu Word <code>bao-cao-hoc-vien-dat.goc.docx</code> chỉ là khung — dữ liệu phiên lấy từ
                <a href="{{ route('daotao.pdt.dat.quan-ly-phien') }}" target="_blank" rel="noopener">Quản lý phiên DAT</a>
                với <strong>cùng tham số lọc</strong> (mã khóa, học viên <code>mã HV|mã khóa</code>, từ–đến ngày, Đạt/Không đạt, …).
                Phiên không đạt → ghi chú <strong>Loại</strong>; tổng giờ/km chỉ cộng phiên đạt.
            </div>

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <form method="GET" action="{{ route('qldtoto.4-dat-cabin-phu-luc-1.index') }}" class="mb-3" id="form-loc-pl1">
                <div class="border rounded p-3 bg-white">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                        <strong class="small">Lọc phiên DAT</strong>
                        <div>
                            <button type="submit" class="btn btn-sm btn-navy mr-1">Lọc</button>
                            <a href="{{ route('qldtoto.4-dat-cabin-phu-luc-1.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label class="small text-muted mb-1" for="filter_ma_khoa_hoc">Mã khóa học <span class="text-danger">*</span></label>
                            <select name="ma_khoa_hoc" id="filter_ma_khoa_hoc" class="form-control form-control-sm" required>
                                <option value="">— Chọn khóa —</option>
                                @foreach ($khoaHocOptions as $kh)
                                    <option value="{{ $kh->MaKhoaHoc }}" @selected(($filters['ma_khoa_hoc'] ?? '') === $kh->MaKhoaHoc)>
                                        {{ $kh->TenKhoaHoc ?: $kh->MaKhoaHoc }} ({{ $kh->MaKhoaHoc }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label class="small text-muted mb-1" for="filter_ma_hoc_vien">Mã học viên</label>
                            <select name="ma_hoc_vien" id="filter_ma_hoc_vien" class="form-control form-control-sm">
                                <option value="">— Tất cả —</option>
                                @if ($selectedHocVienOption)
                                    <option value="{{ $selectedHocVienOption['id'] }}" selected>{{ $selectedHocVienOption['text'] }}</option>
                                @endif
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label class="small text-muted mb-1" for="filter_ma_phien">Mã phiên</label>
                            <input type="text" name="ma_phien" id="filter_ma_phien" class="form-control form-control-sm"
                                   value="{{ $filters['ma_phien'] ?? '' }}" placeholder="Tìm mã phiên…">
                        </div>
                        <div class="form-group col-md-2">
                            <label class="small text-muted mb-1" for="filter_ma_giao_vien">Giáo viên</label>
                            <select name="ma_giao_vien" id="filter_ma_giao_vien" class="form-control form-control-sm">
                                <option value="">— Tất cả —</option>
                                @foreach ($giaoVienOptions as $gv)
                                    <option value="{{ $gv->MaGiaoVien }}" @selected(($filters['ma_giao_vien'] ?? '') === $gv->MaGiaoVien)>
                                        {{ $gv->HoTenGiaoVien ?: $gv->MaGiaoVien }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label class="small text-muted mb-1">Đạt</label>
                            <select name="dat" class="form-control form-control-sm" @disabled(! $canAnalyzeViolations)>
                                <option value="" @selected(($filters['dat'] ?? '') === '')>— Tất cả —</option>
                                <option value="dat" @selected(($filters['dat'] ?? '') === 'dat')>Đạt</option>
                                <option value="chua_dat" @selected(($filters['dat'] ?? '') === 'chua_dat')>Không đạt</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-2">
                            <label class="small text-muted mb-1">Từ ngày</label>
                            <input type="date" name="tu_ngay" class="form-control form-control-sm" value="{{ $filters['tu_ngay'] }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label class="small text-muted mb-1">Đến ngày</label>
                            <input type="date" name="den_ngay" class="form-control form-control-sm" value="{{ $filters['den_ngay'] }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label class="small text-muted mb-1" for="filter_loai_khoa_hoc">Loại khóa</label>
                            <select name="loai_khoa_hoc" id="filter_loai_khoa_hoc" class="form-control form-control-sm">
                                <option value="">— Tất cả —</option>
                                @foreach ($loaiKhoaHocOptions as $loai)
                                    <option value="{{ $loai }}" @selected(($filters['loai_khoa_hoc'] ?? '') === $loai)>{{ $loai }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label class="small text-muted mb-1" for="filter_bien_so_xe">Biển số xe</label>
                            <select name="bien_so_xe" id="filter_bien_so_xe" class="form-control form-control-sm">
                                <option value="">— Tất cả —</option>
                                @foreach ($xeOptions as $xe)
                                    <option value="{{ $xe }}" @selected(($filters['bien_so_xe'] ?? '') === $xe)>{{ $xe }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label class="small text-muted mb-1" for="ngay_bao_cao">Ngày báo cáo (Word)</label>
                            <input type="date" class="form-control form-control-sm" name="ngay_bao_cao" id="ngay_bao_cao"
                                   value="{{ $ngay_bao_cao }}">
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-panel">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <span>Danh sách học viên (theo phiên đã lọc)</span>
            <span class="small text-muted">
                @if (($filters['ma_khoa_hoc'] ?? '') === '')
                    Chọn khóa học rồi bấm Lọc
                @else
                    {{ count($hocViens) }} học viên
                    @if ($tongPhienLoc !== null)
                        · {{ $tongPhienLoc }} phiên
                    @endif
                @endif
            </span>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('qldtoto.4-dat-cabin-phu-luc-1.export') }}" id="form-xuat-pl1">
                @csrf
                @foreach (['ma_khoa_hoc', 'ma_phien', 'ma_giao_vien', 'loai_khoa_hoc', 'bien_so_xe', 'tu_ngay', 'den_ngay', 'dat'] as $key)
                    <input type="hidden" name="{{ $key }}" value="{{ $filters[$key] ?? '' }}">
                @endforeach
                <input type="hidden" name="ma_hoc_vien" value="{{ $filters['ma_hoc_vien'] ?? '' }}">
                <input type="hidden" name="ngay_bao_cao" value="{{ $ngay_bao_cao }}" id="hidden_ngay_bao_cao">

                <div class="mb-2">
                    <button type="submit" class="btn btn-navy btn-sm" @disabled(count($hocViens) === 0)>
                        Xuất ZIP / Word
                    </button>
                    <span class="small text-muted ml-2" id="pl1-chon-count">Đã chọn: 0 (bỏ chọn = xuất cả bảng)</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover mb-0">
                        <thead class="thead-light">
                        <tr>
                            <th class="text-center" style="width: 2.5rem;">
                                <input type="checkbox" id="chk-all-pl1" aria-label="Chọn tất cả">
                            </th>
                            <th>STT</th>
                            <th>Mã HV</th>
                            <th>Họ tên</th>
                            <th class="text-right">Số phiên</th>
                            <th class="text-right">Tổng giờ (đạt)</th>
                            <th class="text-right">Tổng km (đạt)</th>
                            <th>Kết luận CT</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($hocViens as $i => $row)
                            @php
                                $queryXuat = array_filter([
                                    'ma_khoa_hoc' => $filters['ma_khoa_hoc'] ?? '',
                                    'ma_hoc_vien' => $row['ma_loc'],
                                    'ma_phien' => $filters['ma_phien'] ?? '',
                                    'ma_giao_vien' => $filters['ma_giao_vien'] ?? '',
                                    'loai_khoa_hoc' => $filters['loai_khoa_hoc'] ?? '',
                                    'bien_so_xe' => $filters['bien_so_xe'] ?? '',
                                    'tu_ngay' => $filters['tu_ngay'] ?? '',
                                    'den_ngay' => $filters['den_ngay'] ?? '',
                                    'dat' => $filters['dat'] ?? '',
                                    'ngay_bao_cao' => $ngay_bao_cao,
                                ], static fn ($v) => $v !== null && $v !== '');
                            @endphp
                            <tr>
                                <td class="text-center align-middle">
                                    <input type="checkbox" class="chk-hv-pl1" name="ma_hoc_vien_chon[]" value="{{ $row['ma_loc'] }}">
                                </td>
                                <td>{{ $i + 1 }}</td>
                                <td><code>{{ $row['ma_hoc_vien'] }}</code></td>
                                <td>{{ $row['ho_ten'] ?: '—' }}</td>
                                <td class="text-right">{{ $row['so_phien'] }}</td>
                                <td class="text-right">{{ \App\Support\DaoTao\DatHocVienTongHop::formatNumber($row['tong_gio']) }}</td>
                                <td class="text-right">{{ \App\Support\DaoTao\DatHocVienTongHop::formatNumber($row['tong_km']) }}</td>
                                <td>{{ $row['ket_luan'] }}</td>
                                <td class="text-nowrap">
                                    <a href="{{ route('qldtoto.4-dat-cabin-phu-luc-1.export-mot', $queryXuat) }}"
                                       class="btn btn-sm btn-outline-success">Xuất Word</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    @if (($filters['ma_khoa_hoc'] ?? '') === '')
                                        Chọn <strong>mã khóa học</strong> và bấm Lọc để tải danh sách học viên từ DAT.
                                    @else
                                        Không có phiên / học viên với bộ lọc hiện tại.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
@include('DaoTao.dat.partials.phien-filter-scripts')
<script>
(function () {
    var $ngay = $('#ngay_bao_cao');
    var $hidden = $('#hidden_ngay_bao_cao');
    if ($ngay.length && $hidden.length) {
        $ngay.on('change', function () { $hidden.val($ngay.val()); });
    }

    var $all = $('#chk-all-pl1');
    var $boxes = $('.chk-hv-pl1');
    var $count = $('#pl1-chon-count');
    function capNhatDem() {
        var n = $('.chk-hv-pl1:checked').length;
        $count.text('Đã chọn: ' + n + ' (bỏ chọn = xuất cả bảng)');
    }
    $all.on('change', function () { $boxes.prop('checked', $all.prop('checked')); capNhatDem(); });
    $boxes.on('change', function () {
        $all.prop('checked', $boxes.length > 0 && $boxes.filter(':checked').length === $boxes.length);
        capNhatDem();
    });
    capNhatDem();
})();
</script>
@endpush