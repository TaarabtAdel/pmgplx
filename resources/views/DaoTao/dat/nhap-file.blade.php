@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Nhập dữ liệu phiên DAT')

@section('content')
    <div class="card card-panel">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Nhập dữ liệu phiên DAT</span>
            <a href="{{ route('daotao.pdt.dat.quan-ly-phien') }}" class="btn btn-sm btn-outline-secondary">← Quản lý phiên</a>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                Upload file Excel xuất từ hệ thống DAT (sheet <code>DsPhienhoc_*</code>).
                File thường có 3 dòng tiêu đề cơ quan, sau đó <strong>2 dòng tiêu đề cột</strong> (dòng 4–5) rồi dữ liệu từ dòng 6.
                Hệ thống tự nhận dòng tiêu đề có <strong>STT</strong> và <strong>Mã phiên học</strong>.
            </div>

            <form method="POST" action="{{ route('daotao.pdt.dat.nhap-du-lieu-phien.store') }}" enctype="multipart/form-data">
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
