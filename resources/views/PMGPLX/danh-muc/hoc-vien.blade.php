@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Quản lý học viên')

@section('content')
    <div class="card card-panel">
        <div class="card-header">Thông tin tìm kiếm học viên</div>
        <div class="card-body">
            <form method="GET" action="{{ route('pmgplx.dm.hoc-vien.index') }}">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-3">
                        <label>Mã ĐK|Họ tên|CMT|Số hồ sơ</label>
                        <input type="text" class="form-control form-control-sm" name="tu_khoa" value="{{ $filters['tu_khoa'] }}" placeholder="Nhập từ khóa">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="filter_ma_kh">Khóa học</label>
                        <select name="ma_kh" id="filter_ma_kh" class="form-control form-control-sm">
                            <option value="">—Tất cả—</option>
                            @foreach ($khoaHocs as $kh)
                                <option value="{{ $kh->MaKH }}" @selected($filters['ma_kh'] === $kh->MaKH)>
                                    {{ $kh->TenKH }} ({{ $kh->MaKH }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label>Hạng GPLX</label>
                        <input type="text" class="form-control form-control-sm" name="hang_gplx" value="{{ $filters['hang_gplx'] }}" placeholder="VD: B1, B2">
                    </div>
                    <div class="form-group col-md-2">
                        <label>Trạng thái</label>
                        <select class="form-control form-control-sm" name="trang_thai">
                            <option value="">—Tất cả—</option>
                            <option value="1" @selected($filters['trang_thai'] === '1')>Hiệu lực</option>
                            <option value="0" @selected($filters['trang_thai'] === '0')>Không hiệu lực</option>
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary btn-block">Tìm kiếm</button>
                        <a href="{{ route('pmgplx.dm.hoc-vien.index') }}" class="btn btn-sm btn-outline-secondary btn-block mt-1" title="Làm mới">↻</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-panel">
        <div class="card-header">Danh sách học viên</div>
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center mb-3">
                <div class="btn-group btn-group-sm mr-2 mb-2" role="group">
                    <button type="button" class="btn btn-success" disabled title="Sắp hỗ trợ">＋ Thêm mới</button>
                    <button type="button" class="btn btn-warning" disabled title="Sắp hỗ trợ">✎ Xem - Sửa</button>
                    <button type="button" class="btn btn-danger" disabled title="Sắp hỗ trợ">✕ Xóa</button>
                </div>

                <div class="mb-2 mr-2">
                    <a href="{{ route('pmgplx.dm.hoc-vien.dong-bo.form', array_filter(['ma_kh_nguon' => $filters['ma_kh']])) }}"
                       class="btn btn-sm btn-info">
                        Đồng Bộ Qua Bản Cũ
                    </a>
                    <a href="{{ route('pmgplxold.dm.hoc-vien.index') }}" class="btn btn-sm btn-outline-secondary ml-1" target="_blank">
                        Xem bản cũ
                    </a>
                </div>

                <div class="ml-auto d-flex flex-wrap align-items-center mb-2">
                    <span class="mr-3">Tổng số bản ghi: <strong>{{ number_format($items->total()) }}</strong></span>
                    <form method="GET" action="{{ route('pmgplx.dm.hoc-vien.index') }}" class="form-inline mr-3">
                        @foreach (request()->except(['per_page', 'page']) as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        <label class="mr-2 mb-0">Số bản ghi/trang</label>
                        <select name="per_page" class="form-control form-control-sm" onchange="this.form.submit()">
                            @foreach ([20, 50, 100, 200] as $n)
                                <option value="{{ $n }}" @selected((int) $filters['per_page'] === $n)>{{ $n }}</option>
                            @endforeach
                        </select>
                    </form>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item {{ $items->onFirstPage() ? 'disabled' : '' }}">
                                <a class="page-link" href="{{ $items->url(1) }}">«</a>
                            </li>
                            <li class="page-item {{ $items->onFirstPage() ? 'disabled' : '' }}">
                                <a class="page-link" href="{{ $items->previousPageUrl() }}">‹</a>
                            </li>
                            <li class="page-item active">
                                <span class="page-link">Trang {{ $items->currentPage() }}/{{ max($items->lastPage(), 1) }}</span>
                            </li>
                            <li class="page-item {{ $items->hasMorePages() ? '' : 'disabled' }}">
                                <a class="page-link" href="{{ $items->nextPageUrl() }}">›</a>
                            </li>
                            <li class="page-item {{ $items->hasMorePages() ? '' : 'disabled' }}">
                                <a class="page-link" href="{{ $items->url($items->lastPage()) }}">»</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped table-hover table-data mb-0">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Mã ĐK</th>
                            <th>Họ và tên</th>
                            <th>Ngày sinh</th>
                            <th>Giới tính</th>
                            <th>Số CMT</th>
                            <th>Số hồ sơ</th>
                            <th>Mã khóa học</th>
                            <th>Hạng GPLX</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $index => $row)
                            <tr>
                                <td>{{ $items->firstItem() + $index }}</td>
                                <td>
                                    @include('PMGPLX.danh-muc._trang-thai-icon', ['active' => (string) $row->TrangThai === '1'])
                                    {{ $row->MaDK }}
                                </td>
                                <td>{{ $row->HoVaTen ?: trim(($row->HoDemNLX ?? '').' '.($row->TenNLX ?? '')) }}</td>
                                <td>{{ \App\Support\PMGPLX\NgayVn::format($row->NgaySinh, 'yyyymmdd') }}</td>
                                <td>
                                    @if ($row->GioiTinh === '1' || strtoupper((string) $row->GioiTinh) === 'M')
                                        Nam
                                    @elseif ($row->GioiTinh === '0' || strtoupper((string) $row->GioiTinh) === 'F')
                                        Nữ
                                    @else
                                        {{ $row->GioiTinh }}
                                    @endif
                                </td>
                                <td>{{ $row->SoCMT }}</td>
                                <td>{{ $row->SoHoSo }}</td>
                                <td>{{ $row->MaKhoaHoc }}</td>
                                <td>{{ $row->HangGPLX }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">Không có dữ liệu</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $('#filter_ma_kh').select2({
        theme: 'bootstrap4',
        placeholder: 'Tìm khóa học...',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#filter_ma_kh').closest('.form-group'),
        language: {
            noResults: function () { return 'Không tìm thấy khóa học'; }
        }
    });
</script>
@endpush
