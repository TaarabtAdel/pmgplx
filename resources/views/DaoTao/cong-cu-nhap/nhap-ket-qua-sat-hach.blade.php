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
                Chỉ <strong>cập nhật</strong> học viên đã có trong
                <a href="{{ route('daotao.pdt.bc.thu-tien-hoc-vien') }}">Quản lý thu tiền học viên</a>
                (không tạo mới — cần thêm học viên vào danh sách thu tiền trước).
                <ul class="mb-0 mt-2 small">
                    <li>Lý thuyết: cột L + số lần M · Sa hình: N + O · Đường: P + Q</li>
                    <li><strong>Đ</strong> → Đậu · <strong>KĐ</strong> → Rớt · <strong>—</strong> → bỏ qua môn đó</li>
                    <li>Hạng: <code>B1</code>↔<code>B</code> (sàn) · <code>B11</code>↔<code>B.01</code> (tự động; file thường ghi <code>B</code> / <code>B01</code>)</li>
                </ul>
            </div>

            <form method="POST" action="{{ route('daotao.pdt.cong-cu-nhap.nhap-ket-qua-sat-hach.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="form-group col-md-6 px-0">
                    <label for="file">Chọn file Excel</label>
                    <input type="file" name="file" id="file" class="form-control-file" accept=".xls,.xlsx,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                </div>

                <button type="submit" class="btn btn-navy btn-lg">Xem trước dữ liệu</button>
            </form>
        </div>
    </div>
@endsection
