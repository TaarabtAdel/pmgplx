@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Xem trước — Áp dụng GV dạy thay vào lịch PMGPLX')

@section('content')
    @php
        $meta = $preview['meta'] ?? [];
        $maKh = $preview['ma_kh'] ?? '';
    @endphp

    <div class="card card-panel mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <span>Áp dụng GV dạy thay → lịch PMGPLX</span>
            <a href="{{ route('daotao.pdt.dat.giao-vien-day-thay', ['ma_khoa_hoc' => $preview['ma_khoa_hoc'] ?? '']) }}"
               class="btn btn-sm btn-outline-secondary mt-1 mt-md-0">
                ← GV dạy thay
            </a>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success small py-2">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger small py-2">{{ session('error') }}</div>
            @endif

            <p class="small text-muted mb-3">
                Khóa DAT / PMGPLX: <code>{{ $preview['ma_khoa_hoc'] ?? '' }}</code>
                (<code>MaKH</code> = mã khóa trên phân công).
                Chỉ đổi <strong>MaGV / TenGV</strong> trên lịch TH (<code>LoaiGV=TH</code>, lịch xe <code>IsKhoaHocXeTap=0</code>)
                khi buổi trùng khoảng ngày dạy thay và GV hiện tại vẫn là GV gốc.
            </p>

            <div class="mb-3">
                <strong>Khai báo dạy thay:</strong> {{ number_format((int) ($meta['khai_bao_count'] ?? 0)) }}
                · <strong>Sẽ sửa lịch GV:</strong> {{ number_format((int) ($meta['gv_count'] ?? 0)) }}
                · <strong>Sẽ sửa lịch xe:</strong> {{ number_format((int) ($meta['xe_count'] ?? 0)) }}
            </div>

            @if (! empty($preview['khai_bao']))
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered bg-white">
                        <thead class="thead-light">
                            <tr>
                                <th>GV gốc → GV thay</th>
                                <th>Từ ngày</th>
                                <th>Đến ngày</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($preview['khai_bao'] as $kb)
                                <tr>
                                    <td><code>{{ $kb['ma_giao_vien_goc'] }}</code> → <code>{{ $kb['ma_giao_vien'] }}</code></td>
                                    <td>{{ $kb['tu_ngay'] }}</td>
                                    <td>{{ $kb['den_ngay'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <h6 class="font-weight-bold">Lịch giáo viên (<code>KhoaHoc_GiaoVien</code>)</h6>
            <div class="table-responsive mb-3">
                <table class="table table-sm table-bordered table-hover bg-white">
                    <thead class="thead-light">
                        <tr>
                            <th>MaLichLV</th>
                            <th>Bắt đầu</th>
                            <th>Kết thúc</th>
                            <th>Xe</th>
                            <th>GV hiện tại</th>
                            <th>→ GV mới</th>
                            <th>Khoảng TH</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($preview['gv_lich'] ?? [] as $row)
                            <tr>
                                <td>{{ $row['ma_lich'] }}</td>
                                <td class="text-nowrap">{{ $row['ngay_bd'] }}</td>
                                <td class="text-nowrap">{{ $row['ngay_kt'] }}</td>
                                <td><code>{{ $row['bien_so_xe'] ?: '—' }}</code></td>
                                <td><code>{{ $row['ma_gv_cu'] }}</code> {{ $row['ten_gv_cu'] }}</td>
                                <td class="table-warning"><code>{{ $row['ma_gv_moi'] }}</code> {{ $row['ten_gv_moi'] }}</td>
                                <td class="text-nowrap small">{{ $row['tu_ngay_thay'] }} — {{ $row['den_ngay_thay'] ?? '∞' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-muted text-center py-3">Không có dòng lịch GV cần đổi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <h6 class="font-weight-bold">Lịch xe tập (<code>KhoaHoc_XeTap</code>)</h6>
            <div class="table-responsive mb-3">
                <table class="table table-sm table-bordered table-hover bg-white">
                    <thead class="thead-light">
                        <tr>
                            <th>MaLichSD</th>
                            <th>Bắt đầu</th>
                            <th>Kết thúc</th>
                            <th>Biển số</th>
                            <th>GV hiện tại</th>
                            <th>→ GV mới</th>
                            <th>Khoảng TH</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($preview['xe_lich'] ?? [] as $row)
                            <tr>
                                <td>{{ $row['ma_lich'] }}</td>
                                <td class="text-nowrap">{{ $row['ngay_bd'] }}</td>
                                <td class="text-nowrap">{{ $row['ngay_kt'] }}</td>
                                <td><code>{{ $row['bien_so_xe'] ?: '—' }}</code></td>
                                <td><code>{{ $row['ma_gv_cu'] }}</code> {{ $row['ten_gv_cu'] }}</td>
                                <td class="table-warning"><code>{{ $row['ma_gv_moi'] }}</code> {{ $row['ten_gv_moi'] }}</td>
                                <td class="text-nowrap small">{{ $row['tu_ngay_thay'] }} — {{ $row['den_ngay_thay'] ?? '∞' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-muted text-center py-3">Không có dòng lịch xe cần đổi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex flex-wrap align-items-center">
                @if ((int) ($meta['gv_count'] ?? 0) + (int) ($meta['xe_count'] ?? 0) > 0)
                    <form method="POST" action="{{ route('daotao.pdt.dat.giao-vien-day-thay.apply-lich') }}" class="mr-2 mb-2"
                          onsubmit="return confirm('Ghi thay đổi vào lịch PMGPLX (GPLX_BAN_MOI)?');">
                        @csrf
                        <input type="hidden" name="ma_khoa_hoc" value="{{ $preview['ma_khoa_hoc'] ?? '' }}">
                        <button type="submit" class="btn btn-navy">Xác nhận áp dụng vào lịch</button>
                    </form>
                @else
                    <p class="text-muted small mb-2 mr-3">Không có dòng nào để cập nhật (có thể đã áp dụng hoặc không trùng ngày).</p>
                @endif
                @if ($maKh !== '')
                    <a href="{{ route('pmgplx.lich.gv.index', ['ma_kh' => $maKh]) }}" class="btn btn-outline-primary btn-sm mb-2 mr-2" target="_blank" rel="noopener">
                        Mở lịch GV PMGPLX
                    </a>
                    <a href="{{ route('pmgplx.lich.xe.index', ['ma_kh' => $maKh]) }}" class="btn btn-outline-primary btn-sm mb-2" target="_blank" rel="noopener">
                        Mở lịch xe PMGPLX
                    </a>
                @endif
            </div>
        </div>
    </div>
@endsection
