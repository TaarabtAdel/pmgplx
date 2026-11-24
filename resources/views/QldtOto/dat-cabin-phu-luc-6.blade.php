@extends('PMGPLX.layouts.quan-ly')

@section('title', $item['title'])

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-white border py-2 small mb-3">
            <li class="breadcrumb-item"><a href="{{ route('qldtoto.index') }}">QLĐT Ô TÔ</a></li>
            @foreach (array_slice($duongDan, 1) as $buoc)
                <li class="breadcrumb-item">{{ $buoc }}</li>
            @endforeach
            <li class="breadcrumb-item active" aria-current="page">{{ $item['title'] }}</li>
        </ol>
    </nav>

    <div class="card card-panel">
        <div class="card-header">{{ $item['title'] }}</div>
        <div class="card-body">
            <div class="alert alert-info small py-2">
                Xuất ra mẫu <code>phu-luc-6-dat-cabin.goc.xlsx</code>.
                Upload file <strong>danh sách thí sinh thi đạt</strong> (Excel <code>.xls</code> / <code>.xlsx</code>) — thường là file KTM
                kiểu <em>«DANH SÁCH THÍ SINH THI ĐẠT»</em>: hàng tiêu đề có <strong>Họ và tên</strong> + <strong>Số định danh</strong> (CCCD).
                Hệ thống đối chiếu PMGPLX (khóa đã chọn) → <strong>mã học viên</strong> (MaDK), rồi lấy <strong>Tổng giờ</strong> / <strong>Tổng km</strong>
                như màn <a href="{{ route('daotao.pdt.dat.tong-hop-hoc-vien') }}" target="_blank" rel="noopener">Tổng hợp học viên DAT</a>
                (chỉ phiên đạt; có thể lọc từ–đến ngày).
                <a href="{{ route('qldtoto.4-dat-cabin-phu-luc-6.mau-danh-sach-dat') }}" class="ml-1">Tải mẫu cấu trúc danh sách đạt</a> (không dữ liệu học viên).
            </div>

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('qldtoto.4-dat-cabin-phu-luc-6.preview') }}" enctype="multipart/form-data" id="form-phu-luc-6">
                @csrf
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="ma_khoa_hoc">Mã khóa học (DAT)</label>
                        <select class="form-control form-control-sm" id="ma_khoa_hoc" name="ma_khoa_hoc" required>
                            <option value="">— Chọn khóa —</option>
                            @foreach ($khoaHocOptions as $kh)
                                <option value="{{ $kh->MaKhoaHoc }}" @selected(old('ma_khoa_hoc') === $kh->MaKhoaHoc)>
                                    {{ $kh->TenKhoaHoc ?: $kh->MaKhoaHoc }} ({{ $kh->MaKhoaHoc }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label for="tu_ngay">Từ ngày</label>
                        <input type="date" class="form-control form-control-sm" id="tu_ngay" name="tu_ngay"
                               value="{{ old('tu_ngay') }}">
                    </div>
                    <div class="form-group col-md-2">
                        <label for="den_ngay">Đến ngày</label>
                        <input type="date" class="form-control form-control-sm" id="den_ngay" name="den_ngay"
                               value="{{ old('den_ngay') }}">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="file_danh_sach">File danh sách đạt (Excel)
                            @if (! empty($danhSachSan))
                                <span class="text-muted font-weight-normal">— tùy chọn sau khi xem trước</span>
                            @endif
                        </label>
                        <input type="file" class="form-control-file form-control-sm" id="file_danh_sach" name="file_danh_sach"
                               accept=".xlsx,.xls" @if(empty($danhSachSan)) required @endif>
                        @if (! empty($danhSachSan))
                            <small class="form-text text-success">Đã lưu danh sách từ lần xem trước — có thể xuất Excel mà không chọn lại file.</small>
                        @endif
                    </div>
                </div>
                <button type="submit" formaction="{{ route('qldtoto.4-dat-cabin-phu-luc-6.preview') }}" class="btn btn-outline-primary">Xem trước</button>
                <button type="submit" formaction="{{ route('qldtoto.4-dat-cabin-phu-luc-6.export') }}" class="btn btn-navy">Xuất Excel — Phụ lục 6</button>
            </form>

            @if (! empty($preview) && is_array($preview))
                <div class="table-responsive mt-4">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="thead-light">
                        <tr>
                            <th>STT</th>
                            <th>CCCD</th>
                            <th>Họ tên</th>
                            <th>Mã HV</th>
                            <th>Tổng giờ</th>
                            <th>Tổng km</th>
                            <th>Ghi chú</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($preview as $row)
                            <tr @class(['table-warning' => ($row['loi'] ?? '') !== ''])>
                                <td>{{ $row['stt'] }}</td>
                                <td><code>{{ $row['cccd'] }}</code></td>
                                <td>{{ $row['ho_ten'] ?: '—' }}</td>
                                <td class="small">{{ $row['ma_hoc_vien'] ?: '—' }}</td>
                                <td>{{ $row['tong_gio'] !== null ? \App\Support\DaoTao\DatHocVienTongHop::formatNumber($row['tong_gio']) : '—' }}</td>
                                <td>{{ $row['tong_km'] !== null ? \App\Support\DaoTao\DatHocVienTongHop::formatNumber($row['tong_km']) : '—' }}</td>
                                <td class="small text-danger">{{ $row['loi'] ?: 'HT' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection