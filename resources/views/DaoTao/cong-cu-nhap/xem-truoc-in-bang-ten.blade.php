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
        .bang-ten-spec-note {
            font-size: 0.85rem;
            color: #555;
            margin-bottom: 1rem;
            line-height: 1.5;
        }
        .bang-ten-calibrate {
            margin-bottom: 1rem;
            font-size: 0.8rem;
            color: #666;
        }
        .bang-ten-calibrate-bar {
            width: var(--bt-card-w);
            height: var(--bt-card-h);
            border: 1px dashed #c0392b;
            box-sizing: border-box;
            margin-bottom: 0.35rem;
            position: relative;
        }
        .bang-ten-calibrate-bar::before {
            content: "85 mm × 50 mm";
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            font-size: 10pt;
            color: #c0392b;
            white-space: nowrap;
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

            <div class="bang-ten-spec-note">
                Xem trước và bản in dùng <strong>cùng CSS</strong> (<code>85 mm × 50 mm</code>).
                Khung đỏ bên dưới đúng kích thước thẻ — đối chiếu thước kẹp giấy trên màn hình.
                Khi in: hộp thoại chọn <strong>Tỷ lệ 100%</strong>, không «Vừa trang».
            </div>

            <div class="bang-ten-calibrate bang-ten-spec">
                <div class="bang-ten-calibrate-bar"></div>
                Khung mẫu 85 × 50 mm (cùng kích thước mỗi thẻ bên dưới)
            </div>

            <div class="bang-ten-preview-area">
                @include('DaoTao.cong-cu-nhap.partials.bang-ten-cards', ['preview' => $preview])
            </div>
        </div>
    </div>
@endsection
