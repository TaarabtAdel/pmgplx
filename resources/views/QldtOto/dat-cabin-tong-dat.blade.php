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
                Mẫu <code>tong-dat-khoa.goc.xlsx</code> — báo cáo tổng DAT theo khóa.
                <strong>Tổng thời gian / quãng đường</strong> mỗi học viên = cộng <strong>tất cả</strong> phiên (cả Không đạt).
                Bảng tóm tắt hạng: cột <strong>Đủ điều kiện</strong> / <strong>Không đủ điều kiện</strong> = <strong>số phiên</strong> đạt / không đạt
                (cùng tiêu chí quản lý phiên DAT).
            </div>

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('qldtoto.4-dat-cabin-tong-dat.export') }}">
                @csrf
                <div class="form-row">
                    <div class="form-group col-md-5">
                        <label for="ma_khoa_hoc">Mã khóa học (DAT) <span class="text-danger">*</span></label>
                        <select class="form-control form-control-sm" id="ma_khoa_hoc" name="ma_khoa_hoc" required>
                            <option value="">— Chọn khóa —</option>
                            @foreach ($khoaHocOptions as $kh)
                                <option value="{{ $kh->MaKhoaHoc }}" @selected(old('ma_khoa_hoc') === $kh->MaKhoaHoc)>
                                    {{ $kh->TenKhoaHoc ?: $kh->MaKhoaHoc }} ({{ $kh->MaKhoaHoc }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="ngay_bao_cao">Ngày báo cáo</label>
                        <input type="date" class="form-control form-control-sm" name="ngay_bao_cao" id="ngay_bao_cao"
                               value="{{ old('ngay_bao_cao', $ngay_bao_cao) }}" required>
                    </div>
                    <div class="form-group col-md-2">
                        <label for="tu_ngay">Từ ngày phiên</label>
                        <input type="date" class="form-control form-control-sm" name="tu_ngay" id="tu_ngay" value="{{ old('tu_ngay') }}">
                    </div>
                    <div class="form-group col-md-2">
                        <label for="den_ngay">Đến ngày phiên</label>
                        <input type="date" class="form-control form-control-sm" name="den_ngay" id="den_ngay" value="{{ old('den_ngay') }}">
                    </div>
                </div>
                <button type="submit" class="btn btn-navy">Xuất Excel — Tổng DAT</button>
            </form>
        </div>
    </div>
@endsection