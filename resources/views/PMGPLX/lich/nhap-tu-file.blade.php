@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Nhập lịch từ file')

@section('content')
    <div class="card card-panel mb-3">
        <div class="card-header bg-light">Hướng dẫn sử dụng</div>
        <div class="card-body pb-2">
            <p class="mb-2">
                Chức năng nhập lịch thực hành từ file Excel theo mẫu PMGPLX, qua các bước xem trước trước khi ghi vào database.
            </p>
            <ol class="mb-3 pl-3">
                <li class="mb-2">
                    Chuẩn bị file Excel đúng mẫu: mỗi giáo viên chiếm <strong>5 cột</strong>
                    (Thời gian, Nội dung, Chi tiết, Bắt đầu, Kết thúc).
                    Dòng header có <strong>tên GV + biển số xe</strong> và <strong>mã GV + mã khóa học</strong>.
                </li>
                <li class="mb-2">
                    Chọn file (<code>.xls</code>, <code>.xlsx</code>, <code>.csv</code>) và bấm <strong>Xem trước kết quả</strong>.
                </li>
                <li class="mb-2">
                    Kiểm tra dữ liệu đọc từ Excel — dòng nền vàng là <strong>ngày nghỉ</strong> (Nội dung và Chi tiết trống), sẽ không lưu.
                    Bấm <strong>Xem trước lưu vào phần mềm</strong> để sang bước tiếp theo.
                </li>
                <li class="mb-2">
                    Màn <strong>Lịch giáo viên</strong>: sửa giờ nếu cần (định dạng <strong>24 giờ</strong>, vd. <code>05:59</code>).
                    Dòng trùng lịch có thể bật <strong>Chế độ cập nhật</strong> để ghi đè. Tiếp tục sang lịch xe.
                </li>
                <li class="mb-2">
                    Màn <strong>Lịch xe tập lái</strong>: kiểm tra biển số và địa điểm.
                    Dòng có <strong>Tự động</strong> (nền tím) cần <strong>dò lại xe tập</strong> trước khi tiếp tục.
                </li>
                <li class="mb-2">
                    Màn <strong>Xem trước DB</strong>: kiểm tra lần cuối, bấm <strong>Xác nhận lưu DB</strong> để ghi vào phần mềm.
                </li>
            </ol>
            <div class="alert alert-warning mb-0">
                <strong>Lưu ý:</strong>
                Mã giáo viên, mã khóa học và xe tập trong file phải <strong>đã có sẵn</strong> trong phần mềm.
                Dòng <strong>Bổ Sung</strong> / <strong>CABIN</strong> sẽ không lưu lịch tương ứng.
                Có thể <strong>Hủy</strong> hoặc quay lại chọn file bất cứ lúc nào trong quá trình xem trước.
            </div>
        </div>
    </div>

    <div class="card card-panel">
        <div class="card-header">Nhập lịch từ file</div>
        <div class="card-body">
            <form method="POST" action="{{ route('pmgplx.lich.nhap-file.store') }}" enctype="multipart/form-data">
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
