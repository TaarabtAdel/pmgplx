@php
    $batBuocDanhSach = $batBuocDanhSach ?? true;
@endphp
<form method="POST" action="{{ route('qldtoto.bao-cao-chi-tiet.store') }}" enctype="multipart/form-data">
    @csrf
    @if (! empty($recordId))
        <input type="hidden" name="record_id" value="{{ $recordId }}">
    @endif
    <div class="form-row">
        <div class="form-group col-md-3">
            <label for="ngay_ky_thi">Ngày kỳ thi <span class="text-danger">*</span></label>
            <input type="date" name="ngay_ky_thi" id="ngay_ky_thi" class="form-control form-control-sm"
                   value="{{ old('ngay_ky_thi', $ngayKyThi ?? '') }}" required>
            @error('ngay_ky_thi')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="form-group col-md-4">
            <label for="ten_khoa_thi">Tên khóa <span class="text-danger">*</span></label>
            <input type="text" name="ten_khoa_thi" id="ten_khoa_thi" class="form-control form-control-sm"
                   value="{{ old('ten_khoa_thi', $tenKhoaThi ?? '') }}" required maxlength="255"
                   placeholder="VD: KH-OTO-B2-18.11.2025">
            @error('ten_khoa_thi')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="form-group col-md-5">
            <label for="dia_diem_sat_hach">Địa điểm sát hạch (tùy chọn)</label>
            <input type="text" name="dia_diem_sat_hach" id="dia_diem_sat_hach" class="form-control form-control-sm"
                   value="{{ old('dia_diem_sat_hach', $diaDiem ?? '') }}" maxlength="255">
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label>1. Danh sách thí sinh @if ($batBuocDanhSach)<span class="text-danger">*</span>@else<span class="text-muted small">(đã lưu — chỉ tải lại nếu đổi DS)</span>@endif</label>
                <input type="file" name="file_danh_sach" class="form-control-file form-control-sm" accept=".xls,.xlsx" @if ($batBuocDanhSach) required @endif>
                @error('file_danh_sach')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label>2. Lý thuyết — Kết quả</label>
                <input type="file" name="file_lt" class="form-control-file form-control-sm" accept=".xls,.xlsx">
            </div>
            <div class="form-group">
                <label>3. Mô phỏng — Kết quả</label>
                <input type="file" name="file_mp" class="form-control-file form-control-sm" accept=".xls,.xlsx">
            </div>
            <div class="form-group mb-md-0">
                <label>4. Cabin — Kết quả (khớp CCCD)</label>
                <input type="file" name="file_cc" class="form-control-file form-control-sm" accept=".xls,.xlsx">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>5. Hình — Đạt</label>
                <input type="file" name="file_h_dat" class="form-control-file form-control-sm" accept=".xls,.xlsx">
            </div>
            <div class="form-group">
                <label>6. Hình — Trượt</label>
                <input type="file" name="file_h_truot" class="form-control-file form-control-sm" accept=".xls,.xlsx">
            </div>
            <div class="form-group">
                <label>7. Đường trường — Đạt</label>
                <input type="file" name="file_dt_dat" class="form-control-file form-control-sm" accept=".xls,.xlsx">
            </div>
            <div class="form-group mb-0">
                <label>8. Đường trường — Trượt</label>
                <input type="file" name="file_dt_truot" class="form-control-file form-control-sm" accept=".xls,.xlsx">
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-navy btn-sm mt-3">{{ $submitLabel ?? 'Ghép dữ liệu & lưu' }}</button>
</form>