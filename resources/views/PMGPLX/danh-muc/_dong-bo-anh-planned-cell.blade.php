@php
    $anhUrl = $anhUrl ?? null;
    $duongDanAnh = trim((string) ($duongDanAnh ?? ''));
    $hoTen = $hoTen ?? '';
    $maDk = $maDk ?? '';
    $anhUrlCu = $anhUrlCu ?? null;
    $duongDanAnhCu = trim((string) ($duongDanAnhCu ?? ''));
    $khacPath = ! empty($khacPath);
@endphp
<div class="dong-bo-anh-planned-cell">
    @include('PMGPLX.danh-muc._dong-bo-anh-thumb', [
        'anhUrl' => $anhUrl,
        'duongDanAnh' => $duongDanAnh,
        'hoTen' => $hoTen,
        'maDk' => $maDk,
    ])
    @if ($khacPath)
        <div class="dong-bo-anh-ban-cu-stack" title="Đường dẫn đang lưu trên bản cũ — sẽ đổi khi đồng bộ">
            <span class="badge badge-warning dong-bo-anh-ban-cu-label">cũ</span>
            @include('PMGPLX.danh-muc._dong-bo-anh-thumb', [
                'anhUrl' => $anhUrlCu,
                'duongDanAnh' => $duongDanAnhCu,
                'hoTen' => $hoTen,
                'maDk' => $maDk,
            ])
        </div>
    @endif
</div>