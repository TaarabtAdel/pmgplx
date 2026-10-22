@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Quản lý khoá học')

@section('content')
    <div class="card card-panel">
        <div class="card-header">Thông tin tìm kiếm khoá học</div>
        <div class="card-body">
            <form method="GET" action="{{ route('pmgplx.dm.khoa-hoc.index') }}">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-3">
                        <label>Mã|Tên khoá|Số QĐ</label>
                        <input type="text" class="form-control form-control-sm" name="tu_khoa" value="{{ $filters['tu_khoa'] }}" placeholder="Nhập từ khóa">
                    </div>
                    <div class="form-group col-md-2">
                        <label>Hạng đào tạo</label>
                        <select class="form-control form-control-sm" name="hang_dt">
                            <option value="">—Tất cả—</option>
                            @foreach ($hangOptions as $hang)
                                <option value="{{ $hang }}" @selected($filters['hang_dt'] === $hang)>{{ $hang }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label>Khai giảng từ</label>
                        <input type="date" class="form-control form-control-sm" name="tu_ngay" value="{{ $filters['tu_ngay'] }}">
                    </div>
                    <div class="form-group col-md-2">
                        <label>Đến</label>
                        <input type="date" class="form-control form-control-sm" name="den_ngay" value="{{ $filters['den_ngay'] }}">
                    </div>
                    <div class="form-group col-md-1">
                        <label>Trạng thái</label>
                        <select class="form-control form-control-sm" name="trang_thai">
                            <option value="">—Tất cả—</option>
                            <option value="1" @selected($filters['trang_thai'] === '1')>Hiệu lực</option>
                            <option value="0" @selected($filters['trang_thai'] === '0')>Không hiệu lực</option>
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary btn-block">Tìm kiếm</button>
                        <a href="{{ route('pmgplx.dm.khoa-hoc.index') }}" class="btn btn-sm btn-outline-secondary btn-block mt-1" title="Làm mới">↻</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-panel">
        <div class="card-header">Danh sách khoá học</div>
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center mb-3">
                <div class="btn-group btn-group-sm mr-2 mb-2" role="group">
                    <button type="button" class="btn btn-success" disabled title="Sắp hỗ trợ">＋ Thêm mới</button>
                    <button type="button" class="btn btn-warning" disabled title="Sắp hỗ trợ">✎ Xem - Sửa</button>
                    <button type="button" class="btn btn-danger" disabled title="Sắp hỗ trợ">✕ Xóa</button>
                </div>

                <div class="ml-auto d-flex flex-wrap align-items-center mb-2">
                    <span class="mr-3">Tổng số bản ghi: <strong>{{ number_format($items->total()) }}</strong></span>
                    <form method="GET" action="{{ route('pmgplx.dm.khoa-hoc.index') }}" class="form-inline mr-3">
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
                            <th>Mã khoá</th>
                            <th>Tên khoá</th>
                            <th>Hạng GPLX | Hạng ĐT</th>
                            <th>Ngày KG - Ngày BG</th>
                            <th class="text-right">Thời gian ĐT (ngày)</th>
                            <th class="text-right">Tổng số HV</th>
                            <th class="text-right">HV đã nhập</th>
                            <th class="text-right">Lịch GV</th>
                            <th class="text-right">Lịch xe</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $index => $row)
                            <tr>
                                <td>{{ $items->firstItem() + $index }}</td>
                                <td class="text-nowrap">
                                    @include('PMGPLX.danh-muc._trang-thai-icon', ['active' => (bool) $row->TrangThai])
                                    {{ $row->MaKH }}
                                </td>
                                <td>{{ $row->TenKH }}</td>
                                <td class="text-nowrap">{{ trim((string) $row->HangGPLX) ?: '—' }} | {{ trim((string) $row->HangDT) ?: '—' }}</td>
                                <td class="text-nowrap">{{ $row->NgayKG?->format('d/m/Y') ?? '—' }} - {{ $row->NgayBG?->format('d/m/Y') ?? '—' }}</td>
                                <td class="text-right">{{ $row->ThoiGianDT }}</td>
                                <td class="text-right">{{ $row->TongSoHV }}</td>
                                <td class="text-right js-no-row-select">
                                    <a href="{{ route('pmgplx.dm.hoc-vien.index', ['ma_kh' => $row->MaKH]) }}" title="Xem học viên khoá {{ $row->MaKH }}">
                                        {{ number_format((int) $row->so_hoc_vien) }}
                                    </a>
                                </td>
                                <td class="text-right js-no-row-select">
                                    <a href="{{ route('pmgplx.lich.gv.index', ['ma_kh' => $row->MaKH]) }}" title="Xem lịch giáo viên khoá {{ $row->MaKH }}">
                                        {{ number_format((int) $row->so_lich_gv) }}
                                    </a>
                                </td>
                                <td class="text-right js-no-row-select">
                                    <a href="{{ route('pmgplx.lich.xe.index', ['ma_kh' => $row->MaKH]) }}" title="Xem lịch xe khoá {{ $row->MaKH }}">
                                        {{ number_format((int) $row->so_lich_xe) }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4">Không có dữ liệu</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
