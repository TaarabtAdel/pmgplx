@extends('PMGPLX.layouts.quan-ly')

@php
    $dangSua = $lich->exists;
    $tieuDe = $dangSua ? 'Lịch dự kiến: '.$lich->TenKhoa : 'Tạo lịch dự kiến thực hành';
@endphp

@section('title', $tieuDe)

@push('styles')
<style>
    #bang-cap-gv td { vertical-align: middle; }
    #bang-cap-gv .col-stt { width: 3rem; text-align: center; }
    #bang-cap-gv .col-xe { width: 16rem; }
    #bang-cap-gv .col-xoa { width: 3rem; }
    .ds-nghi { display: flex; flex-wrap: wrap; gap: .35rem; min-height: 1.9rem; }
    .chip-nghi { display: inline-flex; align-items: center; border: 1px solid #ced4da; border-radius: 1rem; background: #f8f9fa; padding: .1rem .2rem .1rem .6rem; font-size: .85rem; line-height: 1.4; }
    .chip-nghi .thu { font-weight: 600; margin-right: .25rem; }
    .chip-nghi .thu.cn { color: #dc3545; }
    .chip-nghi .ly-do { color: #6c757d; margin-left: .3rem; max-width: 12rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .chip-nghi .btn-xoa-nghi { border: 0; background: none; color: #dc3545; font-size: 1rem; line-height: 1; padding: 0 .35rem; }
</style>
@endpush

@section('content')
    @include('QldtOto.lich-du-kien.thuc-hanh.partials.breadcrumb', ['trangCon' => $dangSua ? $lich->TenKhoa : 'Tạo mới'])

    <form method="POST" id="form-lich-du-kien" autocomplete="off"
          action="{{ $dangSua ? route('qldtoto.lich-du-kien.thuc-hanh.update', $lich) : route('qldtoto.lich-du-kien.thuc-hanh.store') }}">
        @csrf
        @if ($dangSua)
            @method('PUT')
        @endif

        <div class="card card-panel">
            <div class="card-header">{{ $tieuDe }}</div>
            <div class="card-body">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-3">
                        <label for="ten_khoa">Tên khoá <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" id="ten_khoa" name="ten_khoa" maxlength="255" required
                               value="{{ old('ten_khoa', $lich->TenKhoa) }}" placeholder="vd K26B10">
                    </div>
                    <div class="form-group col-md-2">
                        <label for="hang">Hạng <span class="text-danger">*</span></label>
                        <select class="form-control form-control-sm" id="hang" name="hang" required>
                            <option value="">—Chọn hạng—</option>
                            @foreach ($hangOptions as $ma => $nhan)
                                <option value="{{ $ma }}" @selected(old('hang', $lich->Hang) === $ma)>{{ $nhan }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label for="ngay_bat_dau">Ngày bắt đầu thực hành <span class="text-danger">*</span></label>
                        <input type="date" class="form-control form-control-sm" id="ngay_bat_dau" name="ngay_bat_dau" required
                               value="{{ old('ngay_bat_dau', $lich->NgayBatDauThucHanh?->format('Y-m-d')) }}">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="xe_tu_dong">Xe tự động</label>
                        <select class="form-control form-control-sm chon-s2" id="xe_tu_dong" name="xe_tu_dong" data-placeholder="Chọn xe số tự động…">
                            <option value=""></option>
                            @foreach ($xeTuDongOptions as $x)
                                <option value="{{ $x['bien_so'] }}" @selected($xeTuDong === $x['bien_so'])>{{ $x['nhan'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label for="cabin_ngay">Cabin của cặp trước</label>
                        <input type="date" class="form-control form-control-sm" id="cabin_ngay" name="cabin_ngay"
                               value="{{ old('cabin_ngay', $lich->CabinCapTruocNgay?->format('Y-m-d')) }}"
                               title="Ngày cabin của cặp trước — để trống nếu chưa có">
                    </div>
                </div>

                <fieldset class="border rounded px-3 pt-2 mb-3">
                    <legend class="w-auto px-2 mb-0 small font-weight-bold">
                        Ngày nghỉ <span class="badge badge-secondary" id="so-ngay-nghi">{{ count($ngayNghi) }}</span>
                    </legend>
                    <div class="form-row align-items-center mb-2">
                        <div class="col-auto">
                            <div class="input-group input-group-sm">
                                <input type="date" class="form-control" id="nghi-mot-ngay" aria-label="Ngày nghỉ" style="width: 13rem; flex: 0 0 13rem;">
                                <input type="text" class="form-control" id="nghi-ghi-chu" maxlength="255" placeholder="Lý do (tuỳ chọn)" aria-label="Lý do" style="width: 12rem;">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-primary" id="btn-them-mot-ngay">＋ Thêm</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-them-thu-5"
                                    title="Thêm các thứ 5 trong 30 ngày kể từ Ngày bắt đầu thực hành">＋ Thứ 5 (30 ngày)</button>
                        </div>
                        <div class="col-auto ml-auto">
                            <button type="button" class="btn btn-sm btn-link text-danger px-0" id="btn-xoa-het-nghi">Xoá hết</button>
                        </div>
                    </div>
                    <div id="nghi-thong-bao" class="small text-info mb-2 d-none"></div>
                    <div class="ds-nghi mb-3" id="ds-nghi">
                        @foreach ($ngayNghi as $i => $n)
                            <span class="chip-nghi">
                                <input type="hidden" data-field="ngay" name="ngay_nghi[{{ $i }}][ngay]" value="{{ $n['ngay'] ?? '' }}">
                                <input type="hidden" data-field="ghi_chu" name="ngay_nghi[{{ $i }}][ghi_chu]" value="{{ $n['ghi_chu'] ?? '' }}">
                                <span class="thu"></span><span class="ngay"></span><span class="ly-do"></span>
                                <button type="button" class="btn-xoa-nghi" title="Bỏ ngày này" aria-label="Bỏ ngày này">×</button>
                            </span>
                        @endforeach
                        <span id="nghi-trong" class="small text-muted align-self-center {{ count($ngayNghi) ? 'd-none' : '' }}">Chưa có ngày nghỉ.</span>
                    </div>
                </fieldset>

                <fieldset class="border rounded px-3 pt-2 mb-3">
                    <legend class="w-auto px-2 mb-0 small font-weight-bold">
                        Cặp giáo viên sáng – chiều <span class="badge badge-secondary" id="so-cap-gv">{{ count($capGv) }}</span>
                    </legend>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-2" id="bang-cap-gv">
                            <thead class="thead-light">
                                <tr>
                                    <th class="col-stt">#</th>
                                    <th>Giáo viên sáng</th>
                                    <th>Giáo viên chiều</th>
                                    <th class="col-xe">Xe</th>
                                    <th class="col-xoa"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($capGv as $i => $c)
                                    @include('QldtOto.lich-du-kien.thuc-hanh.partials.dong-cap-gv', ['i' => $i, 'c' => $c])
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <button type="button" class="btn btn-sm btn-outline-primary mr-3" id="btn-them-cap-gv">＋ Thêm cặp</button>
                        <span class="small text-muted">Chọn giáo viên mà ô Xe đang trống thì tự điền xe đã gán cho giáo viên đó (danh mục giáo viên).</span>
                    </div>
                </fieldset>

                <div class="d-flex align-items-center">
                    <a href="{{ route('qldtoto.lich-du-kien.thuc-hanh.index') }}" class="btn btn-sm btn-outline-secondary mr-auto">← Danh sách</a>
                    @if ($dangSua)
                        <button type="button" class="btn btn-sm btn-outline-danger mr-2" id="btn-xoa-lich">Xoá lịch</button>
                    @endif
                    <button type="submit" class="btn btn-success mr-2" name="hanh_dong" value="sinh_lich"
                            title="Lưu thông tin rồi sinh lại lịch: điền lần lượt các bài của hạng vào các ngày từ Ngày bắt đầu thực hành, bỏ ngày nghỉ">Sinh lịch</button>
                    <button type="submit" class="btn btn-navy">{{ $dangSua ? 'Lưu' : 'Tạo lịch dự kiến' }}</button>
                </div>
            </div>
        </div>
    </form>

    @if ($dangSua)
        <form method="POST" action="{{ route('qldtoto.lich-du-kien.thuc-hanh.destroy', $lich) }}" id="form-xoa-lich" class="d-none">
            @csrf
            @method('DELETE')
        </form>

        @include('QldtOto.lich-du-kien.thuc-hanh.partials.bang-lich')
    @endif

    <template id="mau-cap-gv">
        @include('QldtOto.lich-du-kien.thuc-hanh.partials.dong-cap-gv', ['i' => null, 'c' => []])
    </template>

    <template id="mau-chip-nghi">
        <span class="chip-nghi">
            <input type="hidden" data-field="ngay">
            <input type="hidden" data-field="ghi_chu">
            <span class="thu"></span><span class="ngay"></span><span class="ly-do"></span>
            <button type="button" class="btn-xoa-nghi" title="Bỏ ngày này" aria-label="Bỏ ngày này">×</button>
        </span>
    </template>
@endsection

@push('scripts')
<script>
    (function () {
        var ds = document.getElementById('ds-nghi');
        var trong = document.getElementById('nghi-trong');
        var thongBao = document.getElementById('nghi-thong-bao');
        var THU = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];

        function chips() {
            return Array.prototype.slice.call(ds.querySelectorAll('.chip-nghi'));
        }

        function ngayCua(chip) {
            return chip.querySelector('[data-field="ngay"]').value;
        }

        function taoDate(ymd) {
            var p = ymd.split('-');
            return new Date(+p[0], +p[1] - 1, +p[2]);
        }

        function ymdCua(d) {
            return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        }

        function veChip(chip) {
            var ymd = ngayCua(chip);
            var thu = THU[taoDate(ymd).getDay()];
            var p = ymd.split('-');
            var lyDo = chip.querySelector('[data-field="ghi_chu"]').value;
            chip.querySelector('.thu').textContent = thu;
            chip.querySelector('.thu').classList.toggle('cn', thu === 'CN');
            chip.querySelector('.ngay').textContent = p[2] + '/' + p[1] + '/' + p[0];
            chip.querySelector('.ly-do').textContent = lyDo ? '· ' + lyDo : '';
            chip.title = lyDo;
        }

        // Sắp theo ngày + đánh lại name để server nhận đúng mảng.
        function sapXep() {
            var list = chips().sort(function (a, b) {
                return ngayCua(a) < ngayCua(b) ? -1 : (ngayCua(a) > ngayCua(b) ? 1 : 0);
            });
            list.forEach(function (chip, i) {
                ds.insertBefore(chip, trong);
                chip.querySelectorAll('[data-field]').forEach(function (inp) {
                    inp.name = 'ngay_nghi[' + i + '][' + inp.getAttribute('data-field') + ']';
                });
                veChip(chip);
            });
            trong.classList.toggle('d-none', list.length > 0);
            document.getElementById('so-ngay-nghi').textContent = String(list.length);
        }

        function themNgay(ymd, ghiChu) {
            if (chips().some(function (c) { return ngayCua(c) === ymd; })) {
                return false;
            }
            var chip = document.getElementById('mau-chip-nghi').content.firstElementChild.cloneNode(true);
            chip.querySelector('[data-field="ngay"]').value = ymd;
            chip.querySelector('[data-field="ghi_chu"]').value = ghiChu;
            ds.insertBefore(chip, trong);
            return true;
        }

        function bao(text) {
            thongBao.textContent = text;
            thongBao.classList.toggle('d-none', ! text);
        }

        document.getElementById('btn-them-mot-ngay').addEventListener('click', function () {
            var inp = document.getElementById('nghi-mot-ngay');
            if (! inp.value) {
                bao('Chọn ngày cần thêm.');
                return;
            }
            bao(themNgay(inp.value, document.getElementById('nghi-ghi-chu').value.trim()) ? '' : 'Ngày này đã có trong danh sách.');
            inp.value = '';
            sapXep();
        });

        // 30 ngày kể từ Ngày bắt đầu thực hành, tính cả ngày bắt đầu.
        document.getElementById('btn-them-thu-5').addEventListener('click', function () {
            var oBatDau = document.getElementById('ngay_bat_dau');
            if (! oBatDau.value) {
                bao('Nhập Ngày bắt đầu thực hành trước.');
                oBatDau.focus();
                return;
            }
            var d = taoDate(oBatDau.value);
            var tu = new Date(d);
            var den = new Date(d);
            den.setDate(den.getDate() + 29);
            var ghiChu = document.getElementById('nghi-ghi-chu').value.trim();
            var them = 0, co = 0;
            for (; d <= den; d.setDate(d.getDate() + 1)) {
                if (d.getDay() === 4) {
                    themNgay(ymdCua(d), ghiChu) ? them++ : co++;
                }
            }
            var fmt = function (x) { return String(x.getDate()).padStart(2, '0') + '/' + String(x.getMonth() + 1).padStart(2, '0'); };
            bao('Thứ 5 từ ' + fmt(tu) + ' đến ' + fmt(den) + ': thêm ' + them + ' ngày' + (co ? ', ' + co + ' ngày đã có' : '') + '.');
            sapXep();
        });

        document.getElementById('btn-xoa-het-nghi').addEventListener('click', function () {
            if (chips().length && confirm('Bỏ tất cả ngày nghỉ?')) {
                chips().forEach(function (c) { c.remove(); });
                bao('');
                sapXep();
            }
        });

        ds.addEventListener('click', function (e) {
            var btn = e.target.closest('.btn-xoa-nghi');
            if (btn) {
                btn.closest('.chip-nghi').remove();
                sapXep();
            }
        });

        // --- Cặp giáo viên sáng / chiều ---
        var bangCap = document.querySelector('#bang-cap-gv tbody');

        function hienThiGv(item) {
            var xe = item.element ? item.element.getAttribute('data-xe') : '';
            if (! xe) {
                return item.text;
            }
            return $('<span>').text(item.text).append($('<small class="text-muted ml-1">').text('· xe ' + xe));
        }

        function khoiTaoS2(sel) {
            $(sel).select2({
                theme: 'bootstrap4',
                width: '100%',
                allowClear: true,
                placeholder: sel.getAttribute('data-placeholder') || '',
                templateResult: sel.classList.contains('chon-gv') ? hienThiGv : undefined,
                language: {
                    noResults: function () { return 'Không tìm thấy'; },
                    removeAllItems: function () { return 'Bỏ chọn'; },
                },
            });
            // Bấm × chỉ bỏ chọn, không mở danh sách.
            $(sel).on('select2:clearing', function () {
                $(this).data('vuaBoChon', true);
            }).on('select2:opening', function (e) {
                if ($(this).data('vuaBoChon')) {
                    $(this).removeData('vuaBoChon');
                    e.preventDefault();
                }
            });
        }

        function danhSoCap() {
            var rows = bangCap.querySelectorAll('.dong-cap-gv');
            rows.forEach(function (tr, i) {
                tr.querySelector('.so-cap').textContent = String(i + 1);
                tr.querySelectorAll('[data-field]').forEach(function (sel) {
                    sel.name = 'cap_gv[' + i + '][' + sel.getAttribute('data-field') + ']';
                });
            });
            document.getElementById('so-cap-gv').textContent = String(rows.length);
        }

        document.querySelectorAll('.chon-s2').forEach(khoiTaoS2);

        document.getElementById('btn-them-cap-gv').addEventListener('click', function () {
            var tr = document.getElementById('mau-cap-gv').content.firstElementChild.cloneNode(true);
            bangCap.appendChild(tr);
            tr.querySelectorAll('.chon-s2').forEach(khoiTaoS2);
            danhSoCap();
            $(tr.querySelector('.chon-gv')).select2('open');
        });

        bangCap.addEventListener('click', function (e) {
            var btn = e.target.closest('.btn-xoa-cap');
            if (! btn) {
                return;
            }
            var tr = btn.closest('tr');
            $(tr).find('.chon-s2').select2('destroy');
            tr.remove();
            danhSoCap();
        });

        $(bangCap).on('change', '.chon-gv', function () {
            var opt = this.options[this.selectedIndex];
            var xe = opt ? opt.getAttribute('data-xe') : '';
            var selXe = this.closest('tr').querySelector('[data-field="xe"]');
            if (xe && ! selXe.value) {
                $(selXe).val(xe).trigger('change');
            }
        });

        danhSoCap();

        var btnXoa = document.getElementById('btn-xoa-lich');
        if (btnXoa) {
            btnXoa.addEventListener('click', function () {
                if (confirm('Xoá lịch dự kiến này?')) {
                    document.getElementById('form-xoa-lich').submit();
                }
            });
        }

        sapXep();
    })();
</script>
@endpush
