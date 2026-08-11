@extends('layouts.quan-ly')

@section('title', 'Quản lý lịch sử dụng xe tập lái')

@section('content')
    <div class="card card-panel">
        <div class="card-header">Thông tin tìm kiếm lịch sử dụng xe tập lái</div>
        <div class="card-body">
            <form method="GET" action="{{ route('lich.xe.index') }}">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-2">
                        <label>Mã khóa học</label>
                        <input type="text" class="form-control form-control-sm" name="ma_kh" value="{{ $filters['ma_kh'] }}" placeholder="Nhập mã khóa học">
                    </div>
                    <div class="form-group col-md-2">
                        <label>Biển số xe</label>
                        <input type="text" class="form-control form-control-sm" name="bien_so_xe" value="{{ $filters['bien_so_xe'] }}" placeholder="Nhập biển số">
                    </div>
                    <div class="form-group col-md-4">
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
                    <div class="form-group col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary btn-block">Tìm kiếm</button>
                        <a href="{{ route('lich.xe.index') }}" class="btn btn-sm btn-outline-secondary btn-block mt-1" title="Làm mới">↻</a>
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
                    <a href="{{ route('lich.thuc-hanh.create') }}" class="btn btn-success">＋ Thêm mới</a>
                    <button type="button" class="btn btn-warning" disabled>✎ Xem - Sửa</button>
                    <button type="button" class="btn btn-danger" disabled>✕ Xóa</button>
                </div>

                <div class="ml-auto d-flex flex-wrap align-items-center mb-2">
                    <span class="mr-3">Tổng số bản ghi: <strong>{{ number_format($items->total()) }}</strong></span>
                    <form method="GET" action="{{ route('lich.xe.index') }}" class="form-inline mr-3">
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
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $index => $row)
                            <tr>
                                <td>{{ $items->firstItem() + $index }}</td>
                                <td>{{ $row->MaKH }}</td>
                                <td>{{ $row->BienSoXe }}</td>
                                <td>{{ $row->TenGV }}</td>
                                <td>{{ optional($row->NgayBD)->format('d/m/Y H:i') }}</td>
                                <td>{{ optional($row->NgayKT)->format('d/m/Y H:i') }}</td>
                                <td>{{ $row->NgayKG ? \Carbon\Carbon::parse($row->NgayKG)->format('d/m/Y') : '' }}</td>
                                <td>{{ $row->NgayBG ? \Carbon\Carbon::parse($row->NgayBG)->format('d/m/Y') : '' }}</td>
                                <td>{{ $row->TrangThai ? 'Hiệu lực' : 'Không hiệu lực' }}</td>
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
