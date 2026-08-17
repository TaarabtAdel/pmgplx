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
        grid-template-columns: repeat(3, 1fr);
        gap: 12px;
    }
    .bang-ten-card {
        border: 2px solid #222;
        border-radius: 4px;
        background: #fff;
        padding: 8px 8px 10px;
        text-align: center;
        min-height: 175px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        break-inside: avoid;
        page-break-inside: avoid;
    }
    .bang-ten-photo-wrap {
        width: 88px;
        height: 110px;
        border: 1px solid #999;
        background: #f5f5f5;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        margin-bottom: 6px;
    }
    .bang-ten-photo-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .bang-ten-photo-placeholder {
        font-size: 0.75rem;
        color: #888;
        padding: 0.25rem;
    }
    .bang-ten-hang {
        font-size: 1.65rem;
        font-weight: 800;
        color: #c0392b;
        line-height: 1.1;
        margin-bottom: 4px;
    }
    .bang-ten-name {
        font-size: 0.82rem;
        font-weight: 700;
        text-transform: uppercase;
        line-height: 1.25;
        color: #111;
        word-break: break-word;
    }
    @media (max-width: 992px) {
        .bang-ten-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (max-width: 576px) {
        .bang-ten-grid {
            grid-template-columns: 1fr;
        }
    }
    @media print {
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
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }
        .bang-ten-card {
            min-height: 165px;
            padding: 6px;
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
                            <div class="bang-ten-photo-wrap">
                                @if (! empty($hv['anh_src']))
                                    <img src="{{ $hv['anh_src'] }}" alt="{{ $hv['ho_ten'] }}"
                                         onerror="this.replaceWith(Object.assign(document.createElement('span'),{className:'bang-ten-photo-placeholder',textContent:'Không hiển thị được ảnh'}))">
                                @else
                                    <span class="bang-ten-photo-placeholder">Không có ảnh</span>
                                @endif
                            </div>
                            <div class="bang-ten-hang">{{ $hv['hang_gplx'] ?? '—' }}</div>
                            <div class="bang-ten-name">{{ $hv['ho_ten'] ?? '—' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
