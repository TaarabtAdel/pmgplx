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
            @if (! empty($item['mo_ta']))
                <div class="alert alert-info py-2 mb-3">
                    <strong>Ghi chú:</strong> {{ $item['mo_ta'] }}
                </div>
            @endif

            <p class="text-muted small mb-3">
                Xuất file Word theo mẫu <code>bao-cao-dang-ky</code>. Số công văn ghép thành
                <strong>{số}/TTML-{năm}</strong> (năm lấy từ ngày báo cáo). Mặc định ghi chú: «Đào tạo mới».
            </p>

            <form method="POST" action="{{ route('qldtoto.bao-cao-dang-ky.export') }}" id="form-bao-cao-dang-ky">
                @csrf
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="so_cong_van">Số công văn</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control @error('so_cong_van') is-invalid @enderror"
                                   id="so_cong_van" name="so_cong_van" value="{{ old('so_cong_van', $form['so_cong_van']) }}"
                                   required>
                            <div class="input-group-append">
                                <span class="input-group-text">/TTML-<span id="preview-nam">{{ $namHienTai }}</span></span>
                            </div>
                        </div>
                        @error('so_cong_van')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group col-md-3">
                        <label for="ngay_bao_cao">Ngày tháng năm (trên công văn)</label>
                        <input type="date" class="form-control form-control-sm @error('ngay_bao_cao') is-invalid @enderror"
                               id="ngay_bao_cao" name="ngay_bao_cao"
                               value="{{ old('ngay_bao_cao', $form['ngay_bao_cao']) }}" required>
                        @error('ngay_bao_cao')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
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
                </div>

                <div class="form-row">
                    <div class="form-group col-md-2">
                        <label for="hang_dt">Hạng đào tạo</label>
                        <input type="text" class="form-control form-control-sm" id="hang_dt" name="hang_dt"
                               value="{{ old('hang_dt', $form['hang_dt']) }}" required>
                    </div>
                    <div class="form-group col-md-2">
                        <label for="khoa_so">Khoá (số)</label>
                        <input type="text" class="form-control form-control-sm" id="khoa_so" name="khoa_so"
                               value="{{ old('khoa_so', $form['khoa_so']) }}" required
                               placeholder="vd. 56">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="khoa_lop">Khoá – Lớp (mã)</label>
                        <input type="text" class="form-control form-control-sm" id="khoa_lop" name="khoa_lop"
                               value="{{ old('khoa_lop', $form['khoa_lop']) }}" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="hang_gplx">Hạng giấy phép lái xe</label>
                        <input type="text" class="form-control form-control-sm" id="hang_gplx" name="hang_gplx"
                               value="{{ old('hang_gplx', $form['hang_gplx']) }}" required
                               placeholder="vd. B (số sàn)">
                    </div>
                    <div class="form-group col-md-2">
                        <label for="so_luong_hv">Số lượng học viên</label>
                        <input type="number" min="1" class="form-control form-control-sm" id="so_luong_hv" name="so_luong_hv"
                               value="{{ old('so_luong_hv', $form['so_luong_hv']) }}" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="ngay_kg">Ngày khai giảng</label>
                        <input type="text" class="form-control form-control-sm" id="ngay_kg" name="ngay_kg"
                               value="{{ old('ngay_kg', $form['ngay_kg']) }}" required placeholder="dd/mm/yyyy">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="ngay_bg">Ngày bế giảng</label>
                        <input type="text" class="form-control form-control-sm" id="ngay_bg" name="ngay_bg"
                               value="{{ old('ngay_bg', $form['ngay_bg']) }}" required placeholder="dd/mm/yyyy">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="ghi_chu">Ghi chú</label>
                        <input type="text" class="form-control form-control-sm" id="ghi_chu" name="ghi_chu"
                               value="{{ old('ghi_chu', $form['ghi_chu']) }}" placeholder="Đào tạo mới">
                    </div>
                </div>

                <button type="submit" class="btn btn-sm btn-navy">Xuất file Word (.docx)</button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const maKh = document.getElementById('ma_kh');
    const ngayBaoCao = document.getElementById('ngay_bao_cao');
    const previewNam = document.getElementById('preview-nam');
    const fields = ['hang_dt', 'khoa_so', 'khoa_lop', 'hang_gplx', 'so_luong_hv', 'ngay_kg', 'ngay_bg'];

    function updateNamPreview() {
        if (!ngayBaoCao || !previewNam || !ngayBaoCao.value) return;
        previewNam.textContent = ngayBaoCao.value.slice(0, 4);
    }

    async function fillFromKhoa() {
        const ma = maKh?.value;
        if (!ma) return;
        const url = @json(route('qldtoto.bao-cao-dang-ky.khoa', ['maKh' => '__MA__'])).replace('__MA__', encodeURIComponent(ma));
        try {
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            fields.forEach(function (id) {
                const el = document.getElementById(id);
                if (!el || data[id] === undefined) return;
                el.value = data[id];
            });
            if (data.so_luong_hv !== undefined) {
                const sl = document.getElementById('so_luong_hv');
                if (sl) sl.value = data.so_luong_hv;
            }
        } catch (e) { /* bỏ qua */ }
    }

    ngayBaoCao?.addEventListener('change', updateNamPreview);
    maKh?.addEventListener('change', fillFromKhoa);
    updateNamPreview();
})();
</script>
@endpush
