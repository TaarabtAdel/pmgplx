@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Nhập phân công học viên')

@section('content')
    <div class="card card-panel">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Nhập phân công học viên</span>
            <div>
                <a href="{{ route('daotao.pdt.dat.phan-cong-hoc-vien') }}" class="btn btn-sm btn-outline-primary mr-1">Xem phân công</a>
                <a href="{{ route('daotao.pdt.dat.quan-ly-phien') }}" class="btn btn-sm btn-outline-secondary">← Quản lý phiên</a>
            </div>
        </div>
        <div class="card-body">
            <div class="alert alert-info mb-3">
                Upload file Excel phân học viên theo giáo viên. <strong>Mã khóa học</strong> đọc từ <strong>ô A1</strong>
                (ví dụ: <code>44007K261008</code> hoặc tiêu đề dạng <em>… KHÓA BK55</em>).
                <ul class="mb-0 mt-2">
                    <li>Cột: STT · Họ tên · Mã học viên · <strong>Xe tập lái</strong> · <strong>Xe tự động</strong> · Mã giáo viên</li>
                    <li><strong>Mã HV trùng</strong> trong file hoặc đã có trong khóa → <strong>cập nhật</strong> (giữ dòng cuối)</li>
                    <li>Học viên không còn trong file sẽ bị xóa khỏi phân công khóa đó khi xác nhận</li>
                    <li>Sửa từng dòng: dùng nút <strong>Sửa</strong> trên màn <a href="{{ route('daotao.pdt.dat.phan-cong-hoc-vien') }}">Xem phân công</a></li>
                </ul>
            </div>

            <form method="POST" action="{{ route('daotao.pdt.dat.nhap-phan-cong-hoc-vien.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="form-group col-md-6 px-0">
                    <label for="file">Chọn file Excel</label>
                    <input type="file" name="file" id="file" class="form-control-file"
                           accept=".xls,.xlsx,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                </div>

                <button type="submit" class="btn btn-navy btn-lg">Xem trước</button>
            </form>
        </div>
    </div>
@endsection
