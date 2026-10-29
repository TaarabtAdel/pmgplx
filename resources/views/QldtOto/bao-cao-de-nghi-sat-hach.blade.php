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

    <div class="card card-panel">
        <div class="card-header">{{ $item['title'] }}</div>
        <div class="card-body">
            <p class="text-muted small mb-3">
                Một màn nhập chung, hai nút xuất: <strong>Báo cáo đề nghị tổ chức sát hạch</strong>
                (mẫu <code>bao-cao-de-nghi-to-chuc-sat-hach.docx</code>) và
                <strong>Danh sách thí sinh dự sát hạch — Báo cáo 2</strong>.
                Học viên mặc định được chọn là học viên có kết luận CSĐT <em>đạt</em> trên PMGPLX.
            </p>

            <form method="GET" action="{{ route('qldtoto.bao-cao-de-nghi-sat-hach.index') }}" class="form-row align-items-end mb-3">
                <div class="form-group col-md-6 mb-0">
                    <label for="ma_kh_loc">Khoá (PMGPLX)</label>
                    <select class="form-control form-control-sm" id="ma_kh_loc" name="ma_kh" data-placeholder="— Chọn khoá —">
                        <option value=""></option>
                        @foreach ($khoaOptions as $kh)
                            <option value="{{ $kh->MaKH }}" @selected($maKh === $kh->MaKH)>{{ $kh->TenKH }} ({{ $kh->MaKH }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2 mb-0">
                    <button type="submit" class="btn btn-sm btn-primary btn-block">Chọn khoá</button>
                </div>
            </form>

            @if ($form === null)
                <p class="text-muted mb-0">Chọn khoá để tải học viên và thông tin báo cáo.</p>
            @else
                @if (session('error'))
                    <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger py-2 small">
                        <ul class="mb-0 pl-3">
                            @foreach ($errors->all() as $msg)
                                <li>{{ $msg }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @php
                    $v = fn (string $key) => old($key, $form[$key] ?? '');
                    $tongDat = count(array_filter($hocVien, fn ($h) => $h['dat']));
                    $chonCu = old('chon');
                @endphp

                <form method="POST" action="{{ route('qldtoto.bao-cao-de-nghi-sat-hach.export') }}" id="form-bc-sh">
                    @csrf
                    <input type="hidden" name="ma_kh" value="{{ $form['ma_kh'] }}">

                    <h6 class="font-weight-bold mb-2">Thông tin chung</h6>
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="so_bc">Số báo cáo</label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" id="so_bc" name="so_bc" inputmode="numeric"
                                       value="{{ $v('so_bc') }}" placeholder="vd. 707" maxlength="10">
                                <div class="input-group-append"><span class="input-group-text" id="so_bc_hau_to">/TTML-{{ substr((string) $v('ngay_bao_cao'), 0, 4) }}</span></div>
                            </div>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="ngay_bao_cao">Ngày báo cáo</label>
                            <input type="date" class="form-control form-control-sm" id="ngay_bao_cao" name="ngay_bao_cao" value="{{ $v('ngay_bao_cao') }}" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="kinh_gui">Kính gửi</label>
                            <input type="text" class="form-control form-control-sm" id="kinh_gui" name="kinh_gui" value="{{ $v('kinh_gui') }}" maxlength="200">
                        </div>
                    </div>

                    <h6 class="font-weight-bold mb-2 mt-2">Báo cáo đề nghị — bảng khoá</h6>
                    <div class="form-row">
                        <div class="form-group col-md-2">
                            <label for="khoa_lop">Khóa - Lớp</label>
                            <input type="text" class="form-control form-control-sm" id="khoa_lop" name="khoa_lop" value="{{ $v('khoa_lop') }}" maxlength="50">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="hang">Hạng GPLX</label>
                            <input type="text" class="form-control form-control-sm" id="hang" name="hang" value="{{ $v('hang') }}" maxlength="40">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="so_vao_hoc">Số HV vào học</label>
                            <input type="number" class="form-control form-control-sm" id="so_vao_hoc" name="so_vao_hoc" value="{{ $v('so_vao_hoc') }}" min="0">
                        </div>
                        <div class="form-group col-md-2">
                            <label>Số HV dự sát hạch</label>
                            <div class="form-control form-control-sm bg-light" id="so_du_sh">{{ $tongDat }}</div>
                            <small class="text-muted">= số học viên đang chọn</small>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="ngay_sh">Ngày SH dự kiến</label>
                            <input type="date" class="form-control form-control-sm" id="ngay_sh" name="ngay_sh" value="{{ $v('ngay_sh') }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="ghi_chu">Ghi chú</label>
                            <input type="text" class="form-control form-control-sm" id="ghi_chu" name="ghi_chu" value="{{ $v('ghi_chu') }}" maxlength="100">
                        </div>
                    </div>

                    <h6 class="font-weight-bold mb-2 mt-2">Danh sách thí sinh — Báo cáo 2</h6>
                    <div class="form-row">
                        <div class="form-group col-md-2">
                            <label for="hang_bc2">Cấp GPLX hạng</label>
                            <input type="text" class="form-control form-control-sm" id="hang_bc2" name="hang_bc2" value="{{ $v('hang_bc2') }}" maxlength="20">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="khoa_bc2">Khóa</label>
                            <input type="text" class="form-control form-control-sm" id="khoa_bc2" name="khoa_bc2" value="{{ $v('khoa_bc2') }}" maxlength="50">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="thoi_gian_dt">Thời gian đào tạo (tháng)</label>
                            <input type="text" class="form-control form-control-sm" id="thoi_gian_dt" name="thoi_gian_dt" value="{{ $v('thoi_gian_dt') }}" maxlength="20">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="ngay_kg">Ngày khai giảng</label>
                            <input type="date" class="form-control form-control-sm" id="ngay_kg" name="ngay_kg" value="{{ $v('ngay_kg') }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="ngay_bg">Ngày mãn khóa</label>
                            <input type="date" class="form-control form-control-sm" id="ngay_bg" name="ngay_bg" value="{{ $v('ngay_bg') }}">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="ccn_bat_dau">Số chứng chỉ nghề từ</label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" id="ccn_tien_to" name="ccn_tien_to" value="{{ $v('ccn_tien_to') }}" maxlength="10" style="max-width: 3.5rem;">
                                <input type="number" class="form-control" id="ccn_bat_dau" name="ccn_bat_dau" value="{{ $v('ccn_bat_dau') }}" min="0" placeholder="vd. 1447">
                            </div>
                            <small class="text-muted">Tăng dần theo thứ tự cả khoá</small>
                        </div>
                    </div>

                    <div class="d-flex align-items-center mb-2 mt-2">
                        <h6 class="font-weight-bold mb-0">
                            Học viên ({{ count($hocVien) }} trong khoá, đang chọn <span id="dem_chon">{{ $tongDat }}</span>)
                        </h6>
                        <div class="ml-auto">
                            <button type="submit" name="loai" value="bao_cao" class="btn btn-sm btn-navy">Xuất Báo cáo đề nghị</button>
                            <button type="submit" name="loai" value="bc2" class="btn btn-sm btn-primary">Xuất Danh sách BC2</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover mb-3" style="table-layout: fixed; min-width: 1100px;">
                            <colgroup>
                                <col style="width: 2.5rem;"><col style="width: 3rem;"><col style="width: 15rem;"><col style="width: 7rem;">
                                <col style="width: 8rem;"><col><col style="width: 4rem;"><col style="width: 5rem;"><col style="width: 6rem;"><col style="width: 8rem;">
                            </colgroup>
                            <thead class="thead-light">
                                <tr>
                                    <th class="text-center"><input type="checkbox" id="chon_tat_ca" title="Chọn tất cả"></th>
                                    <th>STT</th>
                                    <th>Họ và tên</th>
                                    <th>Ngày sinh</th>
                                    <th>Số CMND</th>
                                    <th>Nơi đăng ký hộ khẩu</th>
                                    <th class="text-center">CNSK</th>
                                    <th class="text-center">Đã có GPLX</th>
                                    <th>Số CCN</th>
                                    <th>Ghi chú</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($hocVien as $hv)
                                    @php
                                        $checked = is_array($chonCu) ? in_array($hv['ma_dk'], $chonCu, true) : $hv['dat'];
                                    @endphp
                                    <tr class="{{ $hv['dat'] ? '' : 'table-warning' }}" data-thu-tu="{{ $hv['thu_tu'] }}" data-ccn-db="{{ $hv['so_ccn'] }}">
                                        <td class="text-center"><input type="checkbox" class="chk-hv" name="chon[]" value="{{ $hv['ma_dk'] }}" @checked($checked)></td>
                                        <td class="stt text-muted"></td>
                                        <td>
                                            {{ $hv['ho_ten'] }}
                                            @unless ($hv['dat'])
                                                <span class="badge badge-warning">CSĐT chưa đạt</span>
                                            @endunless
                                        </td>
                                        <td>{{ $hv['ngay_sinh'] }}</td>
                                        <td>{{ $hv['so_cmnd'] }}</td>
                                        <td class="text-truncate" title="{{ $hv['noi_dk_ho_khau'] }}">{{ $hv['noi_dk_ho_khau'] }}</td>
                                        <td class="text-center">{{ $hv['giay_cnsk'] ? 'x' : '' }}</td>
                                        <td class="text-center">{{ $hv['da_co_gplx'] }}</td>
                                        <td class="ccn"></td>
                                        <td>{{ $hv['ghi_chu'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="text-right">
                        <button type="submit" name="loai" value="bao_cao" class="btn btn-sm btn-navy">Xuất Báo cáo đề nghị</button>
                        <button type="submit" name="loai" value="bc2" class="btn btn-sm btn-primary">Xuất Danh sách BC2</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function ($) {
    $(function () {
        const $khoa = $('#ma_kh_loc');
        $khoa.select2({
            theme: 'bootstrap4',
            width: '100%',
            allowClear: true,
            placeholder: '— Chọn khoá —',
            dropdownParent: $khoa.closest('.form-group'),
            language: { noResults: function () { return 'Không tìm thấy khoá học'; } },
        });
        $khoa.on('select2:select', function () { this.form.submit(); });
    });

    const form = document.getElementById('form-bc-sh');
    if (!form) return;

    const rows = Array.from(form.querySelectorAll('tbody tr'));
    const tienTo = document.getElementById('ccn_tien_to');
    const batDau = document.getElementById('ccn_bat_dau');

    function capNhat() {
        const start = parseInt(batDau.value, 10);
        let stt = 0;
        rows.forEach(function (tr) {
            const chk = tr.querySelector('.chk-hv');
            const ccnDb = tr.dataset.ccnDb;
            tr.querySelector('.ccn').textContent = ccnDb
                ? ccnDb
                : (Number.isNaN(start) ? '' : tienTo.value + (start + Number(tr.dataset.thuTu) - 1));
            tr.querySelector('.stt').textContent = chk.checked ? String(++stt) : '';
            tr.classList.toggle('text-muted', !chk.checked);
        });
        document.getElementById('dem_chon').textContent = stt;
        document.getElementById('so_du_sh').textContent = stt;
        const all = document.getElementById('chon_tat_ca');
        all.checked = stt === rows.length;
        all.indeterminate = stt > 0 && stt < rows.length;
    }

    form.addEventListener('change', function (e) {
        if (e.target.id === 'chon_tat_ca') {
            rows.forEach(function (tr) { tr.querySelector('.chk-hv').checked = e.target.checked; });
        }
        if (e.target.id === 'ngay_bao_cao' && e.target.value) {
            document.getElementById('so_bc_hau_to').textContent = '/TTML-' + e.target.value.slice(0, 4);
        }
        capNhat();
    });
    [tienTo, batDau].forEach(function (el) { el.addEventListener('input', capNhat); });
    document.getElementById('so_bc').addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '');
    });

    capNhat();
})(jQuery);
</script>
@endpush
