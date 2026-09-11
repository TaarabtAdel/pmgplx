@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Điều kiện cảnh báo phiên DAT')

@section('content')
    <div class="card card-panel mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Điều kiện cảnh báo phiên DAT</span>
            <div>
                <a href="{{ route('daotao.pdt.dat.dieu-kien-dat') }}" class="btn btn-sm btn-outline-secondary mr-1">
                    Điều kiện đạt
                </a>
                <a href="{{ route('daotao.pdt.dat.dieu-kien-do-phien') }}" class="btn btn-sm btn-outline-secondary mr-1">
                    Điều kiện dò phiên
                </a>
                <a href="{{ route('daotao.pdt.dat.quan-ly-phien') }}" class="btn btn-sm btn-outline-secondary">← Quản lý phiên</a>
            </div>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">
                Các ngưỡng dưới đây dùng khi kiểm tra phiên tại <strong>Quản lý phiên</strong>.
                Thay đổi sẽ áp dụng ngay cho lần lọc / cảnh báo tiếp theo.
            </p>

            <form method="POST" action="{{ route('daotao.pdt.dat.dieu-kien-canh-bao.update') }}" class="mb-4">
                @csrf
                @method('PUT')

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Thời gian phiên tối thiểu (phút)</label>
                        <input type="number" name="thoi_gian_toi_thieu_phut" class="form-control"
                               min="1" max="999" required
                               value="{{ old('thoi_gian_toi_thieu_phut', $cauHinh->ThoiGianPhienToiThieuPhut) }}">
                        <small class="form-text text-muted">
                            Cảnh báo nếu phiên ngắn hơn ngưỡng này.
                            Hiện tại: <strong>{{ $loiDefinitions[\App\Support\DaoTao\DatDSPhienKiemTra::LOI_THOI_GIAN_NGAN]['label'] ?? '' }}</strong>
                        </small>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Thời gian phiên tối đa (phút)</label>
                        <input type="number" name="thoi_gian_toi_da_phut" class="form-control"
                               min="1" max="9999" required
                               value="{{ old('thoi_gian_toi_da_phut', $cauHinh->ThoiGianPhienToiDaPhut) }}">
                        <small class="form-text text-muted">
                            240 phút = 4 giờ. Cảnh báo nếu phiên dài hơn ngưỡng này.
                            Hiện tại: <strong>{{ $loiDefinitions[\App\Support\DaoTao\DatDSPhienKiemTra::LOI_THOI_GIAN_DAI]['label'] ?? '' }}</strong>
                        </small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Khoảng cách phiên liền kề (phút)</label>
                        <input type="number" name="khoang_phien_lien_ke_phut" class="form-control"
                               min="0" max="999" required
                               value="{{ old('khoang_phien_lien_ke_phut', $cauHinh->KhoangPhienLienKePhut) }}">
                        <small class="form-text text-muted">
                            Cùng một học viên: hai phiên liền kề nhau và cách nhau dưới ngưỡng này
                            → cảnh báo phiên ngắn hơn.
                            Hiện tại: <strong>{{ $loiDefinitions[\App\Support\DaoTao\DatDSPhienKiemTra::LOI_PHIEN_LIEN_KE]['label'] ?? '' }}</strong>
                        </small>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Tỉ lệ nhận diện tối thiểu (%)</label>
                        <input type="number" name="ti_le_nhan_dien_toi_thieu" class="form-control"
                               min="0" max="100" step="0.01" required
                               value="{{ old('ti_le_nhan_dien_toi_thieu', $cauHinh->TiLeNhanDienToiThieu) }}">
                        <small class="form-text text-muted">
                            Phiên đạt khi tỉ lệ ≥ ngưỡng này; dưới ngưỡng = một trong các cảnh báo khiến phiên không đạt.
                            Hiện tại: <strong>{{ $loiDefinitions[\App\Support\DaoTao\DatDSPhienKiemTra::LOI_TI_LE_ND]['label'] ?? '' }}</strong>
                        </small>
                    </div>
                </div>

                @if ($cauHinh->NgayCapNhat)
                    <p class="small text-muted mb-3">
                        Cập nhật lần cuối: {{ $cauHinh->NgayCapNhat->format('d/m/Y H:i') }}
                    </p>
                @endif

                <button type="submit" class="btn btn-navy">Lưu điều kiện</button>
            </form>

            <div class="border rounded p-3 bg-light small mb-3">
                <strong class="d-block mb-2">Cảnh báo theo phân công học viên</strong>
                <ul class="mb-2 pl-3">
                    <li>{{ $loiDefinitions[\App\Support\DaoTao\DatDSPhienKiemTra::LOI_SAI_GIAO_VIEN]['label'] ?? 'Giáo viên khác phân công HV' }}
                        — so với GV chính hoặc <a href="{{ route('daotao.pdt.dat.giao-vien-day-thay') }}">GV dạy thay</a> (theo ngày phiên).</li>
                    <li>{{ $loiDefinitions[\App\Support\DaoTao\DatDSPhienKiemTra::LOI_SAI_XE]['label'] ?? 'Xe khác phân công HV' }}</li>
                    <li>{{ $loiDefinitions[\App\Support\DaoTao\DatDSPhienKiemTra::LOI_LICH_XE]['label'] ?? 'Không khớp lịch xe tập' }}</li>
                </ul>
                <p class="mb-0 text-muted">
                    GV dạy thay khai báo tại trang
                    <a href="{{ route('daotao.pdt.dat.giao-vien-day-thay') }}">Giáo viên dạy thay</a>
                    (theo khóa + GV chính).
                    Phiên có ngày nằm trong khoảng dạy thay sẽ so mã GV phiên với mã GV dạy thay thay vì GV chính.
                </p>
            </div>

            <div class="border rounded p-3 bg-light small">
                <strong class="d-block mb-2">Cảnh báo không cấu hình tại đây</strong>
                <ul class="mb-0 pl-3">
                    <li>{{ $loiDefinitions[\App\Support\DaoTao\DatDSPhienKiemTra::LOI_TRUNG_HV]['label'] ?? 'Học viên trùng khung giờ' }}</li>
                    <li>{{ $loiDefinitions[\App\Support\DaoTao\DatDSPhienKiemTra::LOI_TRUNG_GV]['label'] ?? 'Giáo viên trùng khung giờ' }}</li>
                </ul>
            </div>
        </div>
    </div>
@endsection
