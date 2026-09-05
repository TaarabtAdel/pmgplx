@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Xem trước — Nhập phiên DAT')

@section('content')
    @php
        $meta = $preview['meta'] ?? [];
    @endphp

    <div class="card card-panel mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Xem trước — Nhập dữ liệu phiên DAT</span>
            <a href="{{ route('daotao.pdt.dat.nhap-du-lieu-phien.cancel') }}" class="btn btn-sm btn-outline-secondary">← Chọn file khác</a>
        </div>
        <div class="card-body">
            <div><strong>File:</strong> {{ $preview['file_name'] ?? '' }}</div>
            <div><strong>Sheet:</strong> {{ $preview['sheet_name'] ?? '' }}</div>
            <div>
                <strong>Tổng:</strong>
                {{ number_format((int) ($meta['record_count'] ?? 0)) }} phiên sẽ lưu
                @if ((int) ($meta['skipped_count'] ?? 0) > 0)
                    — bỏ qua {{ (int) $meta['skipped_count'] }} dòng thiếu mã phiên học
                @endif
            </div>
            @if (!empty($meta['date_range']))
                <div><strong>Khoảng thời gian file:</strong> {{ $meta['date_range'] }}</div>
            @endif
            @if ($detailTotal > $detailLimit)
                <div class="text-muted small mt-1">
                    Chỉ hiển thị {{ number_format($detailLimit) }} dòng đầu để xem trước
                    (tổng {{ number_format($detailTotal) }} phiên sẽ lưu khi xác nhận).
                </div>
            @endif
        </div>
    </div>

    <div class="card card-panel mb-3">
        <div class="card-header">Chi tiết — {{ count($detailRows) }} dòng mẫu (tối đa {{ $detailLimit }})</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Mã phiên học</th>
                            <th>Tỉ lệ ND</th>
                            <th>Bắt đầu</th>
                            <th>Kết thúc</th>
                            <th>TH (giờ)</th>
                            <th>QD (km)</th>
                            <th>HV</th>
                            <th>Khóa học</th>
                            <th>GV</th>
                            <th>Xe</th>
                            <th>Thiết bị</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($detailRows as $i => $row)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td><code>{{ $row['MaPhienHoc'] ?? '' }}</code></td>
                                <td>{{ $row['TiLeNhanDien'] ?? '—' }}</td>
                                <td>{{ $row['ThoiGianBatDauPhienHoc'] ?? '—' }}</td>
                                <td>{{ $row['ThoiGianKetThucPhienHoc'] ?? '—' }}</td>
                                <td>{{ $row['ThoiGianThucHanhGio'] ?? '—' }}</td>
                                <td>{{ $row['QuangDuongThucHanhKm'] ?? '—' }}</td>
                                <td>
                                    <div>{{ $row['HoTenHocVien'] ?? '—' }}</div>
                                    <small class="text-muted">{{ $row['MaHocVien'] ?? '' }}</small>
                                </td>
                                <td>
                                    <div>{{ $row['TenKhoaHoc'] ?? '—' }}</div>
                                    <small class="text-muted">{{ $row['MaKhoaHoc'] ?? '' }}</small>
                                </td>
                                <td>
                                    <div>{{ $row['HoTenGiaoVien'] ?? '—' }}</div>
                                    <small class="text-muted">{{ $row['MaGiaoVien'] ?? '' }}</small>
                                </td>
                                <td>{{ $row['BienSoXe'] ?? '—' }}</td>
                                <td>{{ $row['MaThietBi'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('daotao.pdt.dat.nhap-du-lieu-phien.confirm') }}">
        @csrf
        <button type="submit" class="btn btn-success btn-lg">Xác nhận lưu DB</button>
        <a href="{{ route('daotao.pdt.dat.nhap-du-lieu-phien.cancel') }}" class="btn btn-outline-secondary btn-lg ml-2">Hủy</a>
    </form>
@endsection
