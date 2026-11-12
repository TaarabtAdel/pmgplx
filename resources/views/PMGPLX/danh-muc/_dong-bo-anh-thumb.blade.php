@php
    $anhUrl = $anhUrl ?? null;
    $duongDanAnh = trim((string) ($duongDanAnh ?? ''));
    $hoTen = $hoTen ?? '';
    $maDk = $maDk ?? '';
@endphp
@if ($anhUrl)
    <button type="button"
            class="dong-bo-anh-thumb-btn p-0 border-0 bg-transparent"
            title="{{ $duongDanAnh !== '' ? $duongDanAnh : 'Xem ảnh' }}"
            data-anh-url="{{ $anhUrl }}"
            data-anh-title="{{ $hoTen }}"
            data-anh-ma-dk="{{ $maDk }}"
            data-anh-path="{{ $duongDanAnh }}"
            onclick="dongBoMoAnhLon(this)">
        <img src="{{ $anhUrl }}" alt="" height="18" class="dong-bo-anh-thumb">
    </button>
@else
    <span class="text-muted small">—</span>
@endif
