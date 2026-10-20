@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Quản lý lịch làm việc giáo viên')

@section('content')
    <div class="card card-panel">
        <div class="card-header">Thông tin tìm kiếm lịch làm việc giáo viên</div>
        <div class="card-body">
            <form method="GET" action="{{ route('pmgplx.lich.gv.index') }}">
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
                    <div class="form-group col-md-3">
                        <label>Thời gian từ ... đến ...</label>
                        <div class="d-flex align-items-center">
                            <input type="date" class="form-control form-control-sm" name="tu_ngay" value="{{ $filters['tu_ngay'] }}">
                            <span class="mx-2">→</span>
                            <input type="date" class="form-control form-control-sm" name="den_ngay" value="{{ $filters['den_ngay'] }}">
                        </div>
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
                        <a href="{{ route('pmgplx.lich.gv.index') }}" class="btn btn-sm btn-outline-secondary btn-block mt-1" title="Làm mới">↻</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-panel">
        <div class="card-header">Danh sách lịch làm việc giáo viên</div>
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center mb-3">
                <div class="btn-group btn-group-sm mr-2 mb-2" role="group">
                    <a href="{{ route('pmgplx.lich.ly-thuyet.create') }}" class="btn btn-success">＋ Thêm mới</a>
                    <a href="{{ route('pmgplx.lich.gv.do-trung', array_filter([
                        'ma_kh' => $filters['ma_kh'] ?: null,
                        'ma_gv' => $filters['ma_gv'] ?: null,
                        'tu_ngay' => $filters['tu_ngay'] ?: null,
                        'den_ngay' => $filters['den_ngay'] ?: null,
                        'mode' => $filters['ma_gv'] ? 'by_gv' : 'by_range',
                    ])) }}" class="btn btn-outline-primary">⌕ Dò trùng</a>
                    <a href="{{ route('pmgplx.lich.gv.do-trong', array_filter([
                        'run' => $filters['ma_kh'] && $filters['ma_gv'] ? '1' : null,
                        'pair_ma_kh' => $filters['ma_kh'] ? [$filters['ma_kh']] : null,
                        'pair_ma_gv_sang' => $filters['ma_gv'] ? [$filters['ma_gv']] : null,
                        'pair_ma_gv_chieu' => $filters['ma_gv'] ? [$filters['ma_gv']] : null,
                        'tu_ngay' => $filters['tu_ngay'] ?: null,
                        'den_ngay' => $filters['den_ngay'] ?: null,
                    ])) }}" class="btn btn-outline-success">◻ Dò lịch trống</a>
                    <button type="button" class="btn btn-warning" disabled>✎ Xem - Sửa</button>
                </div>
                @if ($khoaChon)
                    <form method="POST" action="{{ route('pmgplx.lich.gv.destroy-theo-khoa') }}" class="mr-2 mb-2"
                          onsubmit="return confirm(@js('Xóa toàn bộ '.number_format($khoaChon['so_buoi']).' buổi lịch giáo viên của khóa '.$khoaChon['ten_kh'].' ('.$khoaChon['ma_kh'].')? Không thể hoàn tác.'));">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="ma_kh" value="{{ $khoaChon['ma_kh'] }}">
                        <input type="hidden" name="return_query" value="{{ http_build_query(request()->except('page')) }}">
                        <button type="submit" class="btn btn-sm btn-danger" @disabled($khoaChon['so_buoi'] === 0)>
                            ✕ Xóa toàn bộ khóa {{ $khoaChon['ten_kh'] }}
                        </button>
                    </form>
                @endif

                <div class="ml-auto d-flex flex-wrap align-items-center mb-2">
                    <span class="mr-3">Tổng số bản ghi: <strong>{{ number_format($items->total()) }}</strong></span>
                    <form method="GET" action="{{ route('pmgplx.lich.gv.index') }}" class="form-inline mr-3">
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
                            <th>Mã giáo viên</th>
                            <th>Tên giáo viên</th>
                            <th>Loại hình đào tạo</th>
                            <th>TG bắt đầu</th>
                            <th>TG kết thúc</th>
                            <th>Môn học GD</th>
                            <th>Ghi chú</th>
                            <th width="100">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $index => $row)
                            <tr>
                                <td>{{ $items->firstItem() + $index }}</td>
                                <td>{{ $row->MaKH }}</td>
                                <td>
                                    @include('PMGPLX.danh-muc._trang-thai-icon', ['active' => (bool) $row->TrangThai])
                                    {{ $row->MaGV }}
                                </td>
                                <td>{{ $row->TenGV }}</td>
                                <td>{{ \App\Support\PMGPLX\LoaiGiaoVien::label($row->LoaiGV) }}</td>
                                <td>{{ optional($row->NgayBD)->format('d/m/Y H:i') }}</td>
                                <td>{{ optional($row->NgayKT)->format('d/m/Y H:i') }}</td>
                                <td>{{ \App\Support\PMGPLX\LichGvMonHoc::displayLabel($row->TenMonHoc, $row->MaMonHoc, $monMap) }}</td>
                                <td class="small">{{ $row->GhiChu }}</td>
                                <td class="text-nowrap">
                                    @if (! $row->IsKhoaHocGiaoVien && $row->NgayBD)
                                        <div class="dropdown lich-gv-thao-tac">
                                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                    data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                Thao tác
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right">
                                                <form method="POST" action="{{ route('pmgplx.lich.gv.destroy', $row->MaLichLV) }}" class="m-0"
                                                      onsubmit="return confirm(@js('Xóa buổi lịch GV '.$row->TenGV.' ('.optional($row->NgayBD)->format('d/m/Y H:i').')?'));">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="return_query" value="{{ http_build_query(request()->query()) }}">
                                                    <button type="submit" class="dropdown-item text-danger">✕ Xóa buổi này</button>
                                                </form>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted small" title="Dòng gán giáo viên vào khóa, không phải buổi lịch">—</span>
                                    @endif
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

@push('scripts')
<script>
    // Menu nằm trong .table-responsive (overflow) — dùng position fixed để không bị cắt.
    $('.lich-gv-thao-tac [data-toggle="dropdown"]').dropdown({
        popperConfig: { positionFixed: true }
    });

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
</script>
@endpush
