@extends('PMGPLX.layouts.quan-ly')

@section('title', 'In bảng tên học viên')

@push('styles')
<style>
    .bang-ten-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    .bang-ten-meta {
        color: #444;
        line-height: 1.6;
    }
    .bang-ten-meta strong {
        color: #1f4e79;
    }
    .bang-ten-spec-note {
        font-size: 0.85rem;
        color: #555;
        margin-bottom: 1rem;
        line-height: 1.5;
    }
    /*
     * Quy cách: 50 mm × 85 mm = cao 50 mm, ngang 85 mm (thẻ nằm ngang).
     * Trước đó nhầm thành 50 ngang × 85 cao (dọc) nên chữ bị xuống dòng từng từ.
     */
    .bang-ten-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, 85mm);
        gap: 5mm;
        justify-content: start;
    }
    .bang-ten-card {
        width: 85mm;
        height: 50mm;
        box-sizing: border-box;
        border: 1px solid #000;
        background: #fff;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        break-inside: avoid;
        page-break-inside: avoid;
        font-family: "Times New Roman", Times, serif;
        font-style: normal;
    }
    .bang-ten-header {
        flex-shrink: 0;
        height: 10mm;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 0 2mm;
        text-align: center;
        line-height: 1.12;
        border-bottom: 1px solid #000;
        box-sizing: border-box;
    }
    .bang-ten-header-line {
        font-size: 10pt;
        font-weight: 400;
        font-style: normal;
        text-transform: uppercase;
        color: #000;
        white-space: nowrap;
    }
    .bang-ten-body {
        flex-shrink: 0;
        height: 40mm;
        display: flex;
        flex-direction: row;
        align-items: stretch;
    }
    /* Ảnh 3 cm × 4 cm */
    .bang-ten-photo-wrap {
        width: 30mm;
        height: 40mm;
        flex-shrink: 0;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border-right: 1px solid #000;
        box-sizing: border-box;
    }
    .bang-ten-photo-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .bang-ten-photo-placeholder {
        font-size: 7pt;
        color: #666;
        text-align: center;
        padding: 1mm;
        line-height: 1.2;
    }
    .bang-ten-info {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 1mm 2mm;
        gap: 1mm;
    }
    .bang-ten-title {
        font-size: 13pt;
        font-weight: 700;
        font-style: normal;
        text-transform: uppercase;
        color: #000;
        line-height: 1.1;
    }
    .bang-ten-name {
        font-size: 14pt;
        font-weight: 700;
        font-style: normal;
        text-transform: uppercase;
        color: #000;
        line-height: 1.1;
        word-break: break-word;
    }
    .bang-ten-hang {
        font-size: 14pt;
        font-weight: 700;
        font-style: normal;
        text-transform: uppercase;
        color: #000;
        line-height: 1.1;
    }
    @media screen {
        .bang-ten-print-area {
            overflow-x: auto;
            padding-bottom: 1rem;
        }
        .bang-ten-scale-wrap {
            transform: scale(1.45);
            transform-origin: top left;
            width: calc(100% / 1.45);
        }
    }
    @media (max-width: 768px) {
        .bang-ten-scale-wrap {
            transform: scale(1.1);
            width: calc(100% / 1.1);
        }
        .bang-ten-header-line {
            white-space: normal;
        }
    }
    @media print {
        @page {
            size: A4;
            margin: 8mm;
        }
        body * {
            visibility: hidden;
        }
        .bang-ten-print-area,
        .bang-ten-print-area * {
            visibility: visible;
        }
        .bang-ten-print-area {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        .bang-ten-scale-wrap {
            transform: none;
            width: 100%;
        }
        .no-print {
            display: none !important;
        }
        .page-wrap {
            padding: 0 !important;
        }
        .card-panel {
            border: none !important;
            box-shadow: none !important;
        }
        .bang-ten-grid {
            grid-template-columns: repeat(2, 85mm);
            gap: 4mm;
            justify-content: flex-start;
        }
        .bang-ten-card {
            width: 85mm;
            height: 50mm;
        }
    }
</style>
@endpush

@section('content')
    @php
        $khoa = $preview['khoa_hoc'] ?? [];
        $meta = $preview['meta'] ?? [];
    @endphp

    <div class="card card-panel">
        <div class="card-header d-flex justify-content-between align-items-center no-print">
            <span>In bảng tên học viên</span>
            <a href="{{ route('daotao.pdt.cong-cu-nhap.nhap-file-in-bang-ten.cancel') }}" class="btn btn-sm btn-outline-secondary">← Chọn file khác</a>
        </div>
        <div class="card-body">
            <div class="bang-ten-toolbar no-print">
                <div class="bang-ten-meta">
                    <div><strong>File:</strong> {{ $preview['file_name'] ?? '' }}</div>
                    <div>
                        <strong>Khoá:</strong> {{ $khoa['ten_khoa_hoc'] ?? '—' }}
                        ({{ $khoa['ma_khoa_hoc'] ?? '—' }})
                        · <strong>Hạng:</strong> {{ $khoa['hang_gplx'] ?? '—' }}
                    </div>
                    <div><strong>{{ number_format((int) ($meta['hoc_vien_count'] ?? 0)) }}</strong> học viên</div>
                </div>
                <div>
                    <button type="button" class="btn btn-navy btn-lg" onclick="window.print()">In bảng tên</button>
                </div>
            </div>

            <div class="bang-ten-spec-note no-print">
                Quy cách in: thẻ <strong>85 mm (ngang) × 50 mm (cao)</strong>, giấy trắng chất lượng tốt, ép plastic kẹp mica.
                Ảnh chân dung <strong>3 cm × 4 cm</strong> (đóng dấu giáp lai tại cơ sở đào tạo).
            </div>

            <div class="bang-ten-print-area">
                <div class="bang-ten-scale-wrap">
                    <div class="bang-ten-grid">
                        @foreach ($preview['hoc_vien'] as $hv)
                            <div class="bang-ten-card">
                                <div class="bang-ten-header">
                                    <div class="bang-ten-header-line">Sở Giáo dục &amp; Đào tạo Quảng Trị</div>
                                    <div class="bang-ten-header-line">Trung tâm GDNN Mạnh Linh</div>
                                </div>
                                <div class="bang-ten-body">
                                    <div class="bang-ten-photo-wrap">
                                        @if (! empty($hv['anh_src']))
                                            <img src="{{ $hv['anh_src'] }}" alt="{{ $hv['ho_ten'] }}"
                                                 onerror="this.replaceWith(Object.assign(document.createElement('span'),{className:'bang-ten-photo-placeholder',textContent:'Không có ảnh'}))">
                                        @else
                                            <span class="bang-ten-photo-placeholder">Không có ảnh</span>
                                        @endif
                                    </div>
                                    <div class="bang-ten-info">
                                        <div class="bang-ten-title">Học viên tập lái xe</div>
                                        <div class="bang-ten-name">{{ $hv['ho_ten'] ?? '—' }}</div>
                                        <div class="bang-ten-hang">{{ $hv['hang_gplx'] ?? '—' }}</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
