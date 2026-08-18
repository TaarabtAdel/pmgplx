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
    .bang-ten-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }
    .bang-ten-card {
        border: 2px solid #000;
        background: #fff;
        break-inside: avoid;
        page-break-inside: avoid;
        font-family: "Times New Roman", Times, serif;
    }
    .bang-ten-header {
        border-bottom: 2px solid #000;
        padding: 6px 8px;
        text-align: center;
        line-height: 1.25;
    }
    .bang-ten-header-line {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #000;
    }
    .bang-ten-body {
        display: flex;
        min-height: 118px;
    }
    .bang-ten-photo-wrap {
        flex: 0 0 34%;
        border-right: 2px solid #000;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        min-height: 118px;
    }
    .bang-ten-photo-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .bang-ten-photo-placeholder {
        font-size: 0.7rem;
        color: #666;
        padding: 0.5rem;
        text-align: center;
    }
    .bang-ten-info {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 8px 10px;
        gap: 8px;
    }
    .bang-ten-title {
        font-size: 0.95rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #000;
        line-height: 1.2;
    }
    .bang-ten-name {
        font-size: 0.9rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #000;
        line-height: 1.25;
        word-break: break-word;
    }
    .bang-ten-hang {
        font-size: 0.88rem;
        font-weight: 700;
        color: #000;
        line-height: 1.2;
    }
    @media (max-width: 992px) {
        .bang-ten-grid {
            grid-template-columns: 1fr;
        }
    }
    @media print {
        @page {
            size: A4;
            margin: 10mm;
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
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        .bang-ten-card {
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .bang-ten-header-line {
            font-size: 9pt;
        }
        .bang-ten-title {
            font-size: 11pt;
        }
        .bang-ten-name,
        .bang-ten-hang {
            font-size: 10pt;
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

            <div class="bang-ten-print-area">
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
                                    <div class="bang-ten-hang">Tập lái xe hạng: {{ $hv['hang_gplx'] ?? '—' }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
