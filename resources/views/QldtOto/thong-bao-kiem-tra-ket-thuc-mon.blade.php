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
                Mẫu <code>thong-bao-kiem-tra-ket-thuc-mon.docx</code>. Chọn khoá, nhập thời gian kiểm tra (lần 2 nếu có),
                học viên bị trừ và học viên khoá trước thi lại rồi bấm <strong>Xuất Word</strong>.
                Không có học viên thi lại thì dòng «Kính gửi … thi lại» và bảng thi lại tự bỏ.
            </p>

            <form method="GET" action="{{ route('qldtoto.thong-bao-kiem-tra-ket-thuc-mon.index') }}" class="form-row align-items-end mb-3">
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
                <p class="text-muted mb-0">Chọn khoá để lập thông báo.</p>
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
                    $truDaChon = old('tru', $form['tru']);
                @endphp

                <form method="POST" action="{{ route('qldtoto.thong-bao-kiem-tra-ket-thuc-mon.export') }}" id="form-thong-bao">
                    @csrf
                    <input type="hidden" name="ma_kh" value="{{ $form['ma_kh'] }}">

                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="so_tb">Số thông báo</label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" id="so_tb" name="so_tb" inputmode="numeric"
                                       value="{{ $v('so_tb') }}" placeholder="vd. 488" maxlength="10" required>
                                <div class="input-group-append"><span class="input-group-text">/QĐ-TTML</span></div>
                            </div>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="ngay_thong_bao">Ngày thông báo</label>
                            <input type="date" class="form-control form-control-sm" id="ngay_thong_bao" name="ngay_thong_bao" value="{{ $v('ngay_thong_bao') }}" required>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="hang">Hạng</label>
                            <input type="text" class="form-control form-control-sm" id="hang" name="hang" value="{{ $v('hang') }}" maxlength="40" required>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="khoa_so">Khóa (số)</label>
                            <input type="text" class="form-control form-control-sm" id="khoa_so" name="khoa_so" value="{{ $v('khoa_so') }}" maxlength="20" required>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="ten_lop">Tên lớp (mục 2)</label>
                            <input type="text" class="form-control form-control-sm" id="ten_lop" name="ten_lop" value="{{ $v('ten_lop') }}" maxlength="40" required>
                        </div>
                    </div>

                    <h6 class="font-weight-bold mb-2">1. Thời gian kiểm tra</h6>
                    @foreach ([1, 2] as $lan)
                        <div class="form-row align-items-end {{ $lan === 2 ? 'lan-2' : '' }}">
                            <div class="form-group col-md-2">
                                @if ($lan === 1)
                                    <label class="d-block">Lần 1</label>
                                    <span class="form-control-plaintext form-control-sm py-0">Cả khoá</span>
                                @else
                                    <div class="custom-control custom-checkbox mb-1">
                                        <input type="hidden" name="co_lan_2" value="0">
                                        <input type="checkbox" class="custom-control-input" id="co_lan_2" name="co_lan_2" value="1" @checked((bool) $v('co_lan_2'))>
                                        <label class="custom-control-label" for="co_lan_2">Có lần 2</label>
                                    </div>
                                    <span class="form-control-plaintext form-control-sm py-0 small text-muted">Vắng, không đạt lần 1</span>
                                @endif
                            </div>
                            <div class="form-group col-md-2">
                                <label for="buoi_{{ $lan }}">Buổi</label>
                                <select class="form-control form-control-sm js-tg" id="buoi_{{ $lan }}" name="buoi_{{ $lan }}">
                                    @foreach ($buoiOptions as $b)
                                        <option value="{{ $b }}" @selected($v('buoi_'.$lan) === $b)>{{ $b }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-2">
                                <label for="gio_{{ $lan }}">Giờ</label>
                                <input type="text" class="form-control form-control-sm js-tg" id="gio_{{ $lan }}" name="gio_{{ $lan }}" value="{{ $v('gio_'.$lan) }}" maxlength="20" placeholder="vd. 7h, 13h30">
                            </div>
                            <div class="form-group col-md-2">
                                <label for="ngay_{{ $lan }}">Ngày</label>
                                <input type="date" class="form-control form-control-sm js-tg" id="ngay_{{ $lan }}" name="ngay_{{ $lan }}" value="{{ $v('ngay_'.$lan) }}" @if ($lan === 1) required @endif>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Sẽ in</label>
                                <div class="form-control form-control-sm bg-light text-truncate" id="xem_{{ $lan }}">—</div>
                            </div>
                        </div>
                    @endforeach
                    <div class="form-group">
                        <label for="dia_diem">Địa điểm (sau chữ «tại»)</label>
                        <input type="text" class="form-control form-control-sm" id="dia_diem" name="dia_diem" value="{{ $v('dia_diem') }}" maxlength="300" required>
                    </div>

                    <h6 class="font-weight-bold mb-2">2. Đối tượng tham gia</h6>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="tru">Trừ học viên của khoá (nếu có)</label>
                            <select class="form-control form-control-sm select2-multi-tru" id="tru" name="tru[]" multiple data-placeholder="Chọn học viên không được kiểm tra">
                                @foreach ($hocVien as $hv)
                                    <option value="{{ $hv['ho_ten'] }}" @selected(in_array($hv['ho_ten'], $truDaChon, true))>{{ $hv['ho_ten'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="ly_do_tru">Lý do trừ</label>
                            <input type="text" class="form-control form-control-sm" id="ly_do_tru" name="ly_do_tru" value="{{ $v('ly_do_tru') }}" maxlength="300">
                        </div>
                    </div>

                    <label class="mb-1">Học viên vắng, thi lại các khoá trước</label>
                    <div class="form-row align-items-center mb-2">
                        <div class="col-md-8">
                            <select class="form-control form-control-sm" id="tim-thi-lai"></select>
                        </div>
                        <div class="col-md-4">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-dong-trong">+ Dòng nhập tay</button>
                        </div>
                    </div>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered mb-0" id="bang-thi-lai">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width:3rem">STT</th>
                                    <th style="width:10rem; max-width:10rem">Họ và tên</th>
                                    <th style="width:10rem">Số định danh</th>
                                    <th style="width:8rem">Ngày sinh</th>
                                    <th style="width:5rem">Hạng</th>
                                    <th style="width:11rem">Nội dung KT</th>
                                    <th style="width:2.5rem"></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                        <p class="small text-muted mb-0 mt-1" id="thi-lai-trong">Chưa có học viên thi lại.</p>
                    </div>
                    <datalist id="dl-noi-dung">
                        @foreach ($noiDungOptions as $nd)
                            <option value="{{ $nd }}"></option>
                        @endforeach
                    </datalist>

                    <button type="submit" class="btn btn-sm btn-navy">Xuất Word</button>
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

        const form = document.getElementById('form-thong-bao');
        if (!form) return;

        const THU = ['Chủ nhật', 'Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy'];
        function xem(lan) {
            const ngay = document.getElementById('ngay_' + lan).value;
            const out = document.getElementById('xem_' + lan);
            if (!ngay) { out.textContent = '—'; return; }
            const d = new Date(ngay + 'T00:00:00');
            out.textContent = document.getElementById('buoi_' + lan).value + ' lúc ' + document.getElementById('gio_' + lan).value.trim()
                + ' ngày ' + ngay.split('-').reverse().join('/') + ' (' + THU[d.getDay()] + ')';
        }
        const coLan2 = document.getElementById('co_lan_2');
        function batLan2() {
            document.querySelectorAll('.lan-2 .js-tg').forEach(function (el) {
                el.disabled = !coLan2.checked;
                if (el.type === 'date' || el.id === 'gio_2') el.required = coLan2.checked;
            });
            document.getElementById('xem_2').classList.toggle('text-muted', !coLan2.checked);
        }
        document.querySelectorAll('.js-tg').forEach(function (el) {
            ['input', 'change'].forEach(function (ev) {
                el.addEventListener(ev, function () { xem(el.id.slice(-1)); });
            });
        });
        coLan2.addEventListener('change', batLan2);
        xem(1); xem(2); batLan2();

        const $tru = $('#tru');
        $tru.select2({
            theme: 'default',
            width: '100%',
            placeholder: $tru.data('placeholder') || 'Chọn học viên không được kiểm tra',
            allowClear: true,
            closeOnSelect: false,
            dropdownParent: $tru.closest('.form-group'),
            language: {
                noResults: function () { return 'Không có học viên'; },
                searching: function () { return 'Đang tìm…'; },
            },
        });

        const esc = function (s) { return $('<div>').text(s == null ? '' : s).html(); };
        const tbody = document.querySelector('#bang-thi-lai tbody');
        let thiLai = @json(array_values(old('thi_lai', $form['thi_lai'] ?? [])));

        function render() {
            tbody.innerHTML = thiLai.map(function (r, i) {
                const o = function (f, attrs) {
                    const tdStyle = f === 'ho_ten' ? ' style="max-width:10rem"' : '';
                    return '<td' + tdStyle + '><input type="text" class="form-control form-control-sm js-o" data-f="' + f + '" name="thi_lai[' + i + '][' + f + ']" value="' + esc(r[f]) + '"' + (attrs || '') + '></td>';
                };
                return '<tr data-i="' + i + '"><td class="text-center align-middle">' + (i + 1) + '</td>'
                    + o('ho_ten', ' maxlength="100" required') + o('dinh_danh', ' maxlength="20"') + o('ngay_sinh', ' maxlength="20" placeholder="dd/mm/yyyy"')
                    + o('hang', ' maxlength="10"') + o('noi_dung', ' maxlength="40" placeholder="Nhập LT, MP, H…" list="dl-noi-dung" required')
                    + '<td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger p-0 js-bo" title="Bỏ">&times;</button></td></tr>';
            }).join('');
            document.getElementById('thi-lai-trong').classList.toggle('d-none', thiLai.length > 0);
        }
        function syncField(el) {
            const tr = el.closest('tr');
            if (!tr) return;
            thiLai[+tr.dataset.i][el.dataset.f] = el.value;
        }
        tbody.addEventListener('input', function (e) {
            if (!e.target.classList.contains('js-o')) return;
            syncField(e.target);
        });
        tbody.addEventListener('change', function (e) {
            if (!e.target.classList.contains('js-o')) return;
            syncField(e.target);
        });
        tbody.addEventListener('click', function (e) {
            if (!e.target.closest('.js-bo')) return;
            thiLai.splice(+e.target.closest('tr').dataset.i, 1);
            render();
        });
        document.getElementById('btn-dong-trong').addEventListener('click', function () {
            thiLai.push({ ho_ten: '', dinh_danh: '', ngay_sinh: '', hang: '', noi_dung: 'LT' });
            render();
            tbody.querySelector('tr:last-child [data-f="ho_ten"]').focus();
        });

        const $tim = $('#tim-thi-lai');
        $tim.select2({
            theme: 'bootstrap4', width: '100%', placeholder: 'Tìm học viên khoá trước theo tên hoặc số định danh để thêm vào bảng',
            minimumInputLength: 2,
            ajax: {
                url: @json(route('qldtoto.thong-bao-kiem-tra-ket-thuc-mon.tim-hoc-vien')),
                delay: 300,
                data: function (p) { return { q: p.term, bo_khoa: @json($form['ma_kh'] ?? '') }; },
                processResults: function (data) { return data; },
            },
            language: {
                inputTooShort: function () { return 'Gõ ít nhất 2 ký tự'; },
                noResults: function () { return 'Không tìm thấy học viên'; },
                searching: function () { return 'Đang tìm…'; },
            },
        });
        $tim.on('select2:select', function (e) {
            const hv = e.params.data.hv;
            if (thiLai.some(function (r) { return r.dinh_danh && r.dinh_danh === hv.dinh_danh; })) {
                alert('Học viên này đã có trong bảng.');
            } else {
                thiLai.push({ ho_ten: hv.ho_ten, dinh_danh: hv.dinh_danh, ngay_sinh: hv.ngay_sinh, hang: hv.hang, noi_dung: 'LT' });
                render();
                tbody.querySelector('tr:last-child [data-f="noi_dung"]').select();
            }
            $tim.val(null).trigger('change');
        });

        document.getElementById('so_tb').addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '');
        });
        render();
    });
})(jQuery);
</script>
@endpush
