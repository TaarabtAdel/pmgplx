@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Xem trước — Sổ phân công giáo viên')

@section('content')
    @php
        $meta = $preview['meta'] ?? [];
        $validationErrors = $preview['validation_errors'] ?? [];
        $overlapErrors = $preview['overlap_errors'] ?? [];
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
                {{ number_format((int) ($meta['record_count'] ?? 0)) }} dòng,
                {{ (int) ($meta['khoa_count'] ?? 0) }} khoá,
                {{ (int) ($meta['gv_count'] ?? 0) }} giáo viên,
                {{ (int) ($meta['xe_count'] ?? 0) }} xe
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

    @if ($overlapErrors !== [])
        <div class="alert alert-warning">
            <strong>Trùng lịch giáo viên / xe:</strong>
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
                            <th>Nội dung giảng dạy</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($preview['records'] as $row)
                            <tr>
                                <td>{{ $row['SoTT'] ?? '—' }}</td>
                                <td>{{ $row['HoTen'] ?: '—' }}</td>
                                <td>{{ $row['ThoiGian'] ?: '—' }}</td>
                                <td><strong>{{ $row['TenKhoa'] ?: '—' }}</strong></td>
                                <td>{{ $row['BienSo'] ?: '—' }}</td>
                                <td>{{ $row['NoiDungGiangDay'] ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">Không có dữ liệu</td>
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
                  onsubmit="return confirm('Xác nhận lưu dữ liệu phân công vào DB MANHLINH?');">
                @csrf
                <button type="submit" class="btn btn-navy btn-lg">Xác nhận lưu DB</button>
            </form>
        @else
            <button type="button" class="btn btn-secondary btn-lg ml-2" disabled>Không thể lưu — còn lỗi</button>
        @endif
    </div>
@endsection
