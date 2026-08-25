@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Nhập file sổ phân công giáo viên')

@section('content')
    <div class="card card-panel">
        <div class="card-header">Nhập file sổ phân công giáo viên</div>
        <div class="card-body">
            <div class="alert alert-info">
                Upload file Excel sổ phân công (cột: Số TT, Giáo viên, Thời gian, Khoá đào tạo, Biển số xe, Nội dung giảng dạy).
                Dữ liệu lưu vào DB <strong>MANHLINH</strong> (bảng <code>GiaoVien</code>, <code>XeTapLai</code>, <code>KhoaDaoTao</code>, <code>PhanCongDaoTao</code>).
                Chỉ lưu dòng có <strong>giáo viên</strong>; nội dung map thành <code>ly_thuyet</code> / <code>thuc_hanh</code> (dòng “tự động” bỏ qua).
                Nhập lại cùng khoá sẽ <strong>cập nhật</strong> dòng trùng khóa
                (cùng giáo viên + loại giảng dạy + xe), kể cả khi đổi thời gian.
            </div>

            <form method="POST" action="{{ route('daotao.pdt.cong-cu-nhap.nhap-file-so-phan-cong-giao-vien.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="form-group col-md-6 px-0">
                    <label for="file">Chọn file Excel</label>
                    <input type="file" name="file" id="file" class="form-control-file" accept=".xls,.xlsx" required>
                </div>

                <button type="submit" class="btn btn-navy btn-lg">Xem trước dữ liệu</button>
            </form>
        </div>
    </div>
@endsection
