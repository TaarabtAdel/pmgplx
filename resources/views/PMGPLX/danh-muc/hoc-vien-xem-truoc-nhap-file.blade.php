@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Xem trước nhập học viên từ file')

@section('content')
    @php
        $meta = $preview['meta'] ?? [];
        $colCount = (int) ($meta['col_count'] ?? 0);
    @endphp

    <div class="card card-panel">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Xem trước — Nhập học viên từ file</span>
            <a href="{{ route('pmgplx.dm.hoc-vien.nhap-file.cancel') }}" class="btn btn-sm btn-outline-secondary">← Chọn file khác</a>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <div><strong>File:</strong> {{ $preview['file_name'] ?? '' }}</div>
                <div><strong>Số dòng (không trống):</strong> {{ number_format($totalRows) }}</div>
                <div><strong>Số cột:</strong> {{ number_format($colCount) }}</div>
            </div>

            <div class="alert alert-warning mb-3">
                Mapping cột Excel → học viên <strong>chưa được cấu hình</strong>.
                Bảng dưới chỉ hiển thị dữ liệu thô đọc từ file (tối đa {{ number_format($displayLimit) }} dòng đầu).
                Gửi file mẫu để hoàn thiện bước lưu vào phần mềm.
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>STT</th>
                            @for ($c = 0; $c < $colCount; $c++)
                                <th>Cột {{ $c + 1 }}</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($displayRows as $index => $row)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                @for ($c = 0; $c < $colCount; $c++)
                                    <td>{{ $row[$c] ?? '' }}</td>
                                @endfor
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($totalRows > $displayLimit)
                <p class="text-muted small mt-2 mb-0">
                    … và {{ number_format($totalRows - $displayLimit) }} dòng nữa (chưa hiển thị).
                </p>
            @endif

            <div class="mt-3">
                <a href="{{ route('pmgplx.dm.hoc-vien.nhap-file.cancel') }}" class="btn btn-outline-secondary">Quay lại chọn file</a>
                <a href="{{ route('pmgplx.dm.hoc-vien.index') }}" class="btn btn-outline-secondary ml-2">Danh sách học viên</a>
            </div>
        </div>
    </div>
@endsection
