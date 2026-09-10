@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Nhập kết quả cục')

@section('content')
    <div class="card card-panel">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Nhập kết quả cục</span>
            <a href="{{ route('daotao.pdt.dat.quan-ly-phien') }}" class="btn btn-sm btn-outline-secondary">← Quản lý phiên</a>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                Upload file Excel kết quả truyền lên cục (sheet có tiêu đề <strong>Mã phiên học</strong> ở cột B).
                Hệ thống đọc <strong>mã khóa học</strong> (cột K), <strong>mã phiên</strong> (cột B) và <strong>trạng thái</strong> (cột S).
                Một file có thể chứa <strong>nhiều khóa học</strong> — hệ thống xử lý tất cả cùng lúc.
                <ul class="mb-0 mt-2">
                    <li>Trạng thái có <strong>Khả dụng</strong> → phân loại <strong>Đã truyền lên cục</strong></li>
                    <li>Trạng thái khác <strong>Khả dụng</strong>, hoặc phiên của khóa không có trong file → <strong>Cục không chấp nhận</strong></li>
                </ul>
            </div>

            <form method="POST" action="{{ route('daotao.pdt.dat.nhap-ket-qua-cuc.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="form-group col-md-6 px-0">
                    <label for="file">Chọn file Excel</label>
                    <input type="file" name="file" id="file" class="form-control-file" accept=".xls,.xlsx,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                </div>

                <button type="submit" class="btn btn-navy btn-lg">Xem trước 5 dòng đầu</button>
            </form>
        </div>
    </div>
@endsection
