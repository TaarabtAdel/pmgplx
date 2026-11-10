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
                Mẫu <code>bien-ban-xac-nhan-pho-bien-noi-quy-hoc-tap.docx</code>. Chọn khoá, chỉnh 3 học viên đại diện và thời gian phổ biến rồi bấm <strong>Xuất Word</strong>.
            </p>

            <form method="GET" action="{{ route('qldtoto.bien-ban-pho-bien-noi-quy.index') }}" class="form-row align-items-end mb-3">
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
                <p class="text-muted mb-0">Chọn khoá để lập biên bản.</p>
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

                @php $v = fn (string $key) => old($key, $form[$key] ?? ''); @endphp

                <form method="POST" action="{{ route('qldtoto.bien-ban-pho-bien-noi-quy.export') }}" id="form-bien-ban">
                    @csrf
                    <input type="hidden" name="ma_kh" value="{{ $form['ma_kh'] }}">

                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="ngay_lap">Ngày trên biên bản (góc phải)</label>
                            <input type="date" class="form-control form-control-sm" id="ngay_lap" name="ngay_lap" value="{{ $v('ngay_lap') }}" required>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="gio">Giờ họp</label>
                            <input type="text" class="form-control form-control-sm" id="gio" name="gio" value="{{ $v('gio') }}" placeholder="vd. 8" maxlength="10" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="ngay_hop">Ngày họp / phổ biến</label>
                            <input type="date" class="form-control form-control-sm" id="ngay_hop" name="ngay_hop" value="{{ $v('ngay_hop') }}" required>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="khoa_so">Khóa (số)</label>
                            <input type="text" class="form-control form-control-sm" id="khoa_so" name="khoa_so" value="{{ $v('khoa_so') }}" maxlength="20" required>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="hang_hoa">Hạng (tiêu đề IN HOA)</label>
                            <input type="text" class="form-control form-control-sm" id="hang_hoa" name="hang_hoa" value="{{ $v('hang_hoa') }}" maxlength="20" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="lop_mo_ta">Lớp (mô tả sau «Lớp …»)</label>
                            <input type="text" class="form-control form-control-sm" id="lop_mo_ta" name="lop_mo_ta" value="{{ $v('lop_mo_ta') }}" maxlength="80" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="hang_rieng">Hạng (trong nội dung mục 1)</label>
                            <input type="text" class="form-control form-control-sm" id="hang_rieng" name="hang_rieng" value="{{ $v('hang_rieng') }}" maxlength="40" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="dia_diem">Địa điểm</label>
                            <input type="text" class="form-control form-control-sm" id="dia_diem" name="dia_diem" value="{{ $v('dia_diem') }}" maxlength="300" required>
                        </div>
                    </div>

                    <h6 class="font-weight-bold mb-2">Đại diện Phòng Đào tạo phổ biến</h6>
                    <div class="form-row mb-3">
                        <div class="form-group col-md-2">
                            <label for="xung_ddt">Xưng hô</label>
                            <select class="form-control form-control-sm" id="xung_ddt" name="xung_ddt">
                                @foreach ($xungOptions as $x)
                                    <option value="{{ $x }}" @selected($v('xung_ddt') === $x)>{{ $x }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="ten_ddt">Họ tên</label>
                            <input type="text" class="form-control form-control-sm" id="ten_ddt" name="ten_ddt" value="{{ $v('ten_ddt') }}" maxlength="100" required>
                        </div>
                    </div>

                    <h6 class="font-weight-bold mb-2">3 học viên đại diện lớp (ký cuối biên bản)</h6>
                    <p class="text-muted small">Gợi ý từ danh sách khoá — có thể sửa tay hoặc chọn nhanh.</p>

                    @php
                        $ddOld = old('dai_dien', $form['dai_dien'] ?? []);
                    @endphp
                    @foreach ([0, 1, 2] as $i)
                        @php $dd = $ddOld[$i] ?? ['ma_dk' => '', 'ho_ten' => '', 'ngay_sinh' => '']; @endphp
                        <div class="form-row align-items-end border rounded p-2 mb-2 bg-light">
                            <div class="form-group col-md-1 mb-md-0">
                                <label class="text-muted small">STT</label>
                                <div class="font-weight-bold pt-1">{{ $i + 1 }}</div>
                            </div>
                            <div class="form-group col-md-4 mb-md-0">
                                <label for="hv_chon_{{ $i }}">Chọn học viên</label>
                                <select class="form-control form-control-sm hv-chon chon-hv-s2" id="hv_chon_{{ $i }}" data-i="{{ $i }}">
                                    <option value="">— Chọn —</option>
                                    @foreach ($hocVien as $hv)
                                        <option value="{{ $hv['ma_dk'] }}"
                                                data-ho-ten="{{ $hv['ho_ten'] }}"
                                                data-ngay-sinh="{{ $hv['ngay_sinh'] }}"
                                                @selected(($dd['ma_dk'] ?? '') === $hv['ma_dk'])>
                                            {{ $hv['ho_ten'] }} — {{ $hv['ngay_sinh'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-4 mb-md-0">
                                <label for="dd_ho_ten_{{ $i }}">Họ và tên</label>
                                <input type="text" class="form-control form-control-sm dd-ho-ten" id="dd_ho_ten_{{ $i }}"
                                       name="dai_dien[{{ $i }}][ho_ten]" value="{{ $dd['ho_ten'] ?? '' }}" maxlength="120" required>
                            </div>
                            <div class="form-group col-md-3 mb-md-0">
                                <label for="dd_ns_{{ $i }}">Ngày sinh</label>
                                <input type="text" class="form-control form-control-sm dd-ngay-sinh" id="dd_ns_{{ $i }}"
                                       name="dai_dien[{{ $i }}][ngay_sinh]" value="{{ $dd['ngay_sinh'] ?? '' }}" maxlength="20" placeholder="dd/mm/yyyy" required>
                            </div>
                        </div>
                    @endforeach

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
        const lang = { noResults: function () { return 'Không tìm thấy'; } };

        const $khoa = $('#ma_kh_loc');
        $khoa.select2({
            theme: 'bootstrap4',
            width: '100%',
            allowClear: true,
            placeholder: '— Chọn khoá —',
            dropdownParent: $khoa.closest('.form-group'),
            language: lang,
        });
        $khoa.on('select2:select', function () { this.form.submit(); });

        $('.chon-hv-s2').each(function () {
            const $sel = $(this);
            const i = $sel.data('i');
            $sel.select2({
                theme: 'bootstrap4',
                width: '100%',
                allowClear: true,
                placeholder: '— Chọn học viên —',
                dropdownParent: $sel.closest('.form-group'),
                language: lang,
            });
            $sel.on('select2:select', function () {
                const opt = this.options[this.selectedIndex];
                if (!opt || !opt.value) {
                    return;
                }
                const hoTen = document.getElementById('dd_ho_ten_' + i);
                const ns = document.getElementById('dd_ns_' + i);
                if (hoTen) {
                    hoTen.value = opt.dataset.hoTen || '';
                }
                if (ns) {
                    ns.value = opt.dataset.ngaySinh || '';
                }
            });
            $sel.on('select2:clear', function () {
                const hoTen = document.getElementById('dd_ho_ten_' + i);
                const ns = document.getElementById('dd_ns_' + i);
                if (hoTen) {
                    hoTen.value = '';
                }
                if (ns) {
                    ns.value = '';
                }
            });
        });
    });
})(jQuery);
</script>
@endpush
