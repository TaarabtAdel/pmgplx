@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Tạo lịch phân công TH')

@section('content')
    <div class="card card-panel">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Tạo lịch phân công giảng dạy thực hành</span>
            <a href="{{ route('daotao.pdt.dat.lich-thuc-hanh.create') }}" class="btn btn-sm btn-primary">+ Dự án mới</a>
        </div>
        <div class="card-body">
            <p class="small text-muted">
                Sinh lịch theo cấu hình (giáo viên · xe · chương trình giờ · nghỉ · cabin · xe tự động),
                đối chiếu <a href="{{ route('daotao.pdt.dat.dieu-kien-dat') }}">Điều kiện đạt DAT</a>, xuất Excel (layout 7 cột/GV).
            </p>
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th>Mã khóa</th>
                        <th>Hạng</th>
                        <th>Khai giảng</th>
                        <th>Kết thúc dự kiến</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($duAnList as $row)
                        <tr>
                            <td>{{ $row->MaKhoaHoc }}</td>
                            <td>{{ $row->HangDaoTao }}</td>
                            <td>{{ $row->NgayKhaiGiang?->format('d/m/Y') }}</td>
                            <td>{{ $row->NgayKetThucDuKien?->format('d/m/Y') ?? '—' }}</td>
                            <td>
                                <a href="{{ route('daotao.pdt.dat.lich-thuc-hanh.edit', $row->Id) }}">Mở</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">Chưa có dự án lịch.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
