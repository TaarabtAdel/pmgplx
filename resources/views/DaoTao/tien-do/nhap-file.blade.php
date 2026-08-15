@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Nhập file tiến độ đào tạo')

@section('content')
    <div class="card card-panel">
        <div class="card-header">Nhập file tiến độ đào tạo</div>
        <div class="card-body">
            <div class="alert alert-info">
                Upload file Excel tiến độ đào tạo (vd. <code>TIE__N_ĐO___ĐA_O_TA_O.xlsx</code>).
                Hệ thống sẽ đọc từng sheet, parse dữ liệu lớp và tuần, rồi hiển thị xem trước trước khi lưu DB.
            </div>

            <form method="POST" action="{{ route('daotao.pdt.td.nhap-file.store') }}" enctype="multipart/form-data">
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
