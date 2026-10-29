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
                Xuất file Word theo mẫu <code>quyet-dinh-thanh-lap-lop-hoc</code>.
                Số quyết định ghép thành <strong>{số}/QĐ-TTML</strong>.
            </p>

            <form method="POST" action="{{ route('qldtoto.quyet-dinh-thanh-lap-lop-hoc.export') }}">
                @csrf
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="so_quyet_dinh">Số quyết định</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control @error('so_quyet_dinh') is-invalid @enderror"
                                   id="so_quyet_dinh" name="so_quyet_dinh"
                                   value="{{ old('so_quyet_dinh', $form['so_quyet_dinh']) }}" required>
                            <div class="input-group-append">
                                <span class="input-group-text">/QĐ-TTML</span>
                            </div>
                        </div>
                        @error('so_quyet_dinh')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group col-md-3">
                        <label for="ngay_quyet_dinh">Ngày tháng năm (trên quyết định)</label>
                        <input type="date" class="form-control form-control-sm @error('ngay_quyet_dinh') is-invalid @enderror"
                               id="ngay_quyet_dinh" name="ngay_quyet_dinh"
                               value="{{ old('ngay_quyet_dinh', $form['ngay_quyet_dinh']) }}" required>
                        @error('ngay_quyet_dinh')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                    <div class="form-group col-md-3">
                        <label for="hang_gplx">Hạng (trên quyết định)</label>
                        <input type="text" class="form-control form-control-sm" id="hang_gplx" name="hang_gplx"
                               value="{{ old('hang_gplx', $form['hang_gplx']) }}" required
                               placeholder="vd. B (Số sàn)">
                    </div>
                    <div class="form-group col-md-2">
                        <label for="khoa_so">Khoá (số)</label>
                        <input type="text" class="form-control form-control-sm" id="khoa_so" name="khoa_so"
                               value="{{ old('khoa_so', $form['khoa_so']) }}" required placeholder="vd. 56">
                    </div>
                    <div class="form-group col-md-2">
                        <label for="so_luong_hv">Số lượng học viên</label>
                        <input type="number" min="1" class="form-control form-control-sm" id="so_luong_hv" name="so_luong_hv"
                               value="{{ old('so_luong_hv', $form['so_luong_hv']) }}" required>
                    </div>
                    <div class="form-group col-md-2">
                        <label for="ngay_kg">Ngày khai giảng</label>
                        <input type="text" class="form-control form-control-sm" id="ngay_kg" name="ngay_kg"
                               value="{{ old('ngay_kg', $form['ngay_kg']) }}" required placeholder="dd/mm/yyyy">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="ngay_bg">Ngày kết thúc khóa học</label>
                        <input type="text" class="form-control form-control-sm" id="ngay_bg" name="ngay_bg"
                               value="{{ old('ngay_bg', $form['ngay_bg']) }}" required placeholder="dd/mm/yyyy">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="giao_vien_hd">Giáo viên hướng dẫn tự học có hướng dẫn</label>
                        <input type="text" class="form-control form-control-sm" id="giao_vien_hd" name="giao_vien_hd"
                               value="{{ old('giao_vien_hd', $form['giao_vien_hd']) }}" required>
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
    const fields = ['hang_gplx', 'khoa_so', 'so_luong_hv', 'ngay_kg', 'ngay_bg'];

    async function fillFromKhoa() {
        const ma = maKh?.value;
        if (!ma) return;
        const url = @json(route('qldtoto.quyet-dinh-thanh-lap-lop-hoc.khoa', ['maKh' => '__MA__'])).replace('__MA__', encodeURIComponent(ma));
        try {
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            fields.forEach(function (id) {
                const el = document.getElementById(id);
                if (el && data[id] !== undefined) el.value = data[id];
            });
        } catch (e) { /* bỏ qua */ }
    }

    maKh?.addEventListener('change', fillFromKhoa);
})();
</script>
@endpush
