@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Nhập lịch từ file')

@section('content')
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
