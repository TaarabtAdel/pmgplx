@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Quản lý học viên')

@section('content')
    <div class="card card-panel">
        <div class="card-header">Thông tin tìm kiếm học viên</div>
        <div class="card-body">
            <form method="GET" action="{{ route('pmgplx.dm.hoc-vien.index') }}">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-2">
                        <label>Mã ĐK|Họ tên|CMT|Số HS|SDT</label>
                        <input type="text" class="form-control form-control-sm" name="tu_khoa" value="{{ $filters['tu_khoa'] }}" placeholder="Có dấu hay không đều được">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="filter_ma_kh">Khóa học</label>
                        <select name="ma_kh" id="filter_ma_kh" class="form-control form-control-sm">
                            <option value="">—Tất cả—</option>
                            @foreach ($khoaHocs as $kh)
                                <option value="{{ $kh->MaKH }}" @selected($filters['ma_kh'] === $kh->MaKH)>
                                    {{ $kh->TenKH }} ({{ $kh->MaKH }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label for="filter_sale">Sale</label>
                        <select name="sale" id="filter_sale" class="form-control form-control-sm">
                            <option value="">—Tất cả—</option>
                            @foreach ($saleGoiY as $s)
                                <option value="{{ $s }}" @selected($filters['sale'] === $s)>{{ $s }}</option>
                            @endforeach
                            @if ($filters['sale'] !== '' && ! $saleGoiY->contains($filters['sale']))
                                <option value="{{ $filters['sale'] }}" selected>{{ $filters['sale'] }}</option>
                            @endif
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label>Hạng GPLX</label>
                        <input type="text" class="form-control form-control-sm" name="hang_gplx" value="{{ $filters['hang_gplx'] }}" placeholder="VD: B1, B2">
                    </div>
                    <div class="form-group col-md-2">
                        <label>Trạng thái</label>
                        <select class="form-control form-control-sm" name="trang_thai">
                            <option value="">—Tất cả—</option>
                            <option value="1" @selected($filters['trang_thai'] === '1')>Hiệu lực</option>
                            <option value="0" @selected($filters['trang_thai'] === '0')>Không hiệu lực</option>
                        </select>
                    </div>
                    <div class="form-group col-md-1">
                        <button type="submit" class="btn btn-sm btn-primary btn-block">Tìm kiếm</button>
                        <a href="{{ route('pmgplx.dm.hoc-vien.index') }}" class="btn btn-sm btn-outline-secondary btn-block mt-1" title="Làm mới">↻</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-panel">
        <div class="card-header">Danh sách học viên</div>
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center mb-3">
                <div class="btn-group btn-group-sm mr-2 mb-2" role="group">
                    <button type="button" class="btn btn-success" disabled title="Sắp hỗ trợ">＋ Thêm mới</button>
                    <button type="button" class="btn btn-warning" disabled title="Sắp hỗ trợ">✎ Xem - Sửa</button>
                    <button type="button" class="btn btn-danger" disabled title="Sắp hỗ trợ">✕ Xóa</button>
                </div>

                <div class="mb-2 mr-2">
                    <button type="button" class="btn btn-sm btn-primary mr-1 btn-do-hv" data-ma-dk="" data-ten="">
                        🔍 Dò thông tin
                    </button>
                    <a href="{{ route('pmgplx.dm.hoc-vien.nhap-file.create') }}"
                       class="btn btn-sm btn-success">
                        Nhập học viên mới từ file
                    </a>
                    <a href="{{ route('daotao.pdt.cong-cu-nhap.nhap-file-sdt-sale-hoc-vien') }}"
                       class="btn btn-sm btn-outline-success ml-1"
                       title="File Excel: F=CCCD, G=SDT, H=Sale — lưu bảng phụ MANHLINH">
                        Nhập SDT / Sale từ file
                    </a>
                    <a href="{{ route('pmgplx.dm.hoc-vien.dong-bo.form', array_filter(['ma_kh_nguon' => $filters['ma_kh']])) }}"
                       class="btn btn-sm btn-info ml-1">
                        Đồng Bộ Qua Bản Cũ
                    </a>
                    <a href="{{ route('pmgplxold.dm.hoc-vien.index') }}" class="btn btn-sm btn-outline-secondary ml-1" target="_blank">
                        Xem bản cũ
                    </a>
                </div>

                <button type="button" class="btn btn-sm btn-success mb-2 mr-2" data-toggle="modal" data-target="#modalXuatExcelHv"
                        title="Chọn cột trước khi xuất">
                    ⬇ Xuất Excel ({{ number_format($items->total()) }})
                </button>

                <div class="ml-auto d-flex flex-wrap align-items-center mb-2">
                    <span class="mr-3">Tổng số bản ghi: <strong>{{ number_format($items->total()) }}</strong></span>
                    <form method="GET" action="{{ route('pmgplx.dm.hoc-vien.index') }}" class="form-inline mr-3">
                        @foreach (request()->except(['per_page', 'page']) as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        <label class="mr-2 mb-0">Số bản ghi/trang</label>
                        <select name="per_page" class="form-control form-control-sm" onchange="this.form.submit()">
                            @foreach ([20, 50, 100, 200] as $n)
                                <option value="{{ $n }}" @selected((int) $filters['per_page'] === $n)>{{ $n }}</option>
                            @endforeach
                        </select>
                    </form>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item {{ $items->onFirstPage() ? 'disabled' : '' }}">
                                <a class="page-link" href="{{ $items->url(1) }}">«</a>
                            </li>
                            <li class="page-item {{ $items->onFirstPage() ? 'disabled' : '' }}">
                                <a class="page-link" href="{{ $items->previousPageUrl() }}">‹</a>
                            </li>
                            <li class="page-item active">
                                <span class="page-link">Trang {{ $items->currentPage() }}/{{ max($items->lastPage(), 1) }}</span>
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
            </div>

            <div class="d-flex align-items-center mb-2">
                <span class="small text-muted mr-2">Bấm <strong>+</strong> xem thêm hồ sơ · bấm cột <strong>SDT · Sale</strong> (hoặc ✎) để sửa thông tin phụ.</span>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="hv-toggle-all-details">Mở/đóng tất cả</button>
            </div>
            <div class="hv-danh-muc-wrap">
                <table class="table table-sm table-bordered table-hover hv-danh-muc-table mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width:2.25rem" title="Chi tiết"></th>
                            <th class="col-sticky sticky-1 text-center">STT</th>
                            <th class="col-sticky sticky-2">Học viên</th>
                            <th>NS · Giới tính</th>
                            <th>CCCD · Ngày cấp</th>
                            <th>SDT · Sale</th>
                            <th>Khóa · Hạng</th>
                            <th class="text-center">Đã dò</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $index => $row)
                            @php
                                $maDk = (string) $row->MaDK;
                                $hoTenHv = $row->HoVaTen ?: trim(($row->HoDemNLX ?? '').' '.($row->TenNLX ?? ''));
                                $phu = $thongTinPhu[$maDk] ?? null;
                                $gioiTinh = match (true) {
                                    $row->GioiTinh === '1', strtoupper((string) $row->GioiTinh) === 'M' => 'Nam',
                                    $row->GioiTinh === '0', strtoupper((string) $row->GioiTinh) === 'F' => 'Nữ',
                                    default => (string) $row->GioiTinh,
                                };
                            @endphp
                            <tr class="hv-row-main" data-ma-dk="{{ $maDk }}">
                                <td class="text-center align-middle">
                                    <button type="button" class="btn btn-outline-secondary btn-sm hv-expand-btn hv-detail-toggle" title="Xem thêm thông tin" aria-expanded="false">+</button>
                                </td>
                                <td class="col-sticky sticky-1 text-center text-muted">{{ $items->firstItem() + $index }}</td>
                                <td class="col-sticky sticky-2 hv-cell-stack" title="{{ $hoTenHv }} — {{ $maDk }}">
                                    <div class="hv-stack-line hv-ten">{{ $hoTenHv }}</div>
                                    <div class="hv-stack-sub text-muted">
                                        @include('PMGPLX.danh-muc._trang-thai-icon', ['active' => (string) $row->TrangThai === '1'])
                                        <span class="hv-ma-dk">{{ $row->MaDK }}</span>
                                    </div>
                                </td>
                                <td class="hv-cell-stack">
                                    <div class="hv-stack-line">{{ \App\Support\PMGPLX\NgayVn::format($row->NgaySinh, 'yyyymmdd') ?: '—' }}</div>
                                    <div class="hv-stack-sub text-muted">{{ $gioiTinh ?: '—' }}</div>
                                </td>
                                <td class="hv-cell-stack">
                                    <div class="hv-stack-line">{{ $row->SoCMT ?: '—' }}</div>
                                    <div class="hv-stack-sub text-muted">{{ \App\Support\OCR\DoThongTinGiayTo::ngayDatetime($row->NgayCapCMT) ?: '—' }}</div>
                                </td>
                                <td class="hv-cell-stack hv-open-phu-cell" role="button" tabindex="0"
                                    title="Bấm để sửa SDT / Sale"
                                    data-ma-dk="{{ $maDk }}"
                                    data-ten="{{ $hoTenHv }}"
                                    data-sdt="{{ $phu?->SoDienThoai ?? '' }}"
                                    data-sale="{{ $phu?->Sale ?? '' }}">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-grow-1 min-width-0">
                                            <div class="hv-stack-line hv-text-sdt text-truncate">{{ $phu?->SoDienThoai ?: '—' }}</div>
                                            <div class="hv-stack-sub text-muted hv-text-sale text-truncate">{{ $phu?->Sale ?: '—' }}</div>
                                        </div>
                                        <button type="button" class="btn btn-link btn-sm p-0 ml-1 text-secondary hv-open-phu flex-shrink-0"
                                                title="Sửa thông tin phụ"
                                                data-ma-dk="{{ $maDk }}"
                                                data-ten="{{ $hoTenHv }}"
                                                data-sdt="{{ $phu?->SoDienThoai ?? '' }}"
                                                data-sale="{{ $phu?->Sale ?? '' }}">✎</button>
                                    </div>
                                </td>
                                <td class="hv-cell-stack">
                                    <div class="hv-stack-line">{{ $row->MaKhoaHoc ?: '—' }}</div>
                                    <div class="hv-stack-sub text-muted">{{ $row->HangGPLX ?: ($row->HangDaoTao ?: '—') }}</div>
                                </td>
                                <td class="text-center text-nowrap">
                                    @php $doRoi = $daDo[$maDk] ?? null; @endphp
                                    @if ($doRoi)
                                        @php
                                            $nhanDo = collect($doRoi->trangThai())
                                                ->map(fn ($khop, $k) => (\App\Support\OCR\DoThongTinGiayTo::TRUONG[$k][0] ?? $k).': '.($khop ? 'Khớp' : 'Không khớp'))
                                                ->implode("\n");
                                            $soLech = $doRoi->soTruongLech();
                                            $canChinh = $doRoi->KetLuan === \App\Models\DaoTao\HocVienDoThongTin::CAN_CHINH;
                                        @endphp
                                        <span class="dtt-da-do mr-1" style="cursor: help;"
                                              title="Đã dò {{ $doRoi->NgayCapNhat?->copy()->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }} — {{ $canChinh ? 'Cần chỉnh' : 'OK' }}{{ $soLech > 0 ? ' ('.$soLech.' trường không khớp)' : '' }}&#10;{{ $nhanDo }}">
                                            @if ($canChinh)
                                                <span class="badge badge-warning">Cần chỉnh</span>
                                            @else
                                                <span class="text-success font-weight-bold" style="font-size: 1.1rem;">✓</span>
                                            @endif
                                        </span>
                                    @endif
                                    <button type="button" class="btn btn-sm btn-outline-primary py-0 btn-do-hv"
                                            data-ma-dk="{{ $row->MaDK }}" data-ten="{{ $hoTenHv }}" title="Dò CCCD / GPLX cho học viên này">Dò</button>
                                </td>
                            </tr>
                            <tr class="hv-danh-muc-detail d-none" data-detail-for="{{ $maDk }}">
                                <td colspan="8">
                                    <dl>
                                        <div>
                                            <dt>Nơi thường trú</dt>
                                            <dd>{{ $noiThuongTru[$maDk] ?? '' }}</dd>
                                        </div>
                                        <div>
                                            <dt>Số hồ sơ</dt>
                                            <dd>{{ $row->SoHoSo }}</dd>
                                        </div>
                                        <div>
                                            <dt>Hạng đào tạo</dt>
                                            <dd>{{ $row->HangDaoTao }}</dd>
                                        </div>
                                        <div>
                                            <dt>Trạng thái</dt>
                                            <dd>{{ (string) $row->TrangThai === '1' ? 'Hiệu lực' : 'Không hiệu lực' }}</dd>
                                        </div>
                                    </dl>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">Không có dữ liệu</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @include('PMGPLX.danh-muc.partials.hoc-vien-xuat-excel-modal', [
        'exportRoute' => route('pmgplx.dm.hoc-vien.export'),
        'filters' => $filters,
        'exportColumns' => $exportColumns,
        'total' => $items->total(),
        'cotKhacTieuDe' => 'Cột PMGPLX khác',
    ])

    <div class="modal fade" id="modalThongTinPhu" tabindex="-1" role="dialog" aria-labelledby="modalThongTinPhuLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title" id="modalThongTinPhuLabel">Thông tin phụ (MANHLINH)</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3" id="hv-phu-subtitle"></p>
                    <input type="hidden" id="hv-phu-ma-dk" value="">
                    <div class="form-group">
                        <label class="small mb-0" for="hv-phu-sdt">SDT</label>
                        <input type="text" class="form-control form-control-sm" id="hv-phu-sdt" autocomplete="off" placeholder="Số điện thoại">
                    </div>
                    <div class="form-group mb-0">
                        <label class="small mb-0" for="hv-phu-sale">Sale</label>
                        <input type="text" class="form-control form-control-sm" id="hv-phu-sale" autocomplete="off" placeholder="Tên / mã sale">
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-sm btn-primary" id="hv-phu-luu">Lưu</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDoThongTin" tabindex="-1" role="dialog" aria-labelledby="modalDoThongTinLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title" id="modalDoThongTinLabel">Dò thông tin học viên từ CCCD / GPLX</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-8 mb-3">
                            <div id="dtt-o" class="dtt-luoi"></div>
                        </div>
                        <div class="col-lg-4 mb-3">
                            <div class="border rounded p-2 h-100">
                                <strong>Quét bằng điện thoại</strong>
                                <p class="small text-muted mb-2">
                                    Mở camera điện thoại quét mã này → chụp từng mặt giấy tờ, kết quả tự đổ về đây.
                                </p>
                                <div id="dtt-qr" class="text-center mb-2"></div>
                                <div class="small mb-2 text-break"><a id="dtt-link" href="#" target="_blank"></a></div>
                                <div id="dtt-canh-bao-host" class="alert alert-warning py-1 px-2 small mb-2" hidden>
                                    Máy tính đang mở bằng <code>localhost</code> — điện thoại không vào được địa chỉ này.
                                    Nhập địa chỉ LAN của máy chủ (vd. <code>http://192.168.1.10:8080</code>):
                                </div>
                                <div class="input-group input-group-sm mb-1">
                                    <input type="text" class="form-control" id="dtt-goc" placeholder="Địa chỉ máy chủ cho điện thoại">
                                    <div class="input-group-append"><button type="button" class="btn btn-outline-secondary" id="dtt-goc-luu">Lưu</button></div>
                                </div>
                                <div class="small text-muted" id="dtt-phien-trang-thai"></div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center mb-2">
                        <strong class="mr-auto">Kết quả dò</strong>
                        <button type="button" class="btn btn-sm btn-outline-primary mr-1" id="dtt-tim">Tìm học viên</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary mr-1" id="dtt-sao-chep">Sao chép</button>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="dtt-xoa">Dò lại từ đầu</button>
                    </div>
                    <div class="form-row" id="dtt-kq"></div>

                    <div id="dtt-so-sanh" class="mt-2"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/do-thong-tin.css') }}?v={{ filemtime(public_path('css/do-thong-tin.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/hoc-vien-danh-muc.css') }}?v={{ filemtime(public_path('css/hoc-vien-danh-muc.css')) }}">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
<script src="{{ asset('js/do-thong-tin.js') }}?v={{ filemtime(public_path('js/do-thong-tin.js')) }}"></script>
<script>
(function ($) {
    const DTT = window.DoThongTin;
    const urls = {
        ocr: @json(route('pmgplx.dm.hoc-vien.do-thong-tin.ocr')),
        phien: @json(route('pmgplx.dm.hoc-vien.do-thong-tin.phien.tao')),
        xemPhien: @json(route('pmgplx.dm.hoc-vien.do-thong-tin.phien.xem', ['token' => str_repeat('X', 32)])),
        tim: @json(route('pmgplx.dm.hoc-vien.do-thong-tin.tim-hoc-vien')),
        luu: @json(route('pmgplx.dm.hoc-vien.do-thong-tin.luu')),
    };
    const csrf = @json(csrf_token());
    const KHOA_GOC = 'dtt-goc-may-chu';
    const laLocalhost = /^(localhost|127\.0\.0\.1|\[::1\])$/.test(location.hostname);

    let token = null;
    let duongDan = '';
    let gocCauHinh = '';
    let hoiPhien = null;
    const daNhan = {};

    // Bảng kết quả: 8 ô sửa được.
    $('#dtt-kq').html(Object.keys(DTT.TRUONG).map(function (k) {
        const rong = k === 'noi_thuong_tru' ? 'col-md-6' : (k === 'ho_ten' ? 'col-md-3' : 'col-md-3');
        return '<div class="form-group ' + rong + ' mb-2"><label class="small mb-0">' + DTT.esc(DTT.TRUONG[k]) + '</label>' +
            '<input type="text" class="form-control form-control-sm dtt-truong" data-k="' + k + '"></div>';
    }).join(''));

    function oTruong(k) { return document.querySelector('.dtt-truong[data-k="' + k + '"]'); }

    function layKetQua() {
        const out = {};
        document.querySelectorAll('.dtt-truong').forEach(function (el) { out[el.dataset.k] = el.value.trim(); });
        return out;
    }

    const o = DTT.ganO(document.getElementById('dtt-o'), {
        urlOcr: urls.ocr,
        csrf: csrf,
        onKetQua: function (mat, kq) {
            const cu = oTruong('so_cccd').value;
            Object.keys(kq.ket_qua || {}).forEach(function (k) {
                const el = oTruong(k);
                if (el) el.value = kq.ket_qua[k];
            });
            if (maDkCoDinh) {
                if (dsHocVien.length) veSoSanh(layKetQua()); else timHocVien();
            } else if (oTruong('so_cccd').value && oTruong('so_cccd').value !== cu) {
                timHocVien();
            }
        },
    });

    // Mở từ nút «Dò» của một dòng: so sánh và lưu thẳng vào học viên đó (không tìm theo số căn cước).
    let maDkCoDinh = '';

    /* ---------- Tìm học viên + so sánh ---------- */

    let dsHocVien = [];
    let daLuuGi = false;

    function veSoSanh(kq) {
        const ds = dsHocVien;
        let html = '';
        if (maDkCoDinh && ds[0] && kq.so_cccd && !DTT.khop('so_cccd', kq.so_cccd, ds[0].hien_tai.so_cccd)) {
            html += '<div class="alert alert-danger py-2 small mb-2">Số căn cước dò được <strong>' + DTT.esc(kq.so_cccd) +
                '</strong> khác học viên đang chọn (<strong>' + DTT.esc(ds[0].hien_tai.so_cccd || '—') +
                '</strong>) — kiểm tra lại có đúng giấy tờ của học viên này không.</div>';
        }
        html += '<table class="table table-sm table-bordered dtt-so-sanh mb-0"><thead class="thead-light"><tr><th>Thông tin</th><th>Kết quả dò</th>';
        ds.forEach(function (hv) {
            html += '<th>PMGPLX — ' + DTT.esc(hv.ma_dk) + (hv.ma_kh ? ' <small class="text-muted">(' + DTT.esc(hv.ma_kh) + ')</small>' : '') + '</th>';
        });
        html += '</tr></thead><tbody>';
        const nhanKhop = function (khop) {
            return khop ? '<span class="badge badge-success">Khớp</span>' : '<span class="badge badge-danger">Không khớp</span>';
        };
        Object.keys(DTT.TRUONG).forEach(function (k) {
            html += '<tr><td>' + DTT.esc(DTT.TRUONG[k]) + '</td><td>' + DTT.esc(kq[k]) + '</td>';
            ds.forEach(function (hv) {
                const v = hv.hien_tai[k] || '';
                const daLuu = (hv.da_luu || {})[k];
                const khop = kq[k] ? DTT.khop(k, kq[k], v) : null;
                html += '<td class="' + (khop === null ? '' : (khop ? 'khop' : 'khac')) + '">' +
                    '<div class="d-flex justify-content-between align-items-start">' +
                    '<span>' + (v ? DTT.esc(v) : '<span class="text-muted">—</span>') + '</span>' +
                    (khop === null ? '' : '<span class="ml-2">' + nhanKhop(khop) + '</span>') + '</div>' +
                    (daLuu === undefined ? '' : '<div class="small text-muted">Đã lưu: ' + (daLuu ? 'Khớp' : 'Không khớp') + '</div>') +
                    '</td>';
            });
            html += '</tr>';
        });
        html += '<tr><td colspan="2" class="small text-muted align-middle">' +
            'Chọn kết luận rồi lưu — có khi chỉ cần khớp vài thông tin là chấp nhận được (OK). ' +
            'Lưu kèm Khớp / Không khớp từng trường đã dò.</td>';
        ds.forEach(function (hv, i) {
            const coLech = Object.keys(DTT.TRUONG).some(function (k) { return kq[k] && !DTT.khop(k, kq[k], hv.hien_tai[k] || ''); });
            const chon = hv.ket_luan || (coLech ? 'can_chinh' : 'ok');
            const radio = function (val, nhan, lop) {
                return '<div class="custom-control custom-radio custom-control-inline">' +
                    '<input type="radio" class="custom-control-input" id="dtt-kl-' + i + '-' + val + '" name="dtt-kl-' + i + '" value="' + val + '"' +
                    (chon === val ? ' checked' : '') + '>' +
                    '<label class="custom-control-label ' + lop + '" for="dtt-kl-' + i + '-' + val + '">' + nhan + '</label></div>';
            };
            html += '<td>' +
                '<div class="mb-1">' + radio('ok', 'OK', 'text-success font-weight-bold') + radio('can_chinh', 'Cần chỉnh', 'text-warning font-weight-bold') + '</div>' +
                '<button type="button" class="btn btn-sm btn-success dtt-luu" data-hv="' + i + '">Lưu đối chiếu</button>' +
                (hv.luu_luc ? '<div class="small text-muted mt-1">Đã lưu: ' + (hv.ket_luan === 'ok' ? 'OK' : (hv.ket_luan ? 'Cần chỉnh' : '')) +
                    ' — ' + DTT.esc(hv.luu_luc) + '</div>' : '') +
                '</td>';
        });
        html += '</tr></tbody></table><div class="small text-muted mt-1">So kết quả dò với PMGPLX — lưu bên MANHLINH, không sửa PMGPLX.</div>';
        $('#dtt-so-sanh').html(html);
    }

    function timHocVien() {
        const kq = layKetQua();
        if (!maDkCoDinh && !kq.so_cccd && !(kq.ho_ten && kq.ngay_sinh)) {
            $('#dtt-so-sanh').html('<div class="text-muted small">Cần số căn cước (hoặc tên + ngày sinh) để tìm học viên.</div>');
            return;
        }
        $('#dtt-so-sanh').html('<div class="text-muted small">Đang tìm học viên…</div>');
        const thamSo = maDkCoDinh ? { ma_dk: maDkCoDinh } : { so_cccd: kq.so_cccd, ho_ten: kq.ho_ten, ngay_sinh: kq.ngay_sinh };
        $.getJSON(urls.tim, thamSo).done(function (data) {
            dsHocVien = data.hoc_vien || [];
            if (!dsHocVien.length) {
                $('#dtt-so-sanh').html('<div class="alert alert-info py-2 small mb-0">Chưa có học viên nào trên PMGPLX khớp số căn cước <strong>' + DTT.esc(kq.so_cccd) + '</strong>.</div>');
                return;
            }
            veSoSanh(kq);
        }).fail(function () {
            $('#dtt-so-sanh').html('<div class="text-danger small">Không tìm được học viên.</div>');
        });
    }

    $('#dtt-so-sanh').on('click', '.dtt-luu', function () {
        const i = Number(this.dataset.hv);
        const hv = dsHocVien[i];
        const kq = layKetQua();
        const ketQua = {};
        Object.keys(kq).forEach(function (k) { if (kq[k]) ketQua[k] = kq[k]; });
        if (!Object.keys(ketQua).length) {
            alert('Chưa có kết quả dò để đối chiếu.');
            return;
        }
        const btn = this;
        btn.disabled = true;
        $.ajax({
            url: urls.luu, method: 'POST', headers: { 'X-CSRF-TOKEN': csrf },
            contentType: 'application/json', dataType: 'json',
            data: JSON.stringify({
                ma_dk: hv.ma_dk,
                ket_qua: ketQua,
                ket_luan: (document.querySelector('input[name="dtt-kl-' + i + '"]:checked') || {}).value || '',
            }),
        }).done(function (d) {
            daLuuGi = true;
            $('#dtt-phien-trang-thai').text(d.message);
            timHocVien();
        }).fail(function (xhr) {
            alert((xhr.responseJSON && xhr.responseJSON.message) || 'Không lưu được.');
            btn.disabled = false;
        });
    });

    $('#dtt-tim').on('click', timHocVien);
    $('#dtt-sao-chep').on('click', function () {
        const kq = layKetQua();
        const text = Object.keys(DTT.TRUONG).map(function (k) { return DTT.TRUONG[k] + ': ' + (kq[k] || ''); }).join('\n');
        navigator.clipboard.writeText(text).then(function () { $('#dtt-phien-trang-thai').text('Đã sao chép kết quả.'); });
    });
    function datLaiModal() {
        document.querySelectorAll('.dtt-truong').forEach(function (el) { el.value = ''; });
        $('#dtt-so-sanh').empty();
        dsHocVien = [];
        Object.keys(daNhan).forEach(function (k) { delete daNhan[k]; });
        o.datLai();
        token = null;
    }

    $('#dtt-xoa').on('click', function () {
        datLaiModal();
        moPhien();
        if (maDkCoDinh) timHocVien();
    });

    // Sửa tay ô kết quả → vẽ lại bảng so sánh.
    $('#dtt-kq').on('change', '.dtt-truong', function () {
        if (dsHocVien.length) veSoSanh(layKetQua());
    });

    $(document).on('click', '.btn-do-hv', function () {
        maDkCoDinh = this.dataset.maDk || '';
        $('#modalDoThongTinLabel').text(maDkCoDinh
            ? 'Dò thông tin — ' + (this.dataset.ten || '') + ' (' + maDkCoDinh + ')'
            : 'Dò thông tin học viên từ CCCD / GPLX');
        $('#dtt-tim').prop('hidden', !!maDkCoDinh);
        datLaiModal();
        $('#modalDoThongTin').modal('show');
        if (maDkCoDinh) timHocVien();
    });

    /* ---------- Phiên điện thoại (QR) ---------- */

    function gocMayChu() {
        return (localStorage.getItem(KHOA_GOC) || gocCauHinh || location.origin).replace(/\/+$/, '');
    }

    function veQr() {
        if (!duongDan) return;
        const url = gocMayChu() + duongDan;
        const qr = window.qrcode(0, 'M');
        qr.addData(url);
        qr.make();
        $('#dtt-qr').html(qr.createSvgTag({ cellSize: 4, margin: 2 }));
        $('#dtt-link').attr('href', url).text(url);
        const conLocal = /\/\/(localhost|127\.0\.0\.1)/.test(url);
        $('#dtt-canh-bao-host').prop('hidden', !conLocal);
    }

    function moPhien() {
        $('#dtt-phien-trang-thai').text('Đang tạo phiên…');
        $.ajax({ url: urls.phien, method: 'POST', headers: { 'X-CSRF-TOKEN': csrf }, dataType: 'json' }).done(function (d) {
            token = d.token;
            duongDan = d.duong_dan;
            gocCauHinh = d.goc_cau_hinh || '';
            $('#dtt-goc').val(gocMayChu());
            veQr();
            $('#dtt-phien-trang-thai').text('Chờ điện thoại gửi ảnh… (phiên 30 phút)');
        }).fail(function () {
            $('#dtt-phien-trang-thai').text('Không tạo được phiên quét bằng điện thoại.');
        });
    }

    function hoiKetQua() {
        if (!token) return;
        $.getJSON(urls.xemPhien.replace('X'.repeat(32), token)).done(function (d) {
            Object.keys(d.ket_qua || {}).forEach(function (mat) {
                const kq = d.ket_qua[mat];
                const dau = JSON.stringify(kq.ket_qua) + '|' + (kq.canh_bao || '');
                if (daNhan[mat] === dau) return;
                daNhan[mat] = dau;
                o.hienKetQua(mat, kq);
                $('#dtt-phien-trang-thai').text('Đã nhận ' + DTT.MAT[mat].nhan + ' từ điện thoại.');
            });
        }).fail(function (xhr) {
            if (xhr.status === 410) {
                token = null;
                $('#dtt-phien-trang-thai').text('Phiên đã hết hạn — đóng và mở lại «Dò thông tin».');
            }
        });
    }

    $('#dtt-goc-luu').on('click', function () {
        const v = $('#dtt-goc').val().trim();
        if (v) localStorage.setItem(KHOA_GOC, v); else localStorage.removeItem(KHOA_GOC);
        veQr();
    });

    $('#modalDoThongTin').on('shown.bs.modal', function () {
        if (!token) moPhien();
        hoiPhien = setInterval(hoiKetQua, 2000);
    }).on('hidden.bs.modal', function () {
        clearInterval(hoiPhien);
        if (daLuuGi) location.reload();
    });

    if (laLocalhost && !localStorage.getItem(KHOA_GOC)) {
        $('#dtt-canh-bao-host').prop('hidden', false);
    }
})(jQuery);
</script>
<script>
    $('#filter_ma_kh').select2({
        theme: 'bootstrap4',
        placeholder: 'Tìm khóa học...',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#filter_ma_kh').closest('.form-group'),
        language: {
            noResults: function () { return 'Không tìm thấy khóa học'; }
        }
    });

    @include('PMGPLX.danh-muc.partials.hoc-vien-xuat-excel-scripts')

    $('#filter_sale').select2({
        theme: 'bootstrap4',
        placeholder: 'Chọn Sale...',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#filter_sale').closest('.form-group'),
        language: {
            noResults: function () { return 'Không tìm thấy Sale'; }
        }
    });

    @if (session('success'))
        alert(@json(session('success')));
    @elseif (session('error'))
        alert(@json(session('error')));
    @endif
</script>
<script>
(function ($) {
    const urlLuu = @json(route('pmgplx.dm.hoc-vien.thong-tin-phu'));
    const csrf = @json(csrf_token());
    function toggleDetail($main, open) {
        const maDk = $main.data('ma-dk');
        const $detail = $('tr[data-detail-for="' + maDk + '"]');
        const $btn = $main.find('.hv-detail-toggle');
        const show = open !== undefined ? open : $detail.hasClass('d-none');
        $detail.toggleClass('d-none', !show);
        $main.toggleClass('table-active', show);
        $btn.text(show ? '−' : '+').attr('aria-expanded', show ? 'true' : 'false');
    }

    $(document).on('click', '.hv-detail-toggle', function () {
        toggleDetail($(this).closest('tr.hv-row-main'));
    });

    let allOpen = false;
    $('#hv-toggle-all-details').on('click', function () {
        allOpen = !allOpen;
        $('tr.hv-row-main').each(function () {
            toggleDetail($(this), allOpen);
        });
    });

    function syncPhuCellData($main, sdt, sale) {
        const $cell = $main.find('.hv-open-phu-cell');
        $cell.attr('data-sdt', sdt || '').attr('data-sale', sale || '');
        $cell.find('.hv-open-phu').attr('data-sdt', sdt || '').attr('data-sale', sale || '');
    }

    function moModalThongTinPhu($src) {
        const maDk = $src.data('ma-dk');
        const ten = $src.data('ten') || '';
        $('#hv-phu-ma-dk').val(maDk);
        $('#hv-phu-sdt').val($src.data('sdt') || '');
        $('#hv-phu-sale').val($src.data('sale') || '');
        $('#hv-phu-subtitle').text(ten ? ten + ' · ' + maDk : maDk);
        $('#modalThongTinPhu').modal('show');
        setTimeout(function () { $('#hv-phu-sdt').trigger('focus'); }, 300);
    }

    $(document).on('click', '.hv-open-phu', function (e) {
        e.stopPropagation();
        moModalThongTinPhu($(this));
    });

    $(document).on('click', '.hv-open-phu-cell', function (e) {
        if ($(e.target).closest('.hv-open-phu').length) {
            return;
        }
        moModalThongTinPhu($(this));
    });

    $(document).on('keydown', '.hv-open-phu-cell', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            moModalThongTinPhu($(this));
        }
    });

    $('#hv-phu-luu').on('click', function () {
        const maDk = $('#hv-phu-ma-dk').val();
        const sdt = $('#hv-phu-sdt').val();
        const sale = $('#hv-phu-sale').val();
        const $btn = $(this);
        $btn.prop('disabled', true);
        $.ajax({
            url: urlLuu,
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
            dataType: 'json',
            data: { ma_dk: maDk, so_dien_thoai: sdt, sale: sale },
        }).done(function (data) {
            const $main = $('tr.hv-row-main[data-ma-dk="' + maDk + '"]');
            const sdtShow = (data.so_dien_thoai && String(data.so_dien_thoai).trim()) || '—';
            const saleShow = (data.sale && String(data.sale).trim()) || '—';
            $main.find('.hv-text-sdt').text(sdtShow);
            $main.find('.hv-text-sale').text(saleShow);
            syncPhuCellData($main, data.so_dien_thoai || '', data.sale || '');
            $('#modalThongTinPhu').modal('hide');
        }).fail(function (xhr) {
            alert((xhr.responseJSON && xhr.responseJSON.message) || 'Không lưu được.');
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });
})(jQuery);
</script>
@endpush
