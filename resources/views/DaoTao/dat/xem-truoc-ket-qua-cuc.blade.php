@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Xem trước — Nhập kết quả cục')

@section('content')
    @php
        $meta = $preview['meta'] ?? [];
        $maKhoaHocList = $meta['ma_khoa_hoc_list'] ?? [];
        $khoaStats = $meta['khoa_stats'] ?? [];
    @endphp

    <div class="card card-panel mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Xem trước — Nhập kết quả cục</span>
            <a href="{{ route('daotao.pdt.dat.nhap-ket-qua-cuc.cancel') }}" class="btn btn-sm btn-outline-secondary">← Chọn file khác</a>
        </div>
        <div class="card-body">
            <div><strong>File:</strong> {{ $preview['file_name'] ?? '' }}</div>
            <div><strong>Sheet:</strong> {{ $preview['sheet_name'] ?? '' }}</div>
            <div>
                <strong>Số khóa:</strong> {{ number_format((int) ($meta['so_khoa'] ?? count($maKhoaHocList))) }}
            </div>
            <div>
                <strong>Tổng dòng trong file:</strong> {{ number_format((int) ($meta['record_count'] ?? 0)) }}
                @if ((int) ($meta['skipped_count'] ?? 0) > 0)
                    — bỏ qua {{ (int) $meta['skipped_count'] }} dòng thiếu mã khóa
                @endif
            </div>
            <div>
                <strong>Tổng phiên trong DB (các khóa trên):</strong>
                {{ number_format((int) ($meta['tong_phien_db'] ?? 0)) }}
            </div>
            <div class="mt-2">
                <span class="badge badge-success mr-1">
                    {{ number_format((int) ($meta['count_da_truyen'] ?? 0)) }} → Đã truyền lên cục
                </span>
                <span class="badge badge-danger mr-1">
                    {{ number_format((int) ($meta['count_khong_chap_nhan'] ?? 0)) }} trong file → Cục không chấp nhận
                </span>
                @if ((int) ($meta['khong_trong_file'] ?? 0) > 0)
                    <span class="badge badge-warning">
                        {{ number_format((int) $meta['khong_trong_file']) }} phiên DB không có trong file → Cục không chấp nhận
                    </span>
                @endif
            </div>
            @if ($detailTotal > $detailLimit)
                <div class="text-muted small mt-2">
                    Chỉ hiển thị {{ number_format($detailLimit) }} dòng đầu để xem trước
                    (tổng {{ number_format($detailTotal) }} dòng trong file).
                </div>
            @endif
        </div>
    </div>

    @if ($maKhoaHocList !== [])
        <div class="card card-panel mb-3">
            <div class="card-header">Theo khóa học</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Mã khóa</th>
                                <th>Dòng file</th>
                                <th>Phiên DB</th>
                                <th>Đã truyền</th>
                                <th>Cục không chấp nhận (file)</th>
                                <th>DB không có trong file</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($maKhoaHocList as $maKh)
                                @php
                                    $ks = $khoaStats[$maKh] ?? [];
                                @endphp
                                <tr>
                                    <td><code>{{ $maKh }}</code></td>
                                    <td>{{ number_format((int) ($ks['record_count'] ?? 0)) }}</td>
                                    <td>{{ number_format((int) ($ks['tong_phien_db'] ?? 0)) }}</td>
                                    <td>{{ number_format((int) ($ks['count_da_truyen'] ?? 0)) }}</td>
                                    <td>{{ number_format((int) ($ks['count_khong_chap_nhan'] ?? 0)) }}</td>
                                    <td>{{ number_format((int) ($ks['khong_trong_file'] ?? 0)) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="card card-panel mb-3">
        <div class="card-header">Chi tiết — {{ count($detailRows) }} dòng mẫu (tối đa {{ $detailLimit }})</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Mã phiên học</th>
                            <th>Mã khóa học</th>
                            <th>Trạng thái (cột S)</th>
                            <th>Phân loại sẽ gán</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($detailRows as $i => $row)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td><code>{{ $row['MaPhienHoc'] ?? '' }}</code></td>
                                <td><code>{{ $row['MaKhoaHoc'] ?? '' }}</code></td>
                                <td>{{ $row['TrangThai'] ?? '—' }}</td>
                                <td>
                                    @if (($row['KetQuaPhanLoai'] ?? '') === \App\Support\DaoTao\DatKetQuaCucUpdater::PHAN_LOAI_DA_TRUYEN)
                                        <span class="badge badge-success">{{ $row['KetQuaPhanLoai'] }}</span>
                                    @else
                                        <span class="badge badge-danger">{{ $row['KetQuaPhanLoai'] ?? '—' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('daotao.pdt.dat.nhap-ket-qua-cuc.confirm') }}">
        @csrf
        <button type="submit" class="btn btn-success btn-lg">Xác nhận cập nhật phân loại</button>
        <a href="{{ route('daotao.pdt.dat.nhap-ket-qua-cuc.cancel') }}" class="btn btn-outline-secondary btn-lg ml-2">Hủy</a>
    </form>
@endsection
