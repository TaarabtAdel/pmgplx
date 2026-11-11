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
            @if (session('error'))
                <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
            @endif

            <p class="text-muted small mb-3">
                Xuất Word theo mẫu <strong>Sổ tay giáo viên</strong> (mỗi giáo viên thực hành trên lịch khoá một file;
                nhiều GV → file ZIP). Học viên (phần II) lấy từ PMGPLX; ô nghỉ học để trống trên mẫu.
            </p>

            <form method="POST" action="{{ route('qldtoto.so-tay-giao-vien-thuc-hanh.export') }}">
                @csrf
                <div class="form-row">
                    <div class="form-group col-md-5">
                        <label for="ma_kh">Khoá (PMGPLX)</label>
                        <select class="form-control form-control-sm @error('ma_kh') is-invalid @enderror"
                                id="ma_kh" name="ma_kh" required>
                            <option value="">— Chọn khoá —</option>
                            @foreach ($khoaOptions as $kh)
                                <option value="{{ $kh->MaKH }}"
                                    @selected(old('ma_kh', $form['ma_kh']) === $kh->MaKH)>
                                    @if ($kh->TenKH)
                                        {{ $kh->TenKH }} ({{ $kh->MaKH }})
                                    @else
                                        {{ $kh->MaKH }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('ma_kh')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group col-md-3">
                        <label for="ten_lop">Tên lớp / khoá trên sổ</label>
                        <input type="text" class="form-control form-control-sm @error('ten_lop') is-invalid @enderror"
                               id="ten_lop" name="ten_lop" required maxlength="80"
                               value="{{ old('ten_lop', $form['ten_lop']) }}"
                               placeholder="vd. B01K39">
                        @error('ten_lop')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group col-md-4">
                        <label for="mon">Môn (bìa)</label>
                        <input type="text" class="form-control form-control-sm @error('mon') is-invalid @enderror"
                               id="mon" name="mon" maxlength="120"
                               value="{{ old('mon', $form['mon']) }}">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label for="qd_thanh_lap">Quyết định thành lập lớp (mục I)</label>
                        <textarea class="form-control form-control-sm @error('qd_thanh_lap') is-invalid @enderror"
                                  id="qd_thanh_lap" name="qd_thanh_lap" rows="2" maxlength="500"
                                  placeholder="Số / ngày QĐ — dòng 2 nếu cần xuống dòng">{{ old('qd_thanh_lap', $form['qd_thanh_lap']) }}</textarea>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="phuong_thuc">Phương thức tổ chức đào tạo</label>
                        <input type="text" class="form-control form-control-sm" id="phuong_thuc" name="phuong_thuc"
                               maxlength="200" value="{{ old('phuong_thuc', $form['phuong_thuc']) }}">
                    </div>
                </div>

                <div class="form-row mb-2">
                    <div class="form-group col-md-3 mb-0">
                        <label>Hạng GPLX (đọc từ khoá)</label>
                        <input type="text" class="form-control form-control-sm" id="hang_gplx" readonly placeholder="Chọn khoá">
                    </div>
                    <div class="form-group col-md-2 mb-0">
                        <label>Sĩ số</label>
                        <input type="text" class="form-control form-control-sm" id="so_luong_hv" readonly placeholder="—">
                    </div>
                    <div class="form-group col-md-7 mb-0">
                        <label>Giáo viên thực hành (sẽ xuất)</label>
                        <input type="text" class="form-control form-control-sm" id="danh_sach_gv" readonly placeholder="Chọn khoá">
                    </div>
                </div>

                <button type="submit" class="btn btn-sm btn-success">⬇ Xuất sổ tay (.docx / .zip)</button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const $maKh = $('#ma_kh');
    const urlTpl = @json(route('qldtoto.so-tay-giao-vien-thuc-hanh.khoa', ['maKh' => '__MA__']));

    function loadKhoa(ma) {
        if (!ma) {
            $('#ten_lop, #hang_gplx, #so_luong_hv, #danh_sach_gv').val('');
            return;
        }
        const url = urlTpl.replace('__MA__', encodeURIComponent(ma));
        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(r => r.ok ? r.json() : Promise.reject())
            .then(data => {
                const $tenLop = $('#ten_lop');
                if (!$tenLop.val() || $tenLop.data('auto') !== false) {
                    $tenLop.val(data.ten_lop || '').data('auto', true);
                }
                $('#hang_gplx').val(data.hang_gplx || '');
                $('#so_luong_hv').val(data.so_luong_hv ?? '');
                $('#danh_sach_gv').val((data.giao_vien || []).join(', '));
            })
            .catch(() => {});
    }

    $maKh.on('change', function () {
        $('#ten_lop').data('auto', true);
        loadKhoa($(this).val());
    });
    $('#ten_lop').on('input', function () { $(this).data('auto', false); });

    if ($maKh.val()) {
        loadKhoa($maKh.val());
    }
})();
</script>
@endpush
