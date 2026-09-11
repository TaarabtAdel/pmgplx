@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Xem trước — Nhập phân công học viên')

@section('content')
    @php
        $meta = $preview['meta'] ?? [];
    @endphp

    <div class="card card-panel mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Xem trước — Nhập phân công học viên</span>
            <a href="{{ route('daotao.pdt.dat.nhap-phan-cong-hoc-vien.cancel') }}" class="btn btn-sm btn-outline-secondary">← Chọn file khác</a>
        </div>
        <div class="card-body">
            <div><strong>File:</strong> {{ $preview['file_name'] ?? '' }}</div>
            <div><strong>Sheet:</strong> {{ $preview['sheet_name'] ?? '' }}</div>
            <div><strong>Tiêu đề A1:</strong> {{ $meta['title_a1'] ?? '—' }}</div>
            <div><strong>Mã khóa học:</strong> <code>{{ $meta['ma_khoa_hoc'] ?? '' }}</code></div>
            <div><strong>Tổng dòng:</strong> {{ number_format((int) ($meta['record_count'] ?? 0)) }}</div>
            <div class="mt-2">
                <span class="badge badge-success mr-1">{{ number_format((int) ($meta['save_count'] ?? 0)) }} sẽ lưu</span>
                @if ((int) ($meta['error_count'] ?? 0) > 0)
                    <span class="badge badge-danger mr-1">{{ number_format((int) $meta['error_count']) }} lỗi</span>
                @endif
                @if ((int) ($meta['warning_count'] ?? 0) > 0)
                    <span class="badge badge-warning mr-1">{{ number_format((int) $meta['warning_count']) }} cảnh báo</span>
                @endif
                <span class="text-muted small">
                    · {{ number_format((int) ($meta['gv_count'] ?? 0)) }} GV
                    · {{ number_format((int) ($meta['xe_count'] ?? 0)) }} xe
                </span>
            </div>
            @if ($detailTotal > $detailLimit)
                <div class="text-muted small mt-2">
                    Hiển thị {{ number_format($detailLimit) }} dòng đầu (tổng {{ number_format($detailTotal) }} dòng).
                </div>
            @endif
            <p class="small text-muted mt-2 mb-0">
                Xác nhận sẽ đồng bộ phân công khóa <code>{{ $meta['ma_khoa_hoc'] ?? '' }}</code> theo file:
                thêm mới / <strong>cập nhật mã HV trùng</strong> / xóa học viên không còn trong file (chỉ các dòng hợp lệ).
                Mã HV trùng trong file → giữ dòng cuối.
            </p>
        </div>
    </div>

    <div class="card card-panel mb-3">
        <div class="card-header">Chi tiết xem trước</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Dòng</th>
                            <th>STT</th>
                            <th>Họ tên (file)</th>
                            <th>Mã HV</th>
                            <th>Mã GV</th>
                            <th>Xe số sàn</th>
                            <th>Xe tự động</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($detailRows as $row)
                            <tr @class(['table-danger' => ! empty($row['errors']), 'table-warning' => empty($row['errors']) && ! empty($row['warnings'])])>
                                <td>{{ $row['row_number'] ?? '' }}</td>
                                <td>{{ $row['STT'] ?? '' }}</td>
                                <td>{{ $row['HoTenHocVien'] ?? '' }}</td>
                                <td><code>{{ $row['MaHocVien'] ?? '' }}</code></td>
                                <td><code>{{ $row['MaGiaoVien'] ?? '' }}</code></td>
                                <td>{{ $row['BienSoXeHienThi'] ?? ($row['BienSoXe'] ?? '') }}</td>
                                <td>{{ $row['BienSoXeTuDongHienThi'] ?? ($row['BienSoXeTuDong'] ?? '') }}</td>
                                <td class="small">
                                    @if (! empty($row['can_save']))
                                        <span class="text-success">OK</span>
                                    @endif
                                    @foreach ($row['errors'] ?? [] as $err)
                                        <div class="text-danger">{{ $err }}</div>
                                    @endforeach
                                    @foreach ($row['warnings'] ?? [] as $warn)
                                        <div class="text-warning">{{ $warn }}</div>
                                    @endforeach
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">Không có dữ liệu</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('daotao.pdt.dat.nhap-phan-cong-hoc-vien.confirm') }}">
        @csrf
        <button type="submit" class="btn btn-navy btn-lg" @disabled((int) ($meta['save_count'] ?? 0) === 0)>
            Xác nhận lưu {{ number_format((int) ($meta['save_count'] ?? 0)) }} dòng
        </button>
        <a href="{{ route('daotao.pdt.dat.nhap-phan-cong-hoc-vien.cancel') }}" class="btn btn-outline-secondary btn-lg ml-2">Hủy</a>
    </form>
@endsection
