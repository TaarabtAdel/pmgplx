@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Quản lý lịch sử dụng xe tập lái')

@section('content')
    <div class="card card-panel">
        <div class="card-header">Thông tin tìm kiếm lịch sử dụng xe tập lái</div>
        <div class="card-body">
            <form method="GET" action="{{ route('pmgplx.lich.xe.index') }}">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-3">
                        <label for="filter_ma_kh">Mã khóa học</label>
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
                        <label for="filter_bien_so_xe">Biển số xe</label>
                        <select name="bien_so_xe" id="filter_bien_so_xe" class="form-control form-control-sm">
                            <option value="">—Tất cả—</option>
                            @foreach ($xeTaps as $xe)
                                <option value="{{ $xe }}" @selected($filters['bien_so_xe'] === $xe)>{{ $xe }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="filter_ma_gv">Mã|Tên giáo viên</label>
                        <select name="ma_gv" id="filter_ma_gv" class="form-control form-control-sm">
                            <option value="">—Tất cả—</option>
                            @foreach ($giaoViens as $gv)
                                <option value="{{ $gv->MaGV }}" @selected($filters['ma_gv'] === $gv->MaGV)>
                                    {{ $gv->ho_ten }} ({{ $gv->MaGV }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label>Thời gian từ ... đến ...</label>
                        <div class="d-flex align-items-center">
                            <input type="date" class="form-control form-control-sm" name="tu_ngay" value="{{ $filters['tu_ngay'] }}">
                            <span class="mx-2">→</span>
                            <input type="date" class="form-control form-control-sm" name="den_ngay" value="{{ $filters['den_ngay'] }}">
                        </div>
                    </div>
                    <div class="form-group col-md-2">
                        <label>Trạng thái</label>
                        <select class="form-control form-control-sm" name="trang_thai">
                            <option value="">—Tất cả—</option>
                            <option value="1" @selected($filters['trang_thai'] === '1')>Hiệu lực</option>
                            <option value="0" @selected($filters['trang_thai'] === '0')>Không hiệu lực</option>
                        </select>
                    </div>
                    <div class="form-group col-md-1">
                        <button type="submit" class="btn btn-sm btn-primary btn-block">Tìm</button>
                        <a href="{{ route('pmgplx.lich.xe.index') }}" class="btn btn-sm btn-outline-secondary btn-block mt-1" title="Làm mới">↻</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-panel">
        <div class="card-header">Danh sách lịch sử dụng xe tập lái</div>
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center mb-3">
                <div class="btn-group btn-group-sm mr-2 mb-2" role="group">
                    <a href="{{ route('pmgplx.lich.thuc-hanh.create') }}" class="btn btn-success">＋ Thêm mới</a>
                    <button type="button" class="btn btn-warning" disabled>✎ Xem - Sửa</button>
                    <button type="button" class="btn btn-danger" disabled>✕ Xóa</button>
                </div>

                <div class="ml-auto d-flex flex-wrap align-items-center mb-2">
                    <span class="mr-3">Tổng số bản ghi: <strong>{{ number_format($items->total()) }}</strong></span>
                    <form method="GET" action="{{ route('pmgplx.lich.xe.index') }}" class="form-inline mr-3">
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
                            <th>Mã khóa học</th>
                            <th>Biển số xe</th>
                            <th>Giáo viên phụ trách</th>
                            <th>TG bắt đầu</th>
                            <th>TG kết thúc</th>
                            <th>Ngày khai giảng</th>
                            <th>Ngày bế giảng</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $index => $row)
                            <tr>
                                <td>{{ $items->firstItem() + $index }}</td>
                                <td>{{ $row->MaKH }}</td>
                                <td>
                                    @include('PMGPLX.danh-muc._trang-thai-icon', ['active' => (bool) $row->TrangThai])
                                    {{ $row->BienSoXe }}
                                </td>
                                <td>{{ $row->TenGV }}</td>
                                <td>{{ optional($row->NgayBD)->format('d/m/Y H:i') }}</td>
                                <td>{{ optional($row->NgayKT)->format('d/m/Y H:i') }}</td>
                                <td>{{ $row->NgayKG ? \Carbon\Carbon::parse($row->NgayKG)->format('d/m/Y') : '' }}</td>
                                <td>{{ $row->NgayBG ? \Carbon\Carbon::parse($row->NgayBG)->format('d/m/Y') : '' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">Không có dữ liệu</td>
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

    $('#filter_ma_gv').select2({
        theme: 'bootstrap4',
        placeholder: 'Tìm giáo viên...',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#filter_ma_gv').closest('.form-group'),
        language: {
            noResults: function () { return 'Không tìm thấy giáo viên'; }
        }
    });

    $('#filter_bien_so_xe').select2({
        theme: 'bootstrap4',
        placeholder: 'Tìm biển số xe...',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#filter_bien_so_xe').closest('.form-group'),
        language: {
            noResults: function () { return 'Không tìm thấy biển số xe'; }
        }
    });
</script>
@endpush
