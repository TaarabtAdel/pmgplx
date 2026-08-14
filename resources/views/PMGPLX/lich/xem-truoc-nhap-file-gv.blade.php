@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Xem trước lịch giáo viên (nhập file)')

@section('content')
    @php
        $okCount = (int) ($preview['meta']['gv_ok_count'] ?? count($rows));
        $conflictCount = (int) ($preview['meta']['gv_conflict_count'] ?? 0);
        $khoaHocList = $preview['meta']['khoa_hoc_list'] ?? [];
        $ngayList = $preview['meta']['ngay_list'] ?? [];
    @endphp

    <div class="card card-panel">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Xem trước Lịch giáo viên — Nhập từ file</span>
            <a href="{{ route('pmgplx.lich.nhap-file.preview') }}" class="btn btn-sm btn-outline-secondary">← Quay lại Excel</a>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <div><strong>File:</strong> {{ $preview['file_name'] ?? '' }}</div>
                <div><strong>Khóa học:</strong> {{ implode(', ', $khoaHocList) }}</div>
                <div><strong>Môn học:</strong> {{ $preview['meta']['ten_mon_hoc'] ?? 'Thực hành lái xe' }}</div>
                <div><strong>Ngày:</strong> {{ implode(', ', $ngayList) }}</div>
                <div>
                    <strong>Tổng buổi:</strong> {{ count($rows) }} —
                    sẽ lưu <strong class="text-success">{{ $okCount }}</strong>,
                    bỏ qua <strong class="text-danger">{{ $conflictCount }}</strong> trùng
                </div>
            </div>

            <div class="alert alert-info">
                Không có địa điểm. Có thể sửa trực tiếp trên từng dòng trước khi tiếp tục.
            </div>

            @if ($conflictCount > 0)
                <div class="alert alert-warning">
                    Dòng nền đỏ đã trùng lịch giáo viên. Khi lưu sẽ bỏ qua các dòng này.
                </div>
            @endif

            <form method="POST" action="{{ route('pmgplx.lich.nhap-file.to-xe') }}">
                @csrf
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>STT</th>
                                <th>Mã KH</th>
                                <th>Mã GV</th>
                                <th>Tên giáo viên</th>
                                <th>Môn học</th>
                                <th style="min-width: 11rem;">TG bắt đầu</th>
                                <th style="min-width: 11rem;">TG kết thúc</th>
                                <th>Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $i => $row)
                                <tr class="{{ !empty($row['conflict']) ? 'table-danger' : '' }}">
                                    <td>{{ $i + 1 }}</td>
                                    <td>
                                        <input type="hidden" name="rows[{{ $i }}][source_key]" value="{{ $row['source_key'] ?? '' }}">
                                        <input type="text" name="rows[{{ $i }}][MaKH]" class="form-control form-control-sm"
                                               value="{{ $row['MaKH'] }}" readonly required>
                                    </td>
                                    <td>
                                        <input type="text" name="rows[{{ $i }}][MaGV]" class="form-control form-control-sm"
                                               value="{{ $row['MaGV'] }}" readonly required>
                                    </td>
                                    <td>
                                        <input type="text" name="rows[{{ $i }}][TenGV]" class="form-control form-control-sm"
                                               value="{{ $row['TenGV'] }}" readonly required>
                                    </td>
                                    <td>
                                        @php $selectedMaMonHoc = trim((string) ($row['MaMonHoc'] ?? $defaultMaMonHoc ?? '')); @endphp
                                        <select name="rows[{{ $i }}][MaMonHoc]" class="form-control form-control-sm" required>
                                            <option value="">-- Chọn môn học --</option>
                                            @foreach ($monHocs as $mh)
                                                @php
                                                    $maMh = trim((string) $mh->MaMH);
                                                    $tenMh = trim((string) $mh->TenMH);
                                                    $isSelected = $selectedMaMonHoc !== '' && $selectedMaMonHoc === $maMh;
                                                    if (! $isSelected && $selectedMaMonHoc === '') {
                                                        $isSelected = mb_stripos($tenMh, 'Thực hành lái xe') !== false;
                                                    }
                                                @endphp
                                                <option value="{{ $maMh }}" @selected($isSelected)>
                                                    {{ $tenMh }} ({{ $maMh }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="datetime-local" name="rows[{{ $i }}][NgayBD]" class="form-control form-control-sm"
                                               value="{{ \Carbon\Carbon::parse($row['NgayBD'])->format('Y-m-d\TH:i') }}" required>
                                    </td>
                                    <td>
                                        <input type="datetime-local" name="rows[{{ $i }}][NgayKT]" class="form-control form-control-sm"
                                               value="{{ \Carbon\Carbon::parse($row['NgayKT'])->format('Y-m-d\TH:i') }}" required>
                                    </td>
                                    <td>
                                        @if (!empty($row['conflict']))
                                            <span class="text-danger font-weight-bold">Đã thêm vào lịch</span>
                                        @else
                                            <span class="text-success">Sẽ lưu mới</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <button type="submit" class="btn btn-navy btn-lg">Tiếp tục — Xem trước lịch xe tập lái</button>
                <a href="{{ route('pmgplx.lich.nhap-file.preview') }}" class="btn btn-outline-secondary btn-lg ml-2">← Quay lại Excel</a>
                <a href="{{ route('pmgplx.lich.nhap-file.cancel') }}" class="btn btn-outline-danger btn-lg ml-2">Hủy</a>
            </form>
        </div>
    </div>
@endsection
