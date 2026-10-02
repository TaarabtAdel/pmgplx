@php
    $locAction = $locAction ?? route('daotao.pdt.phan-cong-dao-tao.danh-sach-tu-lich');
    $showLoai = (bool) ($showLoai ?? false);
@endphp
<form method="GET" action="{{ $locAction }}">
    <div class="form-row align-items-end">
        <div class="form-group col-md-3">
            <label for="filter_ma_kh">Khoá đào tạo</label>
            <select class="form-control form-control-sm" id="filter_ma_kh" name="ma_kh">
                <option value="">— Tất cả —</option>
                @foreach ($khoaHocs as $kh)
                    <option value="{{ $kh->MaKH }}" @selected(($filters['ma_kh'] ?? '') === $kh->MaKH)>
                        {{ $kh->TenKH }} ({{ $kh->MaKH }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group col-md-3">
            <label for="filter_ma_gv">Giáo viên</label>
            <select class="form-control form-control-sm" id="filter_ma_gv" name="ma_gv">
                <option value="">— Tất cả —</option>
                @foreach ($giaoViens as $gv)
                    <option value="{{ $gv->MaGV }}" @selected(($filters['ma_gv'] ?? '') === $gv->MaGV)>
                        {{ $gv->ho_ten }} ({{ $gv->MaGV }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group col-md-2">
            <label for="filter_bien_so_xe">Biển số xe</label>
            <select class="form-control form-control-sm" id="filter_bien_so_xe" name="bien_so_xe">
                <option value="">— Tất cả —</option>
                @foreach ($xeTaps as $xe)
                    <option value="{{ $xe }}" @selected(($filters['bien_so_xe'] ?? '') === $xe)>{{ $xe }}</option>
                @endforeach
            </select>
        </div>
        @if ($showLoai)
            <div class="form-group col-md-2">
                <label for="loai">Loại giảng dạy</label>
                <select class="form-control form-control-sm" id="loai" name="loai">
                    <option value="tat_ca" @selected(($filters['loai'] ?? 'tat_ca') === 'tat_ca')>Tất cả nguồn</option>
                    <option value="ly_thuyet" @selected(($filters['loai'] ?? '') === 'ly_thuyet')>Chỉ lịch GV (LT)</option>
                    <option value="thuc_hanh" @selected(($filters['loai'] ?? '') === 'thuc_hanh')>Chỉ lịch xe (TH)</option>
                </select>
            </div>
        @endif
        <div class="form-group col-md-2">
            <button type="submit" class="btn btn-sm btn-primary btn-block">Lọc</button>
            <a href="{{ $locAction }}" class="btn btn-sm btn-outline-secondary btn-block mt-1" title="Làm mới">↻</a>
        </div>
    </div>
</form>
