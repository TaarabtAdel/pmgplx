<div class="border rounded p-3 bg-white mb-2 dat-filter-section">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <strong class="small mb-2 mb-md-0">Lọc theo thông tin phiên</strong>
        <div class="d-flex flex-wrap">
            <button type="submit" class="btn btn-sm btn-navy mr-2 mb-2 mb-md-0">Lọc</button>
            <a href="{{ $resetRoute }}"
               class="btn btn-sm btn-outline-secondary mb-2 mb-md-0">
                Reset
            </a>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-md-3">
            <label class="small text-muted mb-1" for="filter_ma_khoa_hoc">Mã khóa học</label>
            <select name="ma_khoa_hoc" id="filter_ma_khoa_hoc" class="form-control form-control-sm"
                    @if ($requireKhoaHoc ?? false) required @endif>
                <option value="">
                    @if ($requireKhoaHoc ?? false)
                        — Chọn khóa —
                    @else
                        — Tất cả —
                    @endif
                </option>
                @foreach ($khoaHocOptions as $kh)
                    <option value="{{ $kh->MaKhoaHoc }}"
                            @selected(($filters['ma_khoa_hoc'] ?? '') === $kh->MaKhoaHoc)>
                        @if (! empty($kh->TenKhoaHoc))
                            {{ $kh->TenKhoaHoc }} ({{ $kh->MaKhoaHoc }})
                        @else
                            {{ $kh->MaKhoaHoc }}
                        @endif
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group col-md-3">
            <label class="small text-muted mb-1" for="filter_ma_phien">Mã phiên</label>
            <input type="text" name="ma_phien" id="filter_ma_phien"
                   class="form-control form-control-sm"
                   value="{{ $filters['ma_phien'] ?? '' }}"
                   placeholder="Tìm mã phiên...">
        </div>
        <div class="form-group col-md-3">
            <label class="small text-muted mb-1" for="filter_ma_hoc_vien">Mã học viên</label>
            <select name="ma_hoc_vien" id="filter_ma_hoc_vien" class="form-control form-control-sm">
                <option value="">— Tất cả —</option>
                @if ($selectedHocVienOption)
                    <option value="{{ $selectedHocVienOption['id'] }}" selected>
                        {{ $selectedHocVienOption['text'] }}
                    </option>
                @endif
            </select>
        </div>
        <div class="form-group col-md-3">
            <label class="small text-muted mb-1" for="filter_ma_giao_vien">Mã giáo viên</label>
            <select name="ma_giao_vien" id="filter_ma_giao_vien" class="form-control form-control-sm">
                <option value="">— Tất cả —</option>
                @foreach ($giaoVienOptions as $gv)
                    <option value="{{ $gv->MaGiaoVien }}"
                            @selected(($filters['ma_giao_vien'] ?? '') === $gv->MaGiaoVien)>
                        @if (! empty($gv->HoTenGiaoVien))
                            {{ $gv->HoTenGiaoVien }} ({{ $gv->MaGiaoVien }})
                        @else
                            {{ $gv->MaGiaoVien }}
                        @endif
                    </option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-md-3">
            <label class="small text-muted mb-1" for="filter_ngay">Chọn ngày</label>
            <input type="date" name="ngay" id="filter_ngay" class="form-control form-control-sm"
                   value="{{ $filters['ngay'] ?? '' }}">
        </div>
        <div class="form-group col-md-3">
            <label class="small text-muted mb-1" for="filter_loai_khoa_hoc">Loại khóa học</label>
            <select name="loai_khoa_hoc" id="filter_loai_khoa_hoc" class="form-control form-control-sm">
                <option value="">— Tất cả —</option>
                @foreach ($loaiKhoaHocOptions as $loai)
                    <option value="{{ $loai }}"
                            @selected(($filters['loai_khoa_hoc'] ?? '') === $loai)>
                        {{ $loai }}
                    </option>
                @endforeach
            </select>
        </div>
        @unless ($hideDatFilter ?? false)
            <div class="form-group col-md-3">
                <label class="small text-muted mb-1">{{ $datLabel ?? 'Đạt' }}</label>
                <select name="dat" class="form-control form-control-sm" @disabled(! ($canApplyDatFilter ?? true))>
                    <option value="" @selected(($filters['dat'] ?? '') === '')>— Tất cả —</option>
                    <option value="dat" @selected(($filters['dat'] ?? '') === 'dat')>Đạt</option>
                    <option value="chua_dat" @selected(($filters['dat'] ?? '') === 'chua_dat')>Không đạt</option>
                </select>
                @if (! empty($datHelp))
                    <small class="text-muted d-block mt-1">{{ $datHelp }}</small>
                @endif
            </div>
        @endunless
    </div>
</div>
