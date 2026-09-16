@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Trang chủ')

@section('content')
    <div class="row">
        <div class="col-lg-3 mb-3">
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

        <div class="col-lg-3 mb-3">
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
                    <a href="{{ route('daotao.pdt.cong-cu-nhap.nhap-ket-qua-dao-tao') }}" class="list-group-item list-group-item-action pl-4">
                        Nhập kết quả đào tạo
                    </a>
                    <a href="{{ route('daotao.pdt.cong-cu-nhap.nhap-xml-bien-ban') }}" class="list-group-item list-group-item-action pl-4">
                        Nhập XML xuất biên bản
                    </a>
                    <div class="list-group-item list-group-item-light small font-weight-bold py-2">Phân Công Đào Tạo</div>
                    <a href="{{ route('daotao.pdt.phan-cong-dao-tao.danh-sach') }}" class="list-group-item list-group-item-action pl-4">
                        Danh sách phân công
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-3 mb-3">
            <div class="card card-panel h-100">
                <div class="card-header">DAT</div>
                <div class="list-group list-group-flush">
                    <div class="list-group-item list-group-item-light small font-weight-bold py-2">Tổng hợp &amp; theo dõi</div>
                    <a href="{{ route('daotao.pdt.dat.tong-hop-hoc-vien') }}" class="list-group-item list-group-item-action pl-4">
                        Tổng hợp học viên
                    </a>
                    <a href="{{ route('daotao.pdt.dat.theo-doi') }}" class="list-group-item list-group-item-action pl-4">
                        Theo dõi DAT
                    </a>
                    <div class="list-group-item list-group-item-light small font-weight-bold py-2">Quản lý phiên</div>
                    <a href="{{ route('daotao.pdt.dat.quan-ly-phien') }}" class="list-group-item list-group-item-action pl-4">
                        Chi tiết phiên
                    </a>
                    <a href="{{ route('daotao.pdt.dat.phan-loai-phien') }}" class="list-group-item list-group-item-action pl-4">
                        Phân loại phiên
                    </a>
                    <div class="list-group-item list-group-item-light small font-weight-bold py-2">Dò phiên</div>
                    <a href="{{ route('daotao.pdt.dat.do-phien-tuyen-duong') }}" class="list-group-item list-group-item-action pl-4">
                        Dò tuyến đường
                    </a>
                    <a href="{{ route('daotao.pdt.dat.do-phien-anh') }}" class="list-group-item list-group-item-action pl-4">
                        Dò ảnh
                    </a>
                    <a href="{{ route('daotao.pdt.dat.do-phien-lich-xe') }}" class="list-group-item list-group-item-action pl-4">
                        Dò phiên với lịch xe
                    </a>
                    <div class="list-group-item list-group-item-light small font-weight-bold py-2">Phân công</div>
                    <a href="{{ route('daotao.pdt.dat.phan-cong-hoc-vien') }}" class="list-group-item list-group-item-action pl-4">
                        Phân công học viên
                    </a>
                    <a href="{{ route('daotao.pdt.dat.giao-vien-day-thay') }}" class="list-group-item list-group-item-action pl-4">
                        Giáo viên dạy thay
                    </a>
                    <a href="{{ route('daotao.pdt.dat.nhap-phan-cong-hoc-vien') }}" class="list-group-item list-group-item-action pl-4">
                        Nhập phân công học viên
                    </a>
                    <div class="list-group-item list-group-item-light small font-weight-bold py-2">Nhập liệu</div>
                    <a href="{{ route('daotao.pdt.dat.nhap-du-lieu-phien') }}" class="list-group-item list-group-item-action pl-4">
                        Nhập dữ liệu phiên
                    </a>
                    <a href="{{ route('daotao.pdt.dat.nhap-ket-qua-cuc') }}" class="list-group-item list-group-item-action pl-4">
                        Nhập kết quả cục
                    </a>
                    <div class="list-group-item list-group-item-light small font-weight-bold py-2">Cấu hình</div>
                    <a href="{{ route('daotao.pdt.dat.dieu-kien-canh-bao') }}" class="list-group-item list-group-item-action pl-4">
                        Điều kiện cảnh báo
                    </a>
                    <a href="{{ route('daotao.pdt.dat.dieu-kien-do-phien') }}" class="list-group-item list-group-item-action pl-4">
                        Điều kiện dò phiên
                    </a>
                    <a href="{{ route('daotao.pdt.dat.dieu-kien-dat') }}" class="list-group-item list-group-item-action pl-4">
                        Điều kiện đạt
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-3 mb-3">
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
