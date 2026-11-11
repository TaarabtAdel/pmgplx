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
                Mẫu <code>quyet-dinh-to-chuc-kiem-tra-ket-thuc-mon.docx</code> và
                <code>bien-ban-phan-cong-to-cham-kiem-tra.docx</code>. Một form dùng chung số QĐ, khoá, thời gian kiểm tra và phân công tổ chấm.
            </p>

            <form method="GET" action="{{ route('qldtoto.quyet-dinh-to-chuc-ktm-bb.index') }}" class="form-row align-items-end mb-3">
                <div class="form-group col-md-6 mb-0">
                    <label for="ma_kh_loc">Khoá (PMGPLX)</label>
                    <select class="form-control form-control-sm" id="ma_kh_loc" name="ma_kh">
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
                <p class="text-muted mb-0">Chọn khoá để lập quyết định và biên bản.</p>
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

                @php $v = fn (string $key, $def = '') => old($key, $form[$key] ?? $def); @endphp

                <form method="POST" id="form-qd-bb">
                    @csrf
                    <input type="hidden" name="ma_kh" value="{{ $form['ma_kh'] }}">

                    <h6 class="font-weight-bold">Quyết định tổ chức kiểm tra</h6>
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="so_qd">Số quyết định</label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" id="so_qd" name="so_qd" inputmode="numeric"
                                       value="{{ $v('so_qd') }}" placeholder="vd. 506" maxlength="10" required>
                                <div class="input-group-append"><span class="input-group-text">/QĐ-TTML</span></div>
                            </div>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="ngay_quyet_dinh">Ngày quyết định</label>
                            <input type="date" class="form-control form-control-sm" id="ngay_quyet_dinh" name="ngay_quyet_dinh" value="{{ $v('ngay_quyet_dinh') }}" required>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="so_hv">Số học viên</label>
                            <input type="number" class="form-control form-control-sm" id="so_hv" name="so_hv" min="1" value="{{ $v('so_hv') }}" required>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="hang">Hạng</label>
                            <input type="text" class="form-control form-control-sm" id="hang" name="hang" value="{{ $v('hang') }}" maxlength="40" required>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="khoa_so">Khóa (số)</label>
                            <input type="text" class="form-control form-control-sm" id="khoa_so" name="khoa_so" value="{{ $v('khoa_so') }}" maxlength="20" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="ghi_thi_lai">Ghi chú học viên thi lại (Điều 1, sau khóa — để trống nếu không dùng)</label>
                        <input type="text" class="form-control form-control-sm" id="ghi_thi_lai" name="ghi_thi_lai" value="{{ $v('ghi_thi_lai') }}" maxlength="300">
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-2">
                            <label for="gio_1">Giờ kiểm tra lần 1</label>
                            <input type="text" class="form-control form-control-sm" id="gio_1" name="gio_1" value="{{ $v('gio_1') }}" maxlength="20" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="ngay_1">Ngày kiểm tra lần 1</label>
                            <input type="date" class="form-control form-control-sm" id="ngay_1" name="ngay_1" value="{{ $v('ngay_1') }}" required>
                        </div>
                        <div class="form-group col-md-3 d-flex align-items-end">
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input" id="co_lan_2" name="co_lan_2" value="1" @checked(old('co_lan_2', $form['co_lan_2'] ?? true))>
                                <label class="custom-control-label" for="co_lan_2">Có kiểm tra lần 2</label>
                            </div>
                        </div>
                        <div class="form-group col-md-2 l2-field">
                            <label for="gio_2">Giờ lần 2</label>
                            <input type="text" class="form-control form-control-sm" id="gio_2" name="gio_2" value="{{ $v('gio_2') }}" maxlength="20">
                        </div>
                        <div class="form-group col-md-2 l2-field">
                            <label for="ngay_2">Ngày lần 2</label>
                            <input type="date" class="form-control form-control-sm" id="ngay_2" name="ngay_2" value="{{ $v('ngay_2') }}">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label for="dia_diem">Địa điểm kiểm tra</label>
                            <input type="text" class="form-control form-control-sm" id="dia_diem" name="dia_diem" value="{{ $v('dia_diem') }}" maxlength="400" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="giam_doc">Giám đốc ký</label>
                            <input type="text" class="form-control form-control-sm" id="giam_doc" name="giam_doc" value="{{ $v('giam_doc') }}" maxlength="100" required>
                        </div>
                    </div>

                    <hr class="my-3">
                    <h6 class="font-weight-bold">Biên bản phân công tổ chấm kiểm tra</h6>
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="ngay_lap">Ngày lập biên bản</label>
                            <input type="date" class="form-control form-control-sm" id="ngay_lap" name="ngay_lap" value="{{ $v('ngay_lap') }}" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="ngay_kiem_tra">Ngày kiểm tra (trên biên bản)</label>
                            <input type="date" class="form-control form-control-sm" id="ngay_kiem_tra" name="ngay_kiem_tra" value="{{ $v('ngay_kiem_tra') }}" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="pho_truong_phong">P. Trưởng phòng Đào tạo (ký)</label>
                            <input type="text" class="form-control form-control-sm" id="pho_truong_phong" name="pho_truong_phong" value="{{ $v('pho_truong_phong') }}" maxlength="100" required>
                        </div>
                    </div>

                    <div class="d-flex align-items-center mb-2">
                        <h6 class="font-weight-bold mb-0 mr-auto">Phân công nhiệm vụ</h6>
                        <button type="button" class="btn btn-sm btn-outline-secondary mr-2 d-none" id="btn-lan-truoc"
                                title="Lấy lại phân công đã lưu trên máy này">Dùng phân công lần trước</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-them-nv">+ Thêm nhiệm vụ</button>
                    </div>
                    <div id="ds-nhiem-vu"></div>

                    <datalist id="dl-giao-vien">
                        @foreach ($giaoVien as $gv)
                            <option value="{{ $gv['ten'] }}"></option>
                        @endforeach
                    </datalist>

                    <div class="mt-3 d-flex flex-wrap">
                        <button type="submit" class="btn btn-sm btn-outline-primary mr-2 mb-2"
                                formaction="{{ route('qldtoto.quyet-dinh-to-chuc-ktm-bb.export-quyet-dinh') }}">Xuất Quyết định</button>
                        <button type="submit" class="btn btn-sm btn-outline-primary mr-2 mb-2"
                                formaction="{{ route('qldtoto.quyet-dinh-to-chuc-ktm-bb.export-bien-ban') }}">Xuất Biên bản</button>
                        <button type="submit" class="btn btn-sm btn-navy mb-2"
                                formaction="{{ route('qldtoto.quyet-dinh-to-chuc-ktm-bb.export-ca-hai') }}">Xuất cả hai (ZIP)</button>
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
            theme: 'bootstrap4', width: '100%', allowClear: true, placeholder: '— Chọn khoá —',
            dropdownParent: $khoa.closest('.form-group'),
            language: { noResults: function () { return 'Không tìm thấy khoá học'; } },
        });
        $khoa.on('select2:select', function () { this.form.submit(); });

        const form = document.getElementById('form-qd-bb');
        if (!form) return;

        const toggleL2 = function () {
            const on = document.getElementById('co_lan_2').checked;
            document.querySelectorAll('.l2-field input').forEach(function (el) {
                el.disabled = !on;
                el.required = on;
            });
        };
        document.getElementById('co_lan_2').addEventListener('change', toggleL2);
        toggleL2();

        document.getElementById('ngay_1').addEventListener('change', function () {
            const kt = document.getElementById('ngay_kiem_tra');
            if (kt && !kt.value) kt.value = this.value;
        });

        const KHOA_LUU = 'qldtoto.quyet-dinh-to-chuc-ktm-bb';
        const XUNG = @json($xungOptions);
        const xungTheoTen = {};
        @json($giaoVien).forEach(function (gv) { xungTheoTen[gv.ten.toLowerCase()] = gv.xung; });

        let nv = @json(array_values(old('nv', $form['nv'] ?? [])));
        nv = nv.map(function (n) {
            const nguoi = Object.values(n.nguoi || {}).map(function (g) { return { xung: g.xung || 'Ông', ten: g.ten || '' }; });
            return { ten: n.ten || '', nguoi: nguoi.length ? nguoi : [{ xung: 'Ông', ten: '' }] };
        });

        const ds = document.getElementById('ds-nhiem-vu');
        const esc = function (s) { return $('<div>').text(s).html(); };

        function render() {
            ds.innerHTML = nv.map(function (n, i) {
                const nguoi = n.nguoi.map(function (g, j) {
                    const xung = XUNG.map(function (x) {
                        return '<option' + (x === g.xung ? ' selected' : '') + '>' + x + '</option>';
                    }).join('');
                    return '<div class="input-group input-group-sm mb-1" data-j="' + j + '">'
                        + '<div class="input-group-prepend"><select class="custom-select custom-select-sm js-xung" name="nv[' + i + '][nguoi][' + j + '][xung]" style="max-width:5rem">' + xung + '</select></div>'
                        + '<input type="text" class="form-control js-ten-nguoi" list="dl-giao-vien" maxlength="100" placeholder="Họ tên"'
                        + ' name="nv[' + i + '][nguoi][' + j + '][ten]" value="' + esc(g.ten) + '">'
                        + '<div class="input-group-append"><button type="button" class="btn btn-outline-danger js-bo-nguoi" title="Bỏ người này">&times;</button></div>'
                        + '</div>';
                }).join('');
                return '<div class="card mb-2" data-i="' + i + '"><div class="card-body p-2">'
                    + '<div class="d-flex align-items-start mb-2">'
                    + '<span class="font-weight-bold mr-2 pt-1">' + (i + 1) + '.</span>'
                    + '<textarea class="form-control form-control-sm js-ten-nv" rows="1" maxlength="500" required name="nv[' + i + '][ten]">' + esc(n.ten) + '</textarea>'
                    + '<div class="btn-group btn-group-sm ml-2">'
                    + '<button type="button" class="btn btn-outline-secondary js-len"' + (i === 0 ? ' disabled' : '') + '>&uarr;</button>'
                    + '<button type="button" class="btn btn-outline-secondary js-xuong"' + (i === nv.length - 1 ? ' disabled' : '') + '>&darr;</button>'
                    + '<button type="button" class="btn btn-outline-danger js-bo-nv">Bỏ</button>'
                    + '</div></div>'
                    + '<div class="pl-4">' + nguoi
                    + '<button type="button" class="btn btn-link btn-sm p-0 js-them-nguoi">+ Thêm người</button></div>'
                    + '</div></div>';
            }).join('');
        }

        const viTri = function (el) {
            const card = el.closest('[data-i]');
            const dong = el.closest('[data-j]');
            return { i: +card.dataset.i, j: dong ? +dong.dataset.j : -1 };
        };

        ds.addEventListener('input', function (e) {
            const p = viTri(e.target);
            if (e.target.classList.contains('js-ten-nv')) nv[p.i].ten = e.target.value;
            if (e.target.classList.contains('js-ten-nguoi')) {
                nv[p.i].nguoi[p.j].ten = e.target.value;
                const xung = xungTheoTen[e.target.value.trim().toLowerCase()];
                if (xung) {
                    nv[p.i].nguoi[p.j].xung = xung;
                    e.target.closest('[data-j]').querySelector('.js-xung').value = xung;
                }
            }
        });
        ds.addEventListener('change', function (e) {
            if (!e.target.classList.contains('js-xung')) return;
            const p = viTri(e.target);
            nv[p.i].nguoi[p.j].xung = e.target.value;
        });
        ds.addEventListener('click', function (e) {
            const btn = e.target.closest('button');
            if (!btn) return;
            const p = viTri(btn);
            if (btn.classList.contains('js-them-nguoi')) {
                const cuoi = nv[p.i].nguoi[nv[p.i].nguoi.length - 1];
                nv[p.i].nguoi.push({ xung: cuoi ? cuoi.xung : 'Ông', ten: '' });
                render();
            } else if (btn.classList.contains('js-bo-nguoi')) {
                nv[p.i].nguoi.splice(p.j, 1);
                if (!nv[p.i].nguoi.length) nv[p.i].nguoi.push({ xung: 'Ông', ten: '' });
                render();
            } else if (btn.classList.contains('js-bo-nv')) {
                if (nv.length === 1) return;
                nv.splice(p.i, 1);
                render();
            } else if (btn.classList.contains('js-len') || btn.classList.contains('js-xuong')) {
                const k = p.i + (btn.classList.contains('js-len') ? -1 : 1);
                [nv[p.i], nv[k]] = [nv[k], nv[p.i]];
                render();
            }
        });
        document.getElementById('btn-them-nv').addEventListener('click', function () {
            nv.push({ ten: '', nguoi: [{ xung: 'Ông', ten: '' }] });
            render();
        });

        const daLuu = localStorage.getItem(KHOA_LUU);
        const btnLanTruoc = document.getElementById('btn-lan-truoc');
        if (daLuu) {
            btnLanTruoc.classList.remove('d-none');
            btnLanTruoc.addEventListener('click', function () {
                try {
                    const luu = JSON.parse(daLuu);
                    if (Array.isArray(luu.nv) && luu.nv.length) nv = luu.nv;
                    if (luu.pho_truong_phong) document.getElementById('pho_truong_phong').value = luu.pho_truong_phong;
                    render();
                } catch (err) { /* bỏ qua */ }
            });
        }

        document.getElementById('so_qd').addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '');
        });
        form.addEventListener('submit', function () {
            localStorage.setItem(KHOA_LUU, JSON.stringify({
                nv: nv,
                pho_truong_phong: document.getElementById('pho_truong_phong').value,
            }));
        });

        render();
    });
})(jQuery);
</script>
@endpush
