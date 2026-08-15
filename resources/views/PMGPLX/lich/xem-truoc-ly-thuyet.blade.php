@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Xem trước lịch dạy lý thuyết')

@section('content')
    @php
        $conflictCount = (int) ($preview['meta']['conflict_count'] ?? 0);
        $okCount = (int) ($preview['meta']['ok_count'] ?? 0);
    @endphp

    <div class="card card-panel">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Xem trước kết quả — Lịch dạy lý thuyết</span>
            <a href="{{ route('pmgplx.lich.ly-thuyet.cancel') }}" class="btn btn-sm btn-outline-secondary">← Quay lại chỉnh sửa</a>
        </div>
        <div class="card-body">
            <div class="mb-3">
                @php
                    $ngayList = collect($preview['rows'] ?? [])
                        ->map(fn ($row) => \Carbon\Carbon::parse($row['NgayBD'])->format('d/m/Y'))
                        ->unique()
                        ->values();
                @endphp
                <div><strong>Khóa học:</strong> {{ $preview['meta']['ten_kh'] ?? '' }} ({{ $preview['form']['ma_kh'] ?? '' }})</div>
                <div><strong>Môn học:</strong> {{ $preview['form']['ten_mon_hoc'] ?? '' }}</div>
                <div><strong>Ngày:</strong> {{ $ngayList->implode(', ') }}</div>
                <div><strong>Giờ:</strong> {{ $preview['form']['gio_bd'] ?? '' }} → {{ $preview['form']['gio_kt'] ?? '' }}</div>
                <div><strong>Tổng buổi:</strong> {{ count($preview['rows']) }} —
                    sẽ lưu <strong class="text-success">{{ $okCount }}</strong>,
                    bỏ qua <strong class="text-danger">{{ $conflictCount }}</strong> trùng
                </div>
            </div>

            @if ($conflictCount > 0)
                <div class="alert alert-warning">
                    Các dòng nền đỏ đã có trong lịch (trùng thời gian). Khi xác nhận sẽ <strong>không lưu</strong> các dòng này.
                </div>
            @endif

            <div class="table-responsive mb-3">
                <table class="table table-sm table-bordered table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>STT</th>
                            <th>Mã GV</th>
                            <th>Tên giáo viên</th>
                            <th>Môn học</th>
                            <th>TG bắt đầu</th>
                            <th>TG kết thúc</th>
                            <th>Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($preview['rows'] as $i => $row)
                            <tr class="{{ !empty($row['conflict']) ? 'table-danger' : '' }}">
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $row['MaGV'] }}</td>
                                <td>{{ $row['TenGV'] }}</td>
                                <td>{{ \App\Support\PMGPLX\LichGvMonHoc::displayLabel($row['TenMonHoc'], $row['MaMonHoc'], $monMap) }}</td>
                                <td>{{ \Carbon\Carbon::parse($row['NgayBD'])->format('d/m/Y H:i') }}</td>
                                <td>{{ \Carbon\Carbon::parse($row['NgayKT'])->format('d/m/Y H:i') }}</td>
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

            @if ($okCount > 0)
                <form method="POST" action="{{ route('pmgplx.lich.ly-thuyet.confirm') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success btn-lg">
                        Xác nhận lưu {{ $okCount }} buổi
                    </button>
                </form>
            @else
                <button type="button" class="btn btn-secondary btn-lg" disabled>Không có buổi nào để lưu</button>
            @endif
            <a href="{{ route('pmgplx.lich.ly-thuyet.cancel') }}" class="btn btn-outline-danger btn-lg ml-2">Hủy</a>
        </div>
    </div>
@endsection
