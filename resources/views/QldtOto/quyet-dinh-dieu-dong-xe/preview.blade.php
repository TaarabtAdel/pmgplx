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
            @if (! empty($trungLichXe))
                <div class="alert alert-warning mt-2 mb-0 py-2 small">
                    <strong>Cảnh báo:</strong> {{ count($trungLichXe) }} xe trùng thời gian với lịch xe tập của khoá khác
                    (<a href="{{ route('pmgplx.lich.xe.index') }}" target="_blank">Lịch xe tập PMGPLX</a>). Xem chi tiết ở từng xe bên dưới.
                </div>
            @else
                <div class="alert alert-success mt-2 mb-0 py-2 small">
                    <strong>Hợp lệ:</strong> không có xe nào trùng thời gian với lịch xe tập của khoá khác
                    (<a href="{{ route('pmgplx.lich.xe.index') }}" target="_blank">Lịch xe tập PMGPLX</a>).
                </div>
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
                @if (! empty($trungLichXe[$xi]))
                    <div class="alert alert-warning rounded-0 border-left-0 border-right-0 border-top-0 mb-0 py-2 small">
                        <strong>Trùng lịch xe tập với khoá khác:</strong>
                        <ul class="mb-0 pl-3">
                            @foreach ($trungLichXe[$xi] as $tr)
                                <li>
                                    {{ $tr['ten_kh'] !== '' ? $tr['ten_kh'].' ('.$tr['ma_kh'].')' : $tr['ma_kh'] }}:
                                    {{ $tr['tu'] }} – {{ $tr['den'] }}, {{ $tr['so_buoi'] }} buổi
                                    @if ($tr['giao_vien'] !== '')
                                        — GV: {{ $tr['giao_vien'] }}
                                    @endif
                                    <a href="{{ route('pmgplx.lich.xe.index', ['ma_kh' => $tr['ma_kh'], 'bien_so_xe' => $block['bien_so'] ?? '']) }}" target="_blank">xem lịch</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    @php
                        $coThoiGian = collect($block['giao_vien'] ?? [])
                            ->contains(fn ($gv) => trim((string) ($gv['tu_ngay'] ?? '')) !== '' || trim((string) ($gv['den_ngay'] ?? '')) !== '');
                    @endphp
                    @if ($coThoiGian)
                        <div class="alert alert-success rounded-0 border-left-0 border-right-0 border-top-0 mb-0 py-2 small">
                            Hợp lệ — xe không trùng lịch xe tập với khoá khác trong thời gian này.
                        </div>
                    @else
                        <div class="alert alert-secondary rounded-0 border-left-0 border-right-0 border-top-0 mb-0 py-2 small">
                            Chưa kiểm tra trùng lịch — xe chưa có thời gian (từ ngày / đến ngày).
                        </div>
                    @endif
                @endif
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0" style="table-layout: fixed; width: 100%;">
                        <colgroup>
                            <col style="width: 40%;">
                            <col style="width: 35%;">
                            <col style="width: 25%;">
                        </colgroup>
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
                                        @php
                                            $fmtNgay = function ($v) {
                                                $v = trim((string) $v);
                                                if ($v === '') {
                                                    return '';
                                                }
                                                try {
                                                    return \Carbon\Carbon::parse($v)->format('d/m/Y');
                                                } catch (\Throwable) {
                                                    return $v;
                                                }
                                            };
                                            $tu = $fmtNgay($gv['tu_ngay'] ?? '');
                                            $den = $fmtNgay($gv['den_ngay'] ?? '');
                                        @endphp
                                        @if ($tu !== '' || $den !== '')
                                            {{ $tu }} – {{ $den }}
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
