@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Xem trước — Nhập file tiến độ đào tạo')

@push('styles')
<style>
    .td-preview-meta {
        color: #444;
        line-height: 1.6;
    }
    .td-preview-meta strong {
        color: #1f4e79;
    }
    .td-ky-hieu-empty {
        color: #aaa;
    }
    .nav-sheet-tabs .nav-link {
        font-weight: 600;
    }
</style>
@endpush

@section('content')
    @php
        $meta = $preview['meta'] ?? [];
    @endphp

    <div class="card card-panel mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Xem trước — Nhập file tiến độ đào tạo</span>
            <a href="{{ route('daotao.pdt.td.nhap-file.cancel') }}" class="btn btn-sm btn-outline-secondary">← Chọn file khác</a>
        </div>
        <div class="card-body td-preview-meta">
            <div><strong>File:</strong> {{ $preview['file_name'] ?? '' }}</div>
            <div>
                <strong>Tổng quan:</strong>
                {{ (int) ($meta['sheet_count'] ?? 0) }} sheet,
                {{ (int) ($meta['class_count'] ?? 0) }} lớp,
                {{ number_format((int) ($meta['record_count'] ?? 0)) }} dòng tuần
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs nav-sheet-tabs mb-3">
        @foreach ($preview['sheets'] as $i => $sheet)
            <li class="nav-item">
                <a class="nav-link {{ $sheetIndex === $i ? 'active' : '' }}"
                   href="{{ route('daotao.pdt.td.nhap-file.preview', ['sheet' => $i]) }}">
                    {{ $sheet['sheet_name'] ?? ('Sheet '.($i + 1)) }}
                    <span class="badge badge-secondary ml-1">{{ (int) ($sheet['meta']['class_count'] ?? 0) }} lớp</span>
                </a>
            </li>
        @endforeach
    </ul>

    <div class="card card-panel mb-3">
        <div class="card-header">
            Danh sách lớp — Sheet <strong>{{ $activeSheet['sheet_name'] ?? '' }}</strong>
            @if (! empty($activeSheet['nam_hoc']))
                (năm {{ $activeSheet['nam_hoc'] }})
            @endif
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>STT</th>
                            <th>Mã khóa-lớp</th>
                            <th>Giáo viên dạy</th>
                            <th>Số HV</th>
                            <th>Số TN</th>
                            <th>Tuần có ký hiệu</th>
                            <th>Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($activeSheet['classes'] ?? [] as $lop)
                            <tr>
                                <td>{{ $lop['SoTT'] ?? '—' }}</td>
                                <td><strong>{{ $lop['MaKhoaLop'] ?? '' }}</strong></td>
                                <td>{{ $lop['GiaoVienDay'] ?? '—' }}</td>
                                <td>{{ $lop['SoLuongHocVien'] ?? '—' }}</td>
                                <td>{{ $lop['SoHocVienTotNghiep'] ?? '—' }}</td>
                                <td>
                                    {{ (int) ($lop['ky_hieu_count'] ?? 0) }}
                                    / {{ (int) ($lop['week_count'] ?? 0) }}
                                </td>
                                <td>{{ $lop['GhiChu'] ?? '' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Không có lớp nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card card-panel">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Chi tiết tuần (chỉ dòng có ký hiệu)</span>
            <span class="small text-muted">
                Hiển thị {{ count($detailRows) }}/{{ number_format($detailTotal) }} dòng
                @if ($detailTotal > $detailLimit)
                    — xem {{ $detailLimit }} dòng đầu
                @endif
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Mã lớp</th>
                            <th>GV</th>
                            <th>Tháng</th>
                            <th>Tuần</th>
                            <th>Từ ngày</th>
                            <th>Đến ngày</th>
                            <th>Ký hiệu</th>
                            <th>Số HV</th>
                            <th>Số TN</th>
                            <th>Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($detailRows as $row)
                            <tr>
                                <td>{{ $row['MaKhoaLop'] ?? '' }}</td>
                                <td>{{ $row['GiaoVienDay'] ?? '—' }}</td>
                                <td>{{ $row['ThangNam'] ?? '—' }}</td>
                                <td>{{ $row['TuanThu'] ?? '—' }}</td>
                                <td>
                                    @if (! empty($row['TuNgay']))
                                        {{ \Carbon\Carbon::parse($row['TuNgay'])->format('d/m/Y') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if (! empty($row['DenNgay']))
                                        {{ \Carbon\Carbon::parse($row['DenNgay'])->format('d/m/Y') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td><strong>{{ $row['KyHieu'] ?? '' }}</strong></td>
                                <td>{{ $row['SoLuongHocVien'] ?? '—' }}</td>
                                <td>{{ $row['SoHocVienTotNghiep'] ?? '—' }}</td>
                                <td>{{ $row['GhiChu'] ?? '' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">Không có dữ liệu tuần.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer d-flex align-items-center flex-wrap">
            <a href="{{ route('daotao.pdt.td.nhap-file.cancel') }}" class="btn btn-outline-secondary">← Chọn file khác</a>
            <form method="POST" action="{{ route('daotao.pdt.td.nhap-file.confirm') }}" class="d-inline ml-2"
                  onsubmit="return confirm('Lưu {{ number_format((int) ($preview['meta']['record_count'] ?? 0)) }} dòng vào DB MANHLINH?\n\nLớp trùng mã khóa-lớp (cùng năm) sẽ được cập nhật (ghi đè dữ liệu cũ).');">
                @csrf
                <button type="submit" class="btn btn-success btn-lg">
                    Xác nhận lưu DB ({{ number_format((int) ($preview['meta']['record_count'] ?? 0)) }} dòng)
                </button>
            </form>
        </div>
    </div>
@endsection
