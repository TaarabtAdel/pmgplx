@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Quản lý học viên')

@section('content')
    <div class="card card-panel">
        <div class="card-header">Thông tin tìm kiếm học viên</div>
        <div class="card-body">
            <form method="GET" action="{{ route('pmgplx.dm.hoc-vien.index') }}">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-3">
                        <label>Mã ĐK|Họ tên|CMT|Số hồ sơ</label>
                        <input type="text" class="form-control form-control-sm" name="tu_khoa" value="{{ $filters['tu_khoa'] }}" placeholder="Nhập từ khóa">
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
                    <div class="form-group col-md-2">
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
                    <a href="{{ route('pmgplx.dm.hoc-vien.dong-bo.form', array_filter(['ma_kh_nguon' => $filters['ma_kh']])) }}"
                       class="btn btn-sm btn-info ml-1">
                        Đồng Bộ Qua Bản Cũ
                    </a>
                    <a href="{{ route('pmgplxold.dm.hoc-vien.index') }}" class="btn btn-sm btn-outline-secondary ml-1" target="_blank">
                        Xem bản cũ
                    </a>
                </div>

                <a href="{{ route('pmgplx.dm.hoc-vien.export', array_filter([
                    'tu_khoa' => $filters['tu_khoa'],
                    'ma_kh' => $filters['ma_kh'],
                    'hang_gplx' => $filters['hang_gplx'],
                    'trang_thai' => $filters['trang_thai'],
                ])) }}"
                   class="btn btn-sm btn-success mb-2 mr-2"
                   title="Xuất Excel theo bộ lọc hiện tại — đủ cột NguoiLX và NguoiLX_HoSo">
                    ⬇ Xuất Excel ({{ number_format($items->total()) }})
                </a>

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

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped table-hover table-data mb-0">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Mã ĐK</th>
                            <th>Họ và tên</th>
                            <th>Ngày sinh</th>
                            <th>Giới tính</th>
                            <th>Số CMT</th>
                            <th>Ngày cấp</th>
                            <th>Nơi TT</th>
                            <th>Mã khóa học</th>
                            <th class="text-center">Đã dò</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $index => $row)
                            @php
                                $hoTenHv = $row->HoVaTen ?: trim(($row->HoDemNLX ?? '').' '.($row->TenNLX ?? ''));
                            @endphp
                            <tr>
                                <td>{{ $items->firstItem() + $index }}</td>
                                <td>
                                    @include('PMGPLX.danh-muc._trang-thai-icon', ['active' => (string) $row->TrangThai === '1'])
                                    {{ $row->MaDK }}
                                </td>
                                <td>{{ $hoTenHv }}</td>
                                <td>{{ \App\Support\PMGPLX\NgayVn::format($row->NgaySinh, 'yyyymmdd') }}</td>
                                <td>
                                    @if ($row->GioiTinh === '1' || strtoupper((string) $row->GioiTinh) === 'M')
                                        Nam
                                    @elseif ($row->GioiTinh === '0' || strtoupper((string) $row->GioiTinh) === 'F')
                                        Nữ
                                    @else
                                        {{ $row->GioiTinh }}
                                    @endif
                                </td>
                                <td>{{ $row->SoCMT }}</td>
                                <td>{{ \App\Support\OCR\DoThongTinGiayTo::ngayDatetime($row->NgayCapCMT) }}</td>
                                <td>{{ $noiThuongTru[(string) $row->MaDK] ?? '' }}</td>
                                <td>{{ $row->MaKhoaHoc }}</td>
                                <td class="text-center text-nowrap">
                                    @php $doRoi = $daDo[(string) $row->MaDK] ?? null; @endphp
                                    @if ($doRoi)
                                        @php
                                            $nhanDo = collect($doRoi->giaTri())
                                                ->map(fn ($v, $k) => (\App\Support\OCR\DoThongTinGiayTo::TRUONG[$k][0] ?? $k).': '.$v)
                                                ->implode("\n");
                                        @endphp
                                        <span class="text-success font-weight-bold dtt-da-do mr-1" style="font-size: 1.1rem;"
                                              title="Đã dò {{ $doRoi->NgayCapNhat?->copy()->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}&#10;{{ $nhanDo }}">✓</span>
                                    @endif
                                    <button type="button" class="btn btn-sm btn-outline-primary py-0 btn-do-hv"
                                            data-ma-dk="{{ $row->MaDK }}" data-ten="{{ $hoTenHv }}" title="Dò CCCD / GPLX cho học viên này">Dò</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4">Không có dữ liệu</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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

    function chuan(s) { return String(s || '').toLowerCase().normalize('NFC').replace(/\s+/g, ' ').replace(/[.,]/g, '').trim(); }

    let dsHocVien = [];
    let daLuuGi = false;

    function veSoSanh(kq) {
        const ds = dsHocVien;
        let html = '';
        if (maDkCoDinh && ds[0] && kq.so_cccd && chuan(kq.so_cccd) !== chuan(ds[0].hien_tai.so_cccd)) {
            html += '<div class="alert alert-danger py-2 small mb-2">Số căn cước dò được <strong>' + DTT.esc(kq.so_cccd) +
                '</strong> khác học viên đang chọn (<strong>' + DTT.esc(ds[0].hien_tai.so_cccd || '—') +
                '</strong>) — kiểm tra lại có đúng giấy tờ của học viên này không.</div>';
        }
        html += '<table class="table table-sm table-bordered dtt-so-sanh mb-0"><thead class="thead-light"><tr><th>Thông tin</th><th>Kết quả dò</th>';
        ds.forEach(function (hv) {
            html += '<th>PMGPLX — ' + DTT.esc(hv.ma_dk) + (hv.ma_kh ? ' <small class="text-muted">(' + DTT.esc(hv.ma_kh) + ')</small>' : '') + '</th>';
        });
        html += '</tr></thead><tbody>';
        Object.keys(DTT.TRUONG).forEach(function (k) {
            html += '<tr><td>' + DTT.esc(DTT.TRUONG[k]) + '</td><td>' + DTT.esc(kq[k]) + '</td>';
            ds.forEach(function (hv, i) {
                const v = hv.hien_tai[k] || '';
                const luu = (hv.da_luu || {})[k] || '';
                const khop = chuan(v) === chuan(kq[k]);
                const daLuuDung = luu && chuan(luu) === chuan(kq[k]);
                const lop = !kq[k] ? '' : (khop ? 'khop' : 'khac');
                html += '<td class="' + lop + '"><div class="d-flex align-items-start">';
                if (kq[k]) {
                    html += '<input type="checkbox" class="mr-2 mt-1 dtt-chon" data-hv="' + i + '" data-k="' + k + '"' +
                        (!khop && !daLuuDung ? ' checked' : '') + ' title="Lưu kết quả dò của trường này">';
                }
                html += '<div>' + (v ? DTT.esc(v) : '<span class="text-muted">—</span>');
                if (luu) {
                    html += '<div class="small ' + (daLuuDung ? 'text-success' : 'text-muted') + '">MANHLINH: ' + DTT.esc(luu) + '</div>';
                }
                html += '</div></div></td>';
            });
            html += '</tr>';
        });
        html += '<tr><td colspan="2" class="small text-muted align-middle">Tích các trường cần lưu (mặc định tích sẵn trường đang lệch) rồi bấm lưu.</td>';
        ds.forEach(function (hv, i) {
            html += '<td><button type="button" class="btn btn-sm btn-success dtt-luu" data-hv="' + i + '">Lưu vào MANHLINH</button>' +
                (hv.luu_luc ? '<div class="small text-muted mt-1">Đã lưu: ' + DTT.esc(hv.luu_luc) + '</div>' : '') + '</td>';
        });
        html += '</tr></tbody></table><div class="small text-muted mt-1">Xanh: khớp PMGPLX · Vàng: khác với kết quả dò. Lưu bên MANHLINH, không sửa PMGPLX.</div>';
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
        const truong = {};
        document.querySelectorAll('.dtt-chon[data-hv="' + i + '"]:checked').forEach(function (el) {
            truong[el.dataset.k] = kq[el.dataset.k] || '';
        });
        if (!Object.keys(truong).length) {
            alert('Chưa tích trường nào để lưu.');
            return;
        }
        const btn = this;
        btn.disabled = true;
        $.ajax({
            url: urls.luu, method: 'POST', headers: { 'X-CSRF-TOKEN': csrf },
            contentType: 'application/json', dataType: 'json',
            data: JSON.stringify({ ma_dk: hv.ma_dk, truong: truong }),
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

    // Sửa tay ô kết quả → vẽ lại bảng so sánh (ô tích mặc định theo giá trị mới).
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

    @if (session('success'))
        alert(@json(session('success')));
    @elseif (session('error'))
        alert(@json(session('error')));
    @endif
</script>
@endpush
