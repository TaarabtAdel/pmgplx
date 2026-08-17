@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Nhập file in bảng tên')

@section('content')
    <div class="card card-panel">
        <div class="card-header">Nhập file in bảng tên học viên</div>
        <div class="card-body">
            <div class="alert alert-info">
                Upload file XML đăng ký khoá học (<code>DANG_KY_KHOA_HOC</code> — vd. file DKKH từ hệ thống).
                Hệ thống đọc ảnh chân dung, hạng GPLX và tên học viên để tạo khung bảng tên in hàng loạt.
                File thường khoảng 3–8 MB (do ảnh học viên nhúng trong XML).
            </div>

            <form method="POST" action="{{ route('daotao.pdt.cong-cu-nhap.nhap-file-in-bang-ten.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="form-group col-md-6 px-0">
                    <label for="file">Chọn file XML</label>
                    <input type="file" name="file" id="file" class="form-control-file" accept=".xml,text/xml" required>
                </div>

                <button type="submit" class="btn btn-navy btn-lg">Tạo bảng tên</button>
            </form>
        </div>
    </div>
@endsection
