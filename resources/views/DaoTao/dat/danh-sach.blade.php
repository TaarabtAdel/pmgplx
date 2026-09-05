@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Quản lý phiên DAT')

@section('content')
    <div class="card card-panel mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Quản lý phiên DAT</span>
            <a href="{{ route('daotao.pdt.dat.nhap-du-lieu-phien') }}" class="btn btn-sm btn-navy">Nhập dữ liệu phiên</a>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('daotao.pdt.dat.quan-ly-phien') }}" class="mb-3">
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label>Tìm nhanh</label>
                        <input type="text" name="q" class="form-control" value="{{ $filters['q'] }}"
                               placeholder="Mã phiên, tên HV/GV, biển số, thiết bị">
                    </div>
                    <div class="form-group col-md-2">
                        <label>Mã học viên</label>
                        <input type="text" name="ma_hoc_vien" class="form-control" value="{{ $filters['ma_hoc_vien'] }}">
                    </div>
                    <div class="form-group col-md-2">
                        <label>Mã khóa học</label>
                        <input type="text" name="ma_khoa_hoc" class="form-control" value="{{ $filters['ma_khoa_hoc'] }}">
                    </div>
                    <div class="form-group col-md-2">
                        <label>Mã giáo viên</label>
                        <input type="text" name="ma_giao_vien" class="form-control" value="{{ $filters['ma_giao_vien'] }}">
                    </div>
                    <div class="form-group col-md-1">
                        <label>Từ ngày</label>
                        <input type="date" name="tu_ngay" class="form-control" value="{{ $filters['tu_ngay'] }}">
                    </div>
                    <div class="form-group col-md-1">
                        <label>Đến ngày</label>
                        <input type="date" name="den_ngay" class="form-control" value="{{ $filters['den_ngay'] }}">
                    </div>
                    <div class="form-group col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-navy btn-block">Lọc</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Mã phiên học</th>
                            <th>Học viên</th>
                            <th>Khóa học</th>
                            <th>Giáo viên</th>
                            <th>Xe</th>
                            <th>Bắt đầu</th>
                            <th>Kết thúc</th>
                            <th>TH (giờ)</th>
                            <th>QD (km)</th>
                            <th>Tỉ lệ ND</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td>{{ ($items->firstItem() ?? 0) + $loop->index }}</td>
                                <td><code>{{ $item->MaPhienHoc }}</code></td>
                                <td>
                                    <div>{{ $item->HoTenHocVien ?? '—' }}</div>
                                    <small class="text-muted">{{ $item->MaHocVien ?? '' }}</small>
                                </td>
                                <td>
                                    <div>{{ $item->TenKhoaHoc ?? '—' }}</div>
                                    <small class="text-muted">{{ $item->MaKhoaHoc ?? '' }}</small>
                                </td>
                                <td>
                                    <div>{{ $item->HoTenGiaoVien ?? '—' }}</div>
                                    <small class="text-muted">{{ $item->MaGiaoVien ?? '' }}</small>
                                </td>
                                <td>{{ $item->BienSoXe ?? '—' }}</td>
                                <td>{{ $item->ThoiGianBatDauPhienHoc?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td>{{ $item->ThoiGianKetThucPhienHoc?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td>{{ $item->ThoiGianThucHanhGio ?? '—' }}</td>
                                <td>{{ $item->QuangDuongThucHanhKm ?? '—' }}</td>
                                <td>{{ $item->TiLeNhanDien ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-4 text-muted">Chưa có dữ liệu phiên học.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $items->links() }}
            </div>
        </div>
    </div>
@endsection
