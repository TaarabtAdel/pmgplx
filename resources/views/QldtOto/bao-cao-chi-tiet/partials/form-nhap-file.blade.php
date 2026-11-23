@php
    $batBuocDanhSach = $batBuocDanhSach ?? true;
    $laThiLai = filter_var(old('la_thi_lai', $laThiLai ?? false), FILTER_VALIDATE_BOOLEAN);
    $baoCaoGocId = old('bao_cao_goc_id', $baoCaoGocId ?? null);
    $danhSachKyGoc = $danhSachKyGoc ?? collect();
@endphp
<form method="POST" action="{{ route('qldtoto.bao-cao-chi-tiet.preview.store') }}" enctype="multipart/form-data">
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

    @if (empty($recordId) || ! empty($laThiLai))
        <div class="border rounded p-2 mb-3 bg-light" id="ktm-thi-lai-block">
            <div class="custom-control custom-checkbox mb-2">
                <input type="checkbox" class="custom-control-input" name="la_thi_lai" value="1" id="la_thi_lai"
                       @checked($laThiLai) @if(! empty($recordId) && $laThiLai) disabled @endif>
                @if (! empty($recordId) && $laThiLai)
                    <input type="hidden" name="la_thi_lai" value="1">
                @endif
                <label class="custom-control-label" for="la_thi_lai">
                    <strong>Báo cáo thi lại</strong>
                    <span class="text-muted font-weight-normal">— kế thừa kết quả kỳ gốc; môn chưa tải file không gán vắng (VLT, VH…)</span>
                </label>
            </div>
            <div class="form-group mb-0 pl-4 {{ $laThiLai ? '' : 'd-none' }}" id="ktm-thi-lai-goc-wrap">
                <label for="bao_cao_goc_id" class="small mb-1">Kỳ thi gốc (ngày + tên khóa đã ghép đủ)</label>
                <select name="bao_cao_goc_id" id="bao_cao_goc_id" class="form-control form-control-sm"
                        @if(! empty($recordId) && $laThiLai) disabled @endif>
                    <option value="">— Chọn kỳ thi gốc —</option>
                    @foreach ($danhSachKyGoc as $ky)
                        @if (! empty($recordId) && (int) $recordId === (int) $ky->id)
                            @continue
                        @endif
                        <option value="{{ $ky->id }}" @selected((int) $baoCaoGocId === (int) $ky->id)>
                            {{ $ky->NgayKyThi->format('d/m/Y') }} — {{ $ky->TenKhoaThi }}
                        </option>
                    @endforeach
                </select>
                @if (! empty($recordId) && $laThiLai && $baoCaoGocId)
                    <input type="hidden" name="bao_cao_goc_id" value="{{ $baoCaoGocId }}">
                @endif
                @error('bao_cao_goc_id')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
        </div>
        @if (empty($recordId))
            @push('scripts')
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        var chk = document.getElementById('la_thi_lai');
                        var wrap = document.getElementById('ktm-thi-lai-goc-wrap');
                        if (!chk || !wrap) return;
                        chk.addEventListener('change', function () {
                            wrap.classList.toggle('d-none', !chk.checked);
                        });
                    });
                </script>
            @endpush
        @endif
    @endif

    @if (! empty($laThiLai))
        <p class="small text-info mb-2">
            <strong>Báo cáo thi lại:</strong> file LT / MP / CC chỉ ghi đè một phần thí sinh (theo ghi chú từng môn). Các môn khác và người không khớp điều kiện giữ kết quả kỳ gốc.
            Kỳ <strong>thi lần 1</strong> (không đánh dấu thi lại) vẫn đọc file theo logic đầy đủ như trước.
        </p>
    @endif

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
                @if (! empty($laThiLai))
                    <small class="text-muted d-block">Thi lại, chỉ cập nhật những người có giá trị ở cột «Kết quả».</small>
                @endif
            </div>
            <div class="form-group">
                <label>3. Mô phỏng — Kết quả</label>
                <input type="file" name="file_mp" class="form-control-file form-control-sm" accept=".xls,.xlsx">
                @if (! empty($laThiLai))
                    <small class="text-muted d-block">Thi lại, chỉ cập nhật các người có kết quả «Đạt».</small>
                @endif
            </div>
            <div class="form-group mb-md-0">
                <label>4. CC (chữ chi) — Kết quả</label>
                <input type="file" name="file_cc" class="form-control-file form-control-sm" accept=".xls,.xlsx">
                @if (! empty($laThiLai))
                    <small class="text-muted d-block">Thi lại, chỉ cập nhật những người có điểm (cột «Điểm CC»).</small>
                @else
                    <small class="text-muted d-block">Cột «Điểm CC»: <strong>≥ 5</strong> → Đạt, &lt; 5 → Không đạt. Khớp CCCD hoặc Họ tên + Ngày sinh.</small>
                @endif
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>5. Hình — Đạt</label>
                <input type="file" name="file_h_dat" class="form-control-file form-control-sm" accept=".xls,.xlsx">
                <small class="text-muted d-block">Danh sách người <strong>Đạt</strong> hình. Có tên trong file → Đ; không có → giữ nguyên cột Hình.</small>
            </div>
            <div class="form-group">
                <label>6. Hình — Trượt</label>
                <input type="file" name="file_h_truot" class="form-control-file form-control-sm" accept=".xls,.xlsx">
                <small class="text-muted d-block">Danh sách người <strong>Không đạt</strong> hình. Có tên trong file → KĐ; không có → giữ nguyên.</small>
            </div>
            <div class="form-group">
                <label>7. Đường trường — Đạt</label>
                <input type="file" name="file_dt_dat" class="form-control-file form-control-sm" accept=".xls,.xlsx">
                <small class="text-muted d-block">File chỉ liệt kê người <strong>Đạt</strong> đường (không có cột điểm). Có tên trong file → Đ; không có → giữ nguyên cột Đường.</small>
            </div>
            <div class="form-group mb-0">
                <label>8. Đường trường — Trượt</label>
                <input type="file" name="file_dt_truot" class="form-control-file form-control-sm" accept=".xls,.xlsx">
                <small class="text-muted d-block">Danh sách người <strong>Không đạt</strong> đường. Có tên trong file → KĐ; không có → giữ nguyên.</small>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-navy btn-sm mt-3">{{ $submitLabel ?? 'Ghép & xem trước' }}</button>
    <p class="small text-muted mt-2 mb-0">Chưa ghi DB — sau khi ghép sẽ hiện 5 dòng mẫu; đúng thì «Xác nhận lưu».</p>
</form>