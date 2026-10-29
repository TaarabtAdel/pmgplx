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
                Xuất Word theo mẫu <code>so-theo-doi-ket-qua-hoc-tap.docx</code>.
                Danh sách học viên và cột lý thuyết / thực hành / kết quả khoá lấy từ PMGPLX (<code>NguoiLX_HoSo</code>).
            </p>

            <form method="POST" action="{{ route('qldtoto.so-theo-doi-ket-qua-hoc-tap.export') }}">
                @csrf
                <div class="form-row">
                    <div class="form-group col-md-6">
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
                                    @if ($kh->HangDT)
                                        — {{ $kh->HangDT }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('ma_kh')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group col-md-3">
                        <label for="ten_khoa">Tên khoá trên sổ (tiêu đề &amp; cột KHÓA HỌC)</label>
                        <input type="text" class="form-control form-control-sm @error('ten_khoa') is-invalid @enderror"
                               id="ten_khoa" name="ten_khoa" required maxlength="80"
                               value="{{ old('ten_khoa', $form['ten_khoa']) }}"
                               placeholder="vd. B01K39">
                        @error('ten_khoa')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group col-md-3">
                        <label for="ngay_lap">Ngày lập sổ</label>
                        <input type="date" class="form-control form-control-sm @error('ngay_lap') is-invalid @enderror"
                               id="ngay_lap" name="ngay_lap" required
                               value="{{ old('ngay_lap', $form['ngay_lap']) }}">
                        @error('ngay_lap')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label>Số học viên (tham khảo)</label>
                        <input type="text" class="form-control form-control-sm" id="so_luong_hv" readonly
                               placeholder="Chọn khoá">
                    </div>
                </div>

                <button type="submit" class="btn btn-sm btn-navy">⬇ Xuất file Word (.docx)</button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const maKh = document.getElementById('ma_kh');
    async function fillKhoa() {
        const ma = maKh?.value;
        const ten = document.getElementById('ten_khoa');
        const sl = document.getElementById('so_luong_hv');
        if (!ma) {
            if (ten && !ten.value) ten.placeholder = 'vd. B01K39';
            if (sl) sl.value = '';
            return;
        }
        const url = @json(route('qldtoto.so-theo-doi-ket-qua-hoc-tap.khoa', ['maKh' => '__MA__'])).replace('__MA__', encodeURIComponent(ma));
        try {
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            if (ten && !ten.value && data.ten_khoa) ten.value = data.ten_khoa;
            if (sl && data.so_luong_hv != null) sl.value = data.so_luong_hv;
        } catch (e) { /* bỏ qua */ }
    }
    maKh?.addEventListener('change', fillKhoa);
    if (maKh?.value) fillKhoa();
})();
</script>
@endpush
