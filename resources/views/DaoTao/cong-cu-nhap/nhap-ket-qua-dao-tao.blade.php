@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Nhập kết quả đào tạo')

@section('content')
    <div class="card card-panel">
        <div class="card-header">Nhập kết quả đào tạo</div>
        <div class="card-body">
            <div class="alert alert-info">
                Upload file Excel kết quả đào tạo (tiêu đề cột B = <strong>Mã học viên</strong>, dữ liệu từ dòng sau header).
                Hệ thống xem trước 5 dòng file, sau đó đối chiếu <code>NguoiLX_HoSo</code> theo mã HV và hiển thị các cột sẽ cập nhật.
                Cột P/Q trên file <strong>không dùng</strong>: giờ đường / km đường lấy từ
                <a href="{{ route('daotao.pdt.dat.theo-doi') }}">Theo dõi DAT</a>
                (Tổng giờ máy chủ / Tổng KM máy chủ, chỉ phiên đạt).
                <strong>Kết luận CSDT</strong> = Đạt khi đủ ngưỡng hình + giờ/km DAT theo hạng B sàn / B tự động / C1.
            </div>

            <form method="POST" action="{{ route('daotao.pdt.cong-cu-nhap.nhap-ket-qua-dao-tao.store') }}" enctype="multipart/form-data">
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
