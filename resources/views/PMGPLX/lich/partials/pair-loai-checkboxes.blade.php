@php
    /** @var list<string> $selected */
    $selected = $selected ?? [];
    if (is_string($selected)) {
        $selected = $selected !== '' ? [$selected] : [];
    }
    $loaiFilters = $loaiFilters ?? \App\Support\PMGPLX\LichXeLoaiGhiChu::allowedFilters();
    $inputName = $inputName ?? 'bo_qua_loai';
@endphp
<div class="pair-loai-wrap border rounded px-2 py-1 bg-light">
    <div class="small text-muted mb-1">Ẩn cả ngày theo tiêu chí (không chọn = hiện mọi ngày). Loại xe: có buổi khớp; Ngày nghỉ (lịch trống): từ NgayKG–NgayBG, cặp không có buổi xe (trước/sau khung khóa không ẩn theo mục này).</div>
    <div class="d-flex flex-wrap">
        @foreach ($loaiFilters as $loaiKey)
            <label class="custom-control custom-checkbox custom-control-inline mb-0 mr-2 small">
                <input type="checkbox"
                       class="custom-control-input"
                       name="{{ $inputName }}[]"
                       value="{{ $loaiKey }}"
                       @checked(in_array($loaiKey, $selected, true))>
                <span class="custom-control-label">{{ \App\Support\PMGPLX\LichXeLoaiGhiChu::filterLabel($loaiKey) }}</span>
            </label>
        @endforeach
    </div>
</div>
