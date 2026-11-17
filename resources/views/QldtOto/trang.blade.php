@extends('PMGPLX.layouts.quan-ly')

@section('title', $item['title'])

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-white border py-2 small mb-3">
            <li class="breadcrumb-item"><a href="{{ route('qldtoto.index') }}">QLĐT Ô TÔ</a></li>
            @foreach (array_slice($duongDan, 1) as $buoc)
                <li class="breadcrumb-item">{{ $buoc }}</li>
            @endforeach
            <li class="breadcrumb-item active" aria-current="page">{{ $item['title'] }}</li>
        </ol>
    </nav>

    <div class="card card-panel">
        <div class="card-header">{{ $item['title'] }}</div>
        <div class="card-body">
            @if (! empty($item['mo_ta']))
                <div class="alert alert-info py-2">
                    <strong>Ghi chú:</strong> {{ $item['mo_ta'] }}
                </div>
            @endif
            @if (($slug ?? '') === '3-danh-sach-thi-sinh-du-sat-hach')
                <p class="mb-3">
                    Danh sách thí sinh dự sát hạch lấy từ <strong>PMGPLX bản cũ</strong>.
                    Vào danh mục học viên bản cũ, lọc theo khóa (và điều kiện khác nếu cần), rồi dùng
                    <strong>Xuất Excel</strong> để tải file.
                </p>
                <a href="{{ route('pmgplxold.dm.hoc-vien.index') }}" class="btn btn-sm btn-success">
                    ⬇ Mở danh mục học viên (bản cũ) — xuất Excel
                </a>
            @elseif (($slug ?? '') === '2-bang-ten-de-in')
                <p class="mb-3">
                    Xuất file bảng tên học viên để in: upload file Excel danh sách, xem trước thẻ tên rồi in
                    qua công cụ <strong>Nhập file in bảng tên</strong> (Phòng đào tạo).
                </p>
                <a href="{{ route('daotao.pdt.cong-cu-nhap.nhap-file-in-bang-ten') }}" class="btn btn-sm btn-navy">
                    Mở nhập file in bảng tên
                </a>
            @elseif (($slug ?? '') === '2-nhap-diem-thoi-gian-quang-duong-ket-qua-dao-tao-tung-hoc')
                <p class="mb-3">
                    Nhập điểm, thời gian, quãng đường và kết quả đào tạo từng học viên vào phần mềm PMGPLX
                    qua công cụ <strong>Nhập kết quả đào tạo</strong> (Phòng đào tạo).
                </p>
                <a href="{{ route('daotao.pdt.cong-cu-nhap.nhap-ket-qua-dao-tao') }}" class="btn btn-sm btn-navy">
                    Mở nhập kết quả đào tạo
                </a>
            @elseif (($slug ?? '') === '1-danh-sach-lop-hoc')
                <p class="mb-3">
                    Xuất file <strong>Danh sách lớp học</strong>: vào <strong>Danh mục học viên</strong> trên PMGPLX,
                    lọc theo khóa (và điều kiện khác nếu cần), chọn cột rồi dùng <strong>Xuất Excel</strong>.
                </p>
                <a href="{{ route('pmgplx.dm.hoc-vien.index') }}" class="btn btn-sm btn-success">
                    Mở PMGPLX — Danh mục học viên (xuất Excel)
                </a>
            @elseif (($slug ?? '') === '2-nhap-lich-vao-phan-mem')
                <p class="mb-3">
                    Nhập lịch thực hành (file Excel) vào PMGPLX qua công cụ <strong>Nhập lịch từ file</strong>.
                </p>
                <a href="{{ route('pmgplx.lich.nhap-file.create') }}" class="btn btn-sm btn-navy">
                    Mở nhập lịch từ file
                </a>
            @elseif (($slug ?? '') === '1-danh-sach-hoc-vien-hoc-tap-trung-lop-ly-thuyet')
                <p class="mb-3">
                    Bữa nay đã chuyển sang <strong>học lý thuyết online</strong>, không còn cần xuất file
                    <em>Danh sách học viên học tập trung lớp lý thuyết</em> tại đây.
                </p>
                <p class="text-muted small mb-3">
                    Nếu cần xuất danh sách học viên (lọc theo khóa, chọn cột, Xuất Excel), dùng
                    <strong>Danh mục học viên</strong> trên PMGPLX.
                </p>
                <a href="{{ route('pmgplx.dm.hoc-vien.index') }}" class="btn btn-sm btn-success">
                    Mở PMGPLX — Danh mục học viên
                </a>
            @else
                <p class="text-muted mb-0">Màn hình đang được xây dựng.</p>
            @endif
        </div>
    </div>
@endsection
