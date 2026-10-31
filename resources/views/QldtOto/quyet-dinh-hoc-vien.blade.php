{{-- Quyết định một học viên: bảo lưu ($loai = bao_luu) / cho thôi học ($loai = thoi_hoc). --}}
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
                Mẫu <code>{{ $mau }}</code> — mỗi quyết định cho một học viên.
                Chọn khoá, chọn học viên rồi bấm <strong>Xuất Word</strong>.
            </p>

            <form method="GET" action="{{ route($routeIndex) }}" class="form-row align-items-end mb-3">
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
                <p class="text-muted mb-0">Chọn khoá để tải danh sách học viên.</p>
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

                <form method="POST" action="{{ route($routeExport) }}" id="form-qd-hoc-vien">
                    @csrf
                    <input type="hidden" name="ma_kh" value="{{ $form['ma_kh'] }}">

                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="so_qd">Số quyết định</label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" id="so_qd" name="so_qd" inputmode="numeric"
                                       value="{{ $v('so_qd') }}" placeholder="vd. 487" maxlength="10" required>
                                <div class="input-group-append"><span class="input-group-text">/QĐ-TTML</span></div>
                            </div>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="ngay_quyet_dinh">Ngày quyết định</label>
                            <input type="date" class="form-control form-control-sm" id="ngay_quyet_dinh" name="ngay_quyet_dinh"
                                   value="{{ $v('ngay_quyet_dinh') }}" required>
                        </div>
                        @if ($loai === 'thoi_hoc')
                            <div class="form-group col-md-6">
                                <label for="gvcn">Giáo viên chủ nhiệm (căn cứ báo cáo học viên)</label>
                                <input type="text" class="form-control form-control-sm" id="gvcn" name="gvcn" list="ds-giao-vien"
                                       value="{{ $v('gvcn') }}" maxlength="120" placeholder="Gõ để chọn giáo viên" required>
                                <datalist id="ds-giao-vien">
                                    @foreach ($giaoVienTen as $ten)
                                        <option value="{{ $ten }}"></option>
                                    @endforeach
                                </datalist>
                            </div>
                        @endif
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="ma_dk">Học viên ({{ count($hocVien) }} trong khoá)</label>
                            <select class="form-control form-control-sm" id="ma_dk" name="ma_dk">
                                <option value=""></option>
                                @foreach ($hocVien as $hv)
                                    <option value="{{ $hv['ma_dk'] }}" data-ho-ten="{{ $hv['ho_ten'] }}" data-ngay-sinh="{{ $hv['ngay_sinh'] }}"
                                            data-noi-cu-tru="{{ $hv['noi_cu_tru'] }}" @selected($v('ma_dk') === $hv['ma_dk'])>
                                        {{ $hv['ho_ten'] }} — {{ $hv['ngay_sinh'] }} — {{ $hv['so_cmnd'] }} ({{ $hv['ma_dk'] }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="ho_ten">Họ tên (trên quyết định)</label>
                            <input type="text" class="form-control form-control-sm" id="ho_ten" name="ho_ten" value="{{ $v('ho_ten') }}" maxlength="120" required>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="ngay_sinh">Sinh ngày</label>
                            <input type="text" class="form-control form-control-sm" id="ngay_sinh" name="ngay_sinh" value="{{ $v('ngay_sinh') }}"
                                   placeholder="dd/mm/yyyy" maxlength="20" required>
                        </div>
                    </div>

                    @if ($loai === 'thoi_hoc')
                        <div class="form-group">
                            <label for="noi_cu_tru">Nơi cư trú</label>
                            <input type="text" class="form-control form-control-sm" id="noi_cu_tru" name="noi_cu_tru" value="{{ $v('noi_cu_tru') }}" maxlength="200" required>
                        </div>
                    @endif

                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="hang">Hạng</label>
                            <input type="text" class="form-control form-control-sm" id="hang" name="hang" value="{{ $v('hang') }}" maxlength="40" required>
                            <small class="text-muted">«… lớp đào tạo lái xe hạng … Khóa …»</small>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="khoa_so">Khóa (số)</label>
                            <input type="text" class="form-control form-control-sm" id="khoa_so" name="khoa_so" value="{{ $v('khoa_so') }}" maxlength="20" required>
                        </div>
                        @if ($loai === 'bao_luu')
                            <div class="form-group col-md-2">
                                <label for="ten_lop">Lớp (Điều 2)</label>
                                <input type="text" class="form-control form-control-sm" id="ten_lop" name="ten_lop" value="{{ $v('ten_lop') }}" maxlength="40" required>
                                <small class="text-muted">«… thời điểm lớp … kiểm tra»</small>
                            </div>
                        @else
                            <div class="form-group col-md-2">
                                <label for="lop_tieu_de">Lớp (tiêu đề)</label>
                                <input type="text" class="form-control form-control-sm" id="lop_tieu_de" name="lop_tieu_de" value="{{ $v('lop_tieu_de') }}" maxlength="40" required>
                                <small class="text-muted">«… 01 học viên B-K53»</small>
                            </div>
                        @endif
                    </div>

                    <div class="form-group">
                        <label for="ly_do">{{ $loai === 'bao_luu' ? 'Lý do bảo lưu' : 'Lý do cho thôi học' }}</label>
                        <textarea class="form-control form-control-sm" id="ly_do" name="ly_do" rows="2" maxlength="500" required>{{ $v('ly_do') }}</textarea>
                    </div>

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
            theme: 'bootstrap4', width: '100%', allowClear: true, placeholder: '— Chọn khoá —',
            dropdownParent: $khoa.closest('.form-group'), language: lang,
        });
        $khoa.on('select2:select', function () { this.form.submit(); });

        const $hv = $('#ma_dk');
        if (!$hv.length) return;
        $hv.select2({
            theme: 'bootstrap4', width: '100%', allowClear: true, placeholder: '— Chọn học viên —',
            dropdownParent: $hv.closest('.form-group'), language: lang,
        });
        $hv.on('select2:select', function () {
            const opt = this.options[this.selectedIndex];
            document.getElementById('ho_ten').value = opt.dataset.hoTen || '';
            document.getElementById('ngay_sinh').value = opt.dataset.ngaySinh || '';
            const noiCuTru = document.getElementById('noi_cu_tru');
            if (noiCuTru) noiCuTru.value = opt.dataset.noiCuTru || '';
        });

        document.getElementById('so_qd').addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '');
        });
    });
})(jQuery);
</script>
@endpush
