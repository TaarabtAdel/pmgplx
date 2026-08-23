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
                    <div class="list-group-item list-group-item-light small font-weight-bold py-2">Công cụ nhập</div>
                    <a href="{{ route('daotao.pdt.cong-cu-nhap.nhap-file-tien-do-dao-tao') }}" class="list-group-item list-group-item-action pl-4">
                        Nhập file tiến độ đào tạo
                    </a>
                    <a href="{{ route('daotao.pdt.cong-cu-nhap.nhap-file-so-phan-cong-giao-vien') }}" class="list-group-item list-group-item-action pl-4">
                        Nhập file sổ phân công giáo viên
                    </a>
                    <a href="{{ route('daotao.pdt.cong-cu-nhap.nhap-file-in-bang-ten') }}" class="list-group-item list-group-item-action pl-4">
                        Nhập file in bảng tên
                    </a>
                    <div class="list-group-item list-group-item-light small font-weight-bold py-2">Phân Công Đào Tạo</div>
                    <a href="{{ route('daotao.pdt.phan-cong-dao-tao.danh-sach') }}" class="list-group-item list-group-item-action pl-4">
                        Danh sách phân công
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-3">
            <div class="card card-panel h-100">
                <div class="card-header">Trung Tâm</div>
                <div class="list-group list-group-flush">
                    <a href="{{ route('trungtam.giao-vien.danh-sach') }}" class="list-group-item list-group-item-action">
                        Giáo viên
                    </a>
                    <a href="{{ route('trungtam.xe-tap-lai.danh-sach') }}" class="list-group-item list-group-item-action">
                        Xe tập lái
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
