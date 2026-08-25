@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Xem trước — Sổ phân công giáo viên')

@push('styles')
<style>
    .phan-cong-skip-row {
        background: #f8f9fa;
        color: #6c757d;
    }
    .phan-cong-skip-row td {
        text-decoration: line-through;
    }
    .phan-cong-skip-row td:last-child,
    .phan-cong-skip-row td:nth-child(7) {
        text-decoration: none;
    }
</style>
@endpush

@section('content')
    @php
        $meta = $preview['meta'] ?? [];
        $validationErrors = $preview['validation_errors'] ?? [];
        $overlapErrors = $preview['overlap_errors'] ?? [];
        $skipCount = (int) ($meta['skip_count'] ?? 0);
        $updateCount = (int) ($meta['update_count'] ?? 0);
        $createCount = (int) ($meta['create_count'] ?? 0);
    @endphp

    <div class="card card-panel mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Xem trước — Sổ phân công giáo viên</span>
            <a href="{{ route('daotao.pdt.cong-cu-nhap.nhap-file-so-phan-cong-giao-vien.cancel') }}" class="btn btn-sm btn-outline-secondary">← Chọn file khác</a>
        </div>
        <div class="card-body">
            <div><strong>File:</strong> {{ $preview['file_name'] ?? '' }}</div>
            <div><strong>Sheet:</strong> {{ $preview['sheet_name'] ?? '' }}</div>
            <div>
                <strong>Tổng quan:</strong>
                {{ number_format((int) ($meta['record_count'] ?? 0)) }} dòng trong file,
                <strong class="text-success">{{ number_format($saveableCount) }} dòng sẽ lưu</strong>
                @if ($updateCount > 0 || $createCount > 0)
                    (<span class="text-primary">{{ number_format($updateCount) }} cập nhật</span>,
                    <span class="text-success">{{ number_format($createCount) }} thêm mới</span>)
                @endif,
                {{ number_format($skipCount) }} dòng bỏ qua
            </div>
            <div class="small text-muted mt-2 mb-0">
                Chỉ lưu dòng có <strong>giáo viên</strong> và nội dung map được:
                <code>ly_thuyet</code> (chứa “lý thuyết” hoặc “GVLT”),
                <code>thuc_hanh</code> (chứa “thực hành” hoặc “GVTH”).
                Dòng “tự động” hoặc không có giáo viên — không lưu.
                Nhập lại cùng khoá: trùng <strong>giáo viên + loại + xe</strong> → <strong>Cập nhật</strong> (kể cả đổi thời gian).
            </div>
        </div>
    </div>

    @if ($validationErrors !== [])
        <div class="alert alert-danger">
            <strong>Lỗi dữ liệu:</strong>
            <ul class="mb-0 pl-3">
                @foreach ($validationErrors as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($saveableCount === 0 && $validationErrors === [])
        <div class="alert alert-warning">
            Không có dòng nào đủ điều kiện lưu. Kiểm tra cột giáo viên và nội dung giảng dạy.
        </div>
    @endif

    @if ($overlapErrors !== [])
        <div class="alert alert-warning">
            <strong>Cảnh báo — trùng lịch giáo viên / xe</strong> (vẫn có thể lưu):
            <ul class="mb-0 pl-3">
                @foreach ($overlapErrors as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card card-panel mb-3">
        <div class="card-header">Danh sách phân công</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>STT</th>
                            <th>Giáo viên</th>
                            <th>Thời gian</th>
                            <th>Khoá</th>
                            <th>Biển số xe</th>
                            <th>Nội dung (file)</th>
                            <th>Loại lưu DB</th>
                            <th>Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($preview['records'] as $index => $row)
                            @php
                                $willSave = \App\Models\DaoTao\PhanCongDaoTao::isSaveableRecord($row);
                                $loai = \App\Models\DaoTao\PhanCongDaoTao::classifyLoaiGiangDay((string) ($row['NoiDungGiangDay'] ?? ''));
                                $skipReason = \App\Models\DaoTao\PhanCongDaoTao::skipReason($row);
                                $importLabel = \App\Models\DaoTao\PhanCongDaoTao::importActionLabel($row['import_action'] ?? null);
                            @endphp
                            <tr class="{{ $willSave ? '' : 'phan-cong-skip-row' }}">
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $row['HoTen'] ?: '—' }}</td>
                                <td>{{ $row['ThoiGian'] ?: '—' }}</td>
                                <td><strong>{{ $row['TenKhoa'] ?: '—' }}</strong></td>
                                <td>{{ $row['BienSo'] ?: '—' }}</td>
                                <td>{{ $row['NoiDungGiangDay'] ?: '—' }}</td>
                                <td>
                                    @if ($willSave)
                                        <strong>{{ \App\Models\DaoTao\PhanCongDaoTao::loaiGiangDayLabel($loai) }}</strong>
                                        <span class="text-muted small">({{ $loai }})</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="small {{ $willSave ? ($importLabel === 'Cập nhật' ? 'text-primary' : 'text-success') : 'text-muted' }}">
                                    {{ $willSave ? ($importLabel ?? 'Sẽ lưu') : ($skipReason ?? 'Bỏ qua') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">Không có dữ liệu</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex align-items-center">
        <a href="{{ route('daotao.pdt.cong-cu-nhap.nhap-file-so-phan-cong-giao-vien.cancel') }}" class="btn btn-outline-secondary">← Chọn file khác</a>
        @if ($canConfirm)
            <form method="POST" action="{{ route('daotao.pdt.cong-cu-nhap.nhap-file-so-phan-cong-giao-vien.confirm') }}" class="d-inline ml-2"
                  onsubmit="return confirm('Xác nhận lưu {{ number_format($saveableCount) }} dòng phân công vào DB MANHLINH?');">
                @csrf
                <button type="submit" class="btn btn-navy btn-lg">Xác nhận lưu DB ({{ number_format($saveableCount) }} dòng)</button>
            </form>
        @else
            <button type="button" class="btn btn-secondary btn-lg ml-2" disabled>
                @if ($validationErrors !== [])
                    Không thể lưu — còn lỗi dữ liệu
                @else
                    Không thể lưu — không có dòng hợp lệ
                @endif
            </button>
        @endif
    </div>
@endsection
