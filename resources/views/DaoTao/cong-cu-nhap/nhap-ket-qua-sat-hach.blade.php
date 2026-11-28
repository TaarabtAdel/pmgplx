@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Nhập kết quả sát hạch')

@section('content')
    <div class="card card-panel">
        <div class="card-header">Nhập kết quả sát hạch</div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="alert alert-info">
                Upload file <strong>BÁO CÁO CHI TIẾT - KẾT QUẢ SÁT HẠCH.xlsx</strong>.
                Hệ thống xem trước <strong>tất cả</strong> dòng, đối chiếu <strong>Số định danh</strong> (cột F) với <strong>Số CMT</strong>
                và <strong>Hạng</strong> (cột I) với PMGPLX (<code>HangGPLX</code> / <code>HangDaoTao</code>), giống
                <a href="{{ route('pmgplx.dm.hoc-vien.index') }}">Danh mục học viên</a>.
                Chọn nơi ghi: <strong>Quản lý thu tiền</strong> (cột L–Q từng môn) hoặc
                <strong>Danh sách cập nhật GPLX</strong> (cột R «Tổng hợp» → cột Kết quả).
                Chỉ cập nhật học viên đã có trong danh sách đã chọn (không tạo mới).
                <ul class="mb-0 mt-2 small">
                    <li>Thu tiền — Lý thuyết: cột L + số lần M · Sa hình: N + O · Đường: P + Q</li>
                    <li>GPLX — cột <strong>R Tổng hợp</strong> ghi nguyên văn vào cột Kết quả</li>
                    <li><strong>Đ</strong> → Đậu · <strong>KĐ</strong> → Rớt · <strong>—</strong> → bỏ qua môn đó (khi cập nhật thu tiền)</li>
                    <li>Hạng: <code>B1</code>↔<code>B</code> (sàn) · <code>B11</code>↔<code>B.01</code> (tự động; file thường ghi <code>B</code> / <code>B01</code>)</li>
                </ul>
            </div>

            <p class="mb-3">
                <a href="{{ route('daotao.pdt.cong-cu-nhap.nhap-ket-qua-sat-hach.mau') }}" class="btn btn-outline-primary btn-sm">
                    Tải file mẫu (.xlsx)
                </a>
                <span class="text-muted small ml-2">Cấu trúc cột A, B, C, F, I, L–R — có 3 dòng ví dụ.</span>
            </p>

            <form method="POST" action="{{ route('daotao.pdt.cong-cu-nhap.nhap-ket-qua-sat-hach.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="form-group">
                    <label class="d-block">Cập nhật vào <span class="text-danger">*</span></label>
                    <div class="custom-control custom-radio">
                        <input type="radio" class="custom-control-input" id="dich_thu_tien" name="dich" value="thu_tien" @checked(old('dich', 'thu_tien') === 'thu_tien') required>
                        <label class="custom-control-label" for="dich_thu_tien">Quản lý thu tiền học viên</label>
                    </div>
                    <div class="custom-control custom-radio">
                        <input type="radio" class="custom-control-input" id="dich_gplx" name="dich" value="cap_nhat_gplx" @checked(old('dich') === 'cap_nhat_gplx')>
                        <label class="custom-control-label" for="dich_gplx">Danh sách cập nhật GPLX học viên — cột Kết quả (Tổng hợp, cột R)</label>
                    </div>
                </div>

                <div class="form-group col-md-6 px-0">
                    <label for="file">Chọn file Excel</label>
                    <input type="file" name="file" id="file" class="form-control-file" accept=".xls,.xlsx,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                </div>

                <button type="submit" class="btn btn-navy btn-lg">Xem trước dữ liệu</button>
            </form>
        </div>
    </div>
@endsection
