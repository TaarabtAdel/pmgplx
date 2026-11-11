@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Nhập SDT / Sale học viên từ file')

@section('content')
    <div class="card card-panel">
        <div class="card-header">Nhập file cập nhật SDT và Sale</div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="alert alert-info">
                Upload file Excel (vd. <strong>DANH SÁCH MỜI HỌC VIÊN VÀO NHÓM LỚP.xlsx</strong>).
                Hệ thống đọc <strong>tất cả sheet</strong>, lấy:
                <ul class="mb-2 mt-2">
                    <li><strong>Cột F</strong> — CCCD</li>
                    <li><strong>Cột G</strong> — SDT</li>
                    <li><strong>Cột H</strong> — Sale</li>
                </ul>
                Đối chiếu CCCD với <strong>PMGPLX</strong> (<code>NguoiLX.SoCMT</code>) rồi ghi vào bảng phụ
                <code>HocVienThongTinPhu</code> (DB MANHLINH) — cùng dữ liệu hiển thị tại
                <a href="{{ route('pmgplx.dm.hoc-vien.index') }}">Danh mục học viên → cột SDT · Sale</a>.
            </div>

            <form method="POST" action="{{ route('daotao.pdt.cong-cu-nhap.nhap-file-sdt-sale-hoc-vien.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="form-group col-md-6 px-0">
                    <label for="file">Chọn file Excel</label>
                    <input type="file" name="file" id="file" class="form-control-file" accept=".xls,.xlsx" required>
                </div>

                <button type="submit" class="btn btn-navy btn-lg">Xem trước dữ liệu</button>
                <a href="{{ route('pmgplx.dm.hoc-vien.index') }}" class="btn btn-outline-secondary btn-lg ml-2">← Danh mục học viên</a>
            </form>
        </div>
    </div>
@endsection
