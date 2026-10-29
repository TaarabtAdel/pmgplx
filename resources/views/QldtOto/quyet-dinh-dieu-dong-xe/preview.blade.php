@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Xem trước — Quyết định điều động xe')

@section('content')
    @php
        $meta = $preview['meta'] ?? [];
    @endphp

    <div class="card card-panel mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Xem trước — Quyết định điều động xe (từng giáo viên)</span>
            <a href="{{ route('qldtoto.quyet-dinh-dieu-dong-xe.cancel') }}" class="btn btn-sm btn-outline-secondary">← Sửa lại</a>
        </div>
        <div class="card-body">
            <div><strong>Khoá PMGPLX:</strong> {{ $preview['ma_kh'] ?? '' }}</div>
            <div><strong>Tên khoá (phân công):</strong> {{ $preview['ten_khoa'] ?? '' }}</div>
            <div><strong>Ngày quyết định:</strong> {{ ! empty($preview['ngay_quyet_dinh']) ? \Carbon\Carbon::parse($preview['ngay_quyet_dinh'])->format('d/m/Y') : '' }}</div>
            <div><strong>Hạng / khóa:</strong> {{ $preview['hang_khoa'] ?? '' }}</div>
            <div class="mt-2 text-muted small">
                {{ number_format((int) ($meta['xe_count'] ?? 0)) }} xe,
                {{ number_format((int) ($meta['gv_count'] ?? 0)) }} dòng giáo viên.
                Mỗi xe một trang theo mẫu <code>quyet-dinh-dieu-dong-xe-gvth.docx</code>.
            </div>
            @if (session('error'))
                <div class="alert alert-danger mt-2 mb-0 py-2 small">{{ session('error') }}</div>
            @endif
            <div class="mt-3">
                <a href="{{ route('qldtoto.quyet-dinh-dieu-dong-xe.export') }}" class="btn btn-sm btn-navy">Xuất Word (tất cả xe)</a>
            </div>
        </div>
    </div>

    @foreach ($preview['xe_blocks'] ?? [] as $xi => $block)
        <div class="card card-panel mb-3">
            <div class="card-header py-2">
                <a href="{{ route('qldtoto.quyet-dinh-dieu-dong-xe.export', ['xe' => $xi]) }}"
                   class="btn btn-sm btn-outline-primary float-right">Xuất xe này</a>
                <strong>Xe:</strong> {{ \App\Support\QldtOto\QuyetDinhDieuDongXeSupport::bienSoHienThi((string) ($block['bien_so'] ?? '')) }}
                @if (! empty($block['so_quyet_dinh']))
                    <span class="ml-3"><strong>Số QĐ:</strong> {{ $block['so_quyet_dinh'] }}{{ \App\Support\QldtOto\QuyetDinhDieuDongXeSupport::HAU_TO_SO_QD }}</span>
                @endif
                <span class="ml-3 small text-muted">
                    {{ $block['loai_xe'] ?? '' }} · {{ $block['nhan_hieu'] ?? '' }} · {{ $block['mau_son'] ?? '' }}
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Giáo viên</th>
                                <th>Thời gian</th>
                                <th>Nội dung</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($block['giao_vien'] ?? [] as $gv)
                                <tr>
                                    <td>{{ $gv['ho_ten'] ?? '' }}</td>
                                    <td>
                                        @if (! empty($gv['tu_ngay']) || ! empty($gv['den_ngay']))
                                            {{ $gv['tu_ngay'] ?? '' }} – {{ $gv['den_ngay'] ?? '' }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $gv['noi_dung'] ?? '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endforeach

    <a href="{{ route('qldtoto.quyet-dinh-dieu-dong-xe.cancel') }}" class="btn btn-sm btn-outline-secondary">← Sửa lại</a>
@endsection
