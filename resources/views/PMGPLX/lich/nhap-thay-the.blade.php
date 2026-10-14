@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Nhập lịch thay thế')

@section('content')
    @include('PMGPLX.lich.partials.nhap-lich-import-routes')

    <div class="card card-panel mb-3">
        <div class="card-header bg-light">Hướng dẫn — Nhập lịch thay thế</div>
        <div class="card-body pb-2">
            <p class="mb-2">
                Cùng mẫu Excel và các bước xem trước như <a href="{{ route('pmgplx.lich.nhap-file.create') }}">Nhập lịch từ file</a>,
                nhưng <strong>không xóa cả khóa</strong>.
            </p>
            <ol class="mb-3 pl-3">
                <li class="mb-2">File Excel giống nhập lịch thường (7 cột / giáo viên).</li>
                <li class="mb-2">Xem trước và chỉnh sửa lịch GV, lịch xe như bình thường.</li>
                <li class="mb-2">
                    Khi lưu: buổi file trùng <strong>cùng khóa + cùng khung TG bắt đầu/kết thúc</strong> với lịch đang có
                    → <strong>cập nhật giáo viên, xe, ghi chú</strong> (và môn GV nếu sửa trên preview).
                </li>
                <li class="mb-2">Buổi không trùng khung giờ trên khóa → <strong>thêm mới</strong>. Buổi cũ không có trong file → <strong>giữ nguyên</strong>.</li>
            </ol>
            <div class="alert alert-info mb-0">
                Vẫn chặn trùng lịch GV <strong>giữa các khóa</strong>. Trùng xe/GV với khóa khác (cùng khung giờ) sẽ bỏ qua dòng đó.
            </div>
        </div>
    </div>

    <div class="card card-panel">
        <div class="card-header">Nhập lịch thay thế từ file</div>
        <div class="card-body">
            <div class="alert alert-primary py-2 mb-3">
                Chỉ <strong>thay GV và xe</strong> (và ghi chú) vào các buổi trùng khung thời gian trên cùng khóa; không xóa toàn bộ lịch khóa.
                Muốn <strong>thay hết lịch khóa</strong> theo file (xóa buổi cũ không có trong file),
                dùng <a href="{{ route('pmgplx.lich.nhap-file.create') }}" class="alert-link font-weight-bold">Nhập lịch từ file</a>.
            </div>
            <form method="POST" action="{{ $nhapLichRoutes['store'] }}" enctype="multipart/form-data">
                @csrf

                <div class="form-group col-md-6 px-0">
                    <label for="file">Chọn file Excel</label>
                    <input type="file" name="file" id="file" class="form-control-file" accept=".xls,.xlsx,.csv" required>
                </div>

                <button type="submit" class="btn btn-navy btn-lg">Xem trước kết quả</button>
            </form>
        </div>
    </div>
@endsection
