@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Danh sách phân công')

@section('content')
    <div class="card card-panel">
        <div class="card-header">Danh sách phân công đào tạo</div>
        <div class="card-body">
            <form method="GET" action="{{ route('daotao.pdt.phan-cong-dao-tao.danh-sach') }}">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-3">
                        <label for="ten_khoa">Khoá đào tạo</label>
                        <select class="form-control form-control-sm" id="ten_khoa" name="ten_khoa">
                            <option value="" @selected($filters['ten_khoa'] === '')>Tất cả</option>
                            @foreach ($khoaOptions as $tenKhoa)
                                <option value="{{ $tenKhoa }}" @selected($filters['ten_khoa'] === $tenKhoa)>{{ $tenKhoa }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="ho_ten">Giáo viên</label>
                        <input type="text"
                               class="form-control form-control-sm"
                               id="ho_ten"
                               name="ho_ten"
                               value="{{ $filters['ho_ten'] }}"
                               placeholder="Tên giáo viên">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="bien_so">Biển số xe</label>
                        <input type="text"
                               class="form-control form-control-sm"
                               id="bien_so"
                               name="bien_so"
                               value="{{ $filters['bien_so'] }}"
                               placeholder="74A-246.00">
                    </div>
                    <div class="form-group col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary btn-block">Lọc</button>
                        <a href="{{ route('daotao.pdt.phan-cong-dao-tao.danh-sach') }}"
                           class="btn btn-sm btn-outline-secondary btn-block mt-1"
                           title="Làm mới">↻</a>
                    </div>
                </div>
            </form>

            <div class="table-responsive mt-3">
                <table class="table table-sm table-bordered table-striped table-hover table-data mb-0">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Khoá</th>
                            <th>Giáo viên</th>
                            <th>Thời gian</th>
                            <th>Biển số xe</th>
                            <th>Nội dung giảng dạy</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $row)
                            <tr>
                                <td>{{ $row->SoTT ?? '—' }}</td>
                                <td><strong>{{ $row->khoaDaoTao?->TenKhoa ?? '—' }}</strong></td>
                                <td>{{ $row->giaoVien?->HoTen ?? '—' }}</td>
                                <td>
                                    @if ($row->TuNgay && $row->DenNgay)
                                        {{ $row->TuNgay->format('d/m/Y') }} – {{ $row->DenNgay->format('d/m/Y') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $row->xeTapLai?->BienSo ?? '—' }}</td>
                                <td>{{ $row->NoiDungGiangDay ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">Chưa có dữ liệu phân công</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($items->hasPages())
                <div class="mt-3">
                    {{ $items->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
