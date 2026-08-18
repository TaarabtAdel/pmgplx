@extends('PMGPLX.layouts.quan-ly')

@section('title', 'In bảng tên học viên')

@push('styles')
    @include('DaoTao.cong-cu-nhap.partials.bang-ten-cards-styles')
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
        .bang-ten-preview-area {
            overflow-x: auto;
            padding-bottom: 1rem;
        }
        @media (max-width: 768px) {
            .bang-ten-header-line {
                white-space: normal;
            }
            .bang-ten-grid {
                grid-template-columns: 1fr;
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
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>In bảng tên học viên</span>
            <a href="{{ route('daotao.pdt.cong-cu-nhap.nhap-file-in-bang-ten.cancel') }}" class="btn btn-sm btn-outline-secondary">← Chọn file khác</a>
        </div>
        <div class="card-body">
            <div class="bang-ten-toolbar">
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
                    <a href="{{ route('daotao.pdt.cong-cu-nhap.nhap-file-in-bang-ten.print') }}"
                       class="btn btn-navy btn-lg"
                       target="_blank"
                       rel="noopener">In bảng tên</a>
                </div>
            </div>

            <div class="bang-ten-preview-area">
                @include('DaoTao.cong-cu-nhap.partials.bang-ten-cards', ['preview' => $preview])
            </div>
        </div>
    </div>
@endsection
