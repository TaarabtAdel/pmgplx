@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Trang chủ')

@section('content')
    <div class="row">
        <div class="col-lg-4 mb-3">
            <div class="card card-panel h-100">
                <div class="card-header">Phần Mềm Giấy Phép Lái Xe</div>
                <div class="list-group list-group-flush">
                    <a href="{{ route('pmgplx.dm.giao-vien.index') }}" class="list-group-item list-group-item-action">
                        Quản lý giáo viên
                    </a>
                    <a href="{{ route('pmgplx.dm.hoc-vien.index') }}" class="list-group-item list-group-item-action">
                        Quản lý học viên
                    </a>
                    <a href="{{ route('pmgplx.dm.xe.index') }}" class="list-group-item list-group-item-action">
                        Quản lý xe
                    </a>
                    <a href="{{ route('pmgplx.lich.gv.index') }}" class="list-group-item list-group-item-action">
                        Lịch làm việc giáo viên
                    </a>
                    <a href="{{ route('pmgplx.lich.xe.index') }}" class="list-group-item list-group-item-action">
                        Lịch sử dụng xe tập lái
                    </a>
                    <a href="{{ route('pmgplxold.dm.hoc-vien.index') }}" class="list-group-item list-group-item-action">
                        Học viên (bản cũ)
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-3">
            <div class="card card-panel h-100">
                <div class="card-header">Phòng Đào Tạo</div>
                <div class="list-group list-group-flush">
                    <div class="list-group-item list-group-item-light small font-weight-bold py-2">Báo cáo</div>
                    <a href="{{ route('daotao.pdt.bc.luu-luong-dao-tao') }}" class="list-group-item list-group-item-action pl-4">
                        Báo cáo Lưu lượng đào tạo
                    </a>
                    <div class="list-group-item list-group-item-light small font-weight-bold py-2">Tiến độ đào tạo</div>
                    <a href="{{ route('daotao.pdt.td.nhap-file') }}" class="list-group-item list-group-item-action pl-4">
                        Nhập file tiến độ đào tạo
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
