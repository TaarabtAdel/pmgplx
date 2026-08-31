@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Nhập học viên mới từ file')

@section('content')
    <div class="card card-panel mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <span>Hướng dẫn sử dụng</span>
            <a href="{{ route('pmgplx.dm.hoc-vien.index') }}" class="btn btn-sm btn-outline-secondary">← Danh sách học viên</a>
        </div>
        <div class="card-body pb-2">
            <p class="mb-2">
                Chức năng nhập học viên mới từ file Excel — xem trước dữ liệu trước khi ghi vào phần mềm.
            </p>
            <ol class="mb-3 pl-3">
                <li class="mb-2">Chuẩn bị file Excel theo mẫu (sẽ cập nhật sau khi nhận file mẫu).</li>
                <li class="mb-2">Chọn file và bấm <strong>Xem trước kết quả</strong>.</li>
                <li class="mb-2">Kiểm tra dữ liệu đọc từ Excel trên màn xem trước.</li>
                <li class="mb-2">Sau khi mapping cột được cấu hình, sẽ có bước <strong>Xác nhận lưu</strong> vào <code>NguoiLX</code> / <code>NguoiLX_HoSo</code>.</li>
            </ol>
            <div class="alert alert-info mb-0">
                <strong>Đang chờ file mẫu:</strong> Hiện mới đọc và hiển thị thô nội dung Excel.
                Gửi file mẫu để hoàn thiện mapping cột và lưu học viên.
            </div>
        </div>
    </div>

    <div class="card card-panel">
        <div class="card-header">Nhập học viên mới từ file</div>
        <div class="card-body">
            <form method="POST" action="{{ route('pmgplx.dm.hoc-vien.nhap-file.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="form-group col-md-6 px-0">
                    <label for="file">Chọn file Excel</label>
                    <input type="file" name="file" id="file" class="form-control-file" accept=".xls,.xlsx,.csv" required>
                    <small class="form-text text-muted">Hỗ trợ .xls, .xlsx, .csv — tối đa 10 MB.</small>
                </div>

                <button type="submit" class="btn btn-navy btn-lg">Xem trước kết quả</button>
            </form>
        </div>
    </div>
@endsection
