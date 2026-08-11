@extends('layouts.quan-ly')

@section('title', 'Quản lý giáo viên')

@section('content')
    <div class="card card-panel">
        <div class="card-header">Thông tin tìm kiếm giáo viên</div>
        <div class="card-body">
            <form method="GET" action="{{ route('dm.giao-vien.index') }}">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-4">
                        <label>Mã|Tên|CMT|Điện thoại</label>
                        <input type="text" class="form-control form-control-sm" name="tu_khoa" value="{{ $filters['tu_khoa'] }}" placeholder="Nhập từ khóa">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Hạng GPLX</label>
                        <input type="text" class="form-control form-control-sm" name="hang_gplx" value="{{ $filters['hang_gplx'] }}" placeholder="VD: B2, C">
                    </div>
                    <div class="form-group col-md-3">
                        <label>Trạng thái</label>
                        <select class="form-control form-control-sm" name="trang_thai">
                            <option value="">—Tất cả—</option>
                            <option value="1" @selected($filters['trang_thai'] === '1')>Hiệu lực</option>
                            <option value="0" @selected($filters['trang_thai'] === '0')>Không hiệu lực</option>
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary btn-block">Tìm kiếm</button>
                        <a href="{{ route('dm.giao-vien.index') }}" class="btn btn-sm btn-outline-secondary btn-block mt-1" title="Làm mới">↻</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-panel">
        <div class="card-header">Danh sách giáo viên</div>
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center mb-3">
                <div class="btn-group btn-group-sm mr-2 mb-2" role="group">
                    <button type="button" class="btn btn-success" disabled title="Sắp hỗ trợ">＋ Thêm mới</button>
                    <button type="button" class="btn btn-warning" disabled title="Sắp hỗ trợ">✎ Xem - Sửa</button>
                    <button type="button" class="btn btn-danger" disabled title="Sắp hỗ trợ">✕ Xóa</button>
                </div>

                <div class="ml-auto d-flex flex-wrap align-items-center mb-2">
                    <span class="mr-3">Tổng số bản ghi: <strong>{{ number_format($items->total()) }}</strong></span>
                    <form method="GET" action="{{ route('dm.giao-vien.index') }}" class="form-inline mr-3">
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
                            <th>Mã GV</th>
                            <th>Họ và tên</th>
                            <th>Ngày sinh</th>
                            <th>Giới tính</th>
                            <th>Điện thoại</th>
                            <th>Hạng GPLX</th>
                            <th>Số QĐ GCN</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $index => $row)
                            <tr>
                                <td>{{ $items->firstItem() + $index }}</td>
                                <td>{{ $row->MaGV }}</td>
                                <td>{{ $row->ho_ten }}</td>
                                <td>
                                    @php
                                        $ns = (string) ($row->NgaySinh ?? '');
                                        echo strlen($ns) === 8
                                            ? substr($ns, 6, 2).'/'.substr($ns, 4, 2).'/'.substr($ns, 0, 4)
                                            : $ns;
                                    @endphp
                                </td>
                                <td>
                                    @if ($row->GioiTinh === '1' || strtoupper((string) $row->GioiTinh) === 'M')
                                        Nam
                                    @elseif ($row->GioiTinh === '0' || strtoupper((string) $row->GioiTinh) === 'F')
                                        Nữ
                                    @else
                                        {{ $row->GioiTinh }}
                                    @endif
                                </td>
                                <td>{{ $row->DienThoai }}</td>
                                <td>{{ $row->HangGPLX }}</td>
                                <td>{{ $row->SoQD_GCN }}</td>
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
