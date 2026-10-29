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
                Xuất Excel theo mẫu <code>danh-sach-hoc-vien.xlsx</code>.
                Học viên lấy từ PMGPLX theo khoá; địa chỉ thường trú / nơi cư trú tra <code>DM_DVHC</code> (ưu tiên sau sáp nhập), giống báo cáo cập nhật GPLX.
            </p>

            <form method="POST" action="{{ route('qldtoto.danh-sach-hoc-sinh-lai-xe.export') }}">
                @csrf
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="so_quyet_dinh">Quyết định số</label>
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
                        <label for="ngay_quyet_dinh">Ngày (trên quyết định)</label>
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
                    <div class="form-group col-md-4">
                        <label for="hang_gplx">Hạng (dòng quyết định kèm theo)</label>
                        <input type="text" class="form-control form-control-sm" id="hang_gplx" name="hang_gplx"
                               value="{{ old('hang_gplx', $form['hang_gplx']) }}" required
                               placeholder="vd. B (Số sàn)">
                    </div>
                    <div class="form-group col-md-2">
                        <label for="khoa_so">Khoá</label>
                        <input type="text" class="form-control form-control-sm" id="khoa_so" name="khoa_so"
                               value="{{ old('khoa_so', $form['khoa_so']) }}" required placeholder="vd. 56">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Số học viên (tham khảo)</label>
                        <input type="text" class="form-control form-control-sm" id="so_luong_hv" readonly
                               value="{{ old('so_luong_hv', $form['so_luong_hv']) }}">
                    </div>
                </div>

                <button type="submit" class="btn btn-sm btn-navy">⬇ Xuất Excel (.xlsx)</button>
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
        if (!ma) return;
        const url = @json(route('qldtoto.danh-sach-hoc-sinh-lai-xe.khoa', ['maKh' => '__MA__'])).replace('__MA__', encodeURIComponent(ma));
        try {
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            const hg = document.getElementById('hang_gplx');
            const ks = document.getElementById('khoa_so');
            const sl = document.getElementById('so_luong_hv');
            if (hg && data.hang_gplx) hg.value = data.hang_gplx;
            if (ks && data.khoa_so) ks.value = data.khoa_so;
            if (sl && data.so_luong_hv != null) sl.value = data.so_luong_hv;
        } catch (e) { /* bỏ qua */ }
    }
    maKh?.addEventListener('change', fillKhoa);
    if (maKh?.value) fillKhoa();
})();
</script>
@endpush
