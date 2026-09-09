@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Tổng hợp học viên DAT')

@push('styles')
<style>
    .dat-filter-section .form-group {
        margin-bottom: 0.75rem;
    }
    @media (min-width: 768px) {
        .dat-filter-section .form-row:last-child .form-group {
            margin-bottom: 0;
        }
    }
    .dat-tong-hop-chua-dat {
        background-color: #fff8e1;
    }
</style>
@endpush

@section('content')
    @php
        $apDungTuNgayFormatted = \Carbon\Carbon::parse($apDungTuNgay)->format('d/m/Y');
    @endphp

    <div class="card card-panel mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <span>Tổng hợp học viên DAT</span>
            <div class="mt-1 mt-md-0">
                <a href="{{ route('daotao.pdt.dat.quan-ly-phien') }}" class="btn btn-sm btn-navy mr-1">
                    Chi tiết phiên
                </a>
                <a href="{{ route('daotao.pdt.dat.dieu-kien-dat') }}" class="btn btn-sm btn-outline-secondary">
                    Điều kiện đạt
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="border rounded p-3 bg-light small mb-3">
                <p class="mb-2">
                    Chọn <strong>mã khóa học</strong> để tổng hợp tự động (tránh quét toàn bộ dữ liệu khi chưa chọn khóa).
                    Các bộ lọc khác vẫn dùng nút <strong>Lọc</strong>.
                    Tổng hợp theo <strong>mã học viên + khóa học</strong> (cộng dồn từ các phiên thực hành
                    <strong>không có cảnh báo</strong> — cùng tiêu chí cột <strong>Đạt</strong> ở màn chi tiết phiên).
                    Bấm <strong>Xem phiên</strong> để mở màn chi tiết từng phiên.
                </p>
                <p class="mb-2">
                    <strong>Xe số tự động (giờ):</strong> cộng giờ thực hành của các phiên có biển số xe thuộc
                    <strong>hạng B11</strong> trong
                    <a href="{{ route('pmgplx.dm.xe.index') }}">danh mục xe tập</a>.
                </p>
                <p class="mb-0 font-weight-bold">
                    Áp dụng từ ngày {{ $apDungTuNgayFormatted }} — so sánh với
                    <a href="{{ route('daotao.pdt.dat.dieu-kien-dat') }}">điều kiện đạt</a> theo loại khóa (hạng).
                </p>
            </div>

            <form method="GET" action="{{ route('daotao.pdt.dat.tong-hop-hoc-vien') }}" class="mb-3" id="datTongHopFilterForm">
                <div class="border rounded p-3 bg-white mb-2 dat-filter-section">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                        <strong class="small mb-2 mb-md-0">Lọc theo thông tin phiên</strong>
                        <div class="d-flex flex-wrap">
                            <button type="submit" class="btn btn-sm btn-navy mr-2 mb-2 mb-md-0">Lọc</button>
                            <a href="{{ route('daotao.pdt.dat.tong-hop-hoc-vien') }}"
                               class="btn btn-sm btn-outline-secondary mr-2 mb-2 mb-md-0">
                                Reset
                            </a>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label class="small text-muted mb-1" for="filter_ma_hoc_vien">Mã học viên</label>
                            <select name="ma_hoc_vien" id="filter_ma_hoc_vien" class="form-control form-control-sm">
                                <option value="">— Tất cả —</option>
                                @if ($selectedHocVienOption)
                                    <option value="{{ $selectedHocVienOption['id'] }}" selected>
                                        {{ $selectedHocVienOption['text'] }}
                                    </option>
                                @endif
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="small text-muted mb-1" for="filter_ma_khoa_hoc">Mã khóa học <span class="text-danger">*</span></label>
                            <select name="ma_khoa_hoc" id="filter_ma_khoa_hoc" class="form-control form-control-sm" required>
                                <option value="">— Chọn khóa —</option>
                                @foreach ($khoaHocOptions as $kh)
                                    <option value="{{ $kh->MaKhoaHoc }}"
                                            @selected(($filters['ma_khoa_hoc'] ?? '') === $kh->MaKhoaHoc)>
                                        @if (! empty($kh->TenKhoaHoc))
                                            {{ $kh->TenKhoaHoc }} ({{ $kh->MaKhoaHoc }})
                                        @else
                                            {{ $kh->MaKhoaHoc }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label class="small text-muted mb-1" for="filter_loai_khoa_hoc">Loại khóa học</label>
                            <select name="loai_khoa_hoc" id="filter_loai_khoa_hoc" class="form-control form-control-sm">
                                <option value="">— Tất cả —</option>
                                @foreach ($loaiKhoaHocOptions as $loai)
                                    <option value="{{ $loai }}"
                                            @selected(($filters['loai_khoa_hoc'] ?? '') === $loai)>
                                        {{ $loai }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label class="small text-muted mb-1">Từ ngày</label>
                            <input type="date" name="tu_ngay" class="form-control form-control-sm"
                                   value="{{ $filters['tu_ngay'] }}">
                        </div>
                        <div class="form-group col-md-4">
                            <label class="small text-muted mb-1">Đến ngày</label>
                            <input type="date" name="den_ngay" class="form-control form-control-sm"
                                   value="{{ $filters['den_ngay'] }}">
                        </div>
                        <div class="form-group col-md-4">
                            <label class="small text-muted mb-1" for="filter_dat_ct">Đạt CT</label>
                            <select name="dat_ct" id="filter_dat_ct" class="form-control form-control-sm" @disabled(! $canTongHop)>
                                <option value="" @selected(($filters['dat_ct'] ?? '') === '')>— Tất cả —</option>
                                <option value="dat" @selected(($filters['dat_ct'] ?? '') === 'dat')>Đạt</option>
                                <option value="chua_dat" @selected(($filters['dat_ct'] ?? '') === 'chua_dat')>Không đạt</option>
                            </select>
                        </div>
                    </div>
                </div>
            </form>

            @if ($canTongHop)
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Học viên</th>
                            <th>Khóa học</th>
                            <th>Số phiên</th>
                            <th>Số giờ học</th>
                            <th>Tổng km</th>
                            <th>Ban đêm (giờ)</th>
                            <th>Xe số tự động (giờ)</th>
                            <th>Đạt CT</th>
                            <th width="100"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            @php
                                $dieuKien = $dieuKienByHang->get($item->LoaiKhoaHoc);
                                $datCt = \App\Support\DaoTao\DatHocVienTongHop::datChuongTrinh($item, $dieuKien);
                                $detailUrl = route('daotao.pdt.dat.quan-ly-phien', array_filter([
                                    'ma_hoc_vien' => \App\Support\DaoTao\DatDSPhienBoLoc::composeMaHocVienValue(
                                        (string) $item->MaHocVien,
                                        (string) ($item->MaKhoaHoc ?? '')
                                    ),
                                    'ma_khoa_hoc' => $filters['ma_khoa_hoc'] ?: null,
                                    'loai_khoa_hoc' => $filters['loai_khoa_hoc'] ?: null,
                                    'tu_ngay' => $filters['tu_ngay'] ?: null,
                                    'den_ngay' => $filters['den_ngay'] ?: null,
                                ]));
                            @endphp
                            <tr @class(['dat-tong-hop-chua-dat' => $datCt === false])>
                                <td>{{ ($items->firstItem() ?? 0) + $loop->index }}</td>
                                <td>
                                    <div>{{ $item->HoTenHocVien ?? '—' }}</div>
                                    <small class="text-muted">{{ $item->MaHocVien }}</small>
                                </td>
                                <td>
                                    <div>
                                        {{ $item->TenKhoaHoc ?? '—' }}@if (! empty($item->LoaiKhoaHoc)) ({{ $item->LoaiKhoaHoc }})@endif
                                    </div>
                                    <small class="text-muted">{{ $item->MaKhoaHoc ?? '' }}</small>
                                </td>
                                <td>{{ number_format($item->SoPhien) }}</td>
                                <td>{{ \App\Support\DaoTao\DatHocVienTongHop::formatNumber($item->TongGioHoc) }}</td>
                                <td>{{ \App\Support\DaoTao\DatHocVienTongHop::formatNumber($item->TongQuangDuongKm) }}</td>
                                <td>{{ \App\Support\DaoTao\DatHocVienTongHop::formatNumber($item->TongBanDemGio) }}</td>
                                <td>{{ \App\Support\DaoTao\DatHocVienTongHop::formatNumber($item->TongXeSoTuDongGio) }}</td>
                                <td>
                                    @if ($datCt === true)
                                        <span class="badge badge-success">Đạt</span>
                                    @elseif ($datCt === false)
                                        <span class="badge badge-warning">Chưa đạt</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    <a href="{{ $detailUrl }}" class="btn btn-sm btn-outline-primary">Xem phiên</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    Không có dữ liệu tổng hợp theo bộ lọc đã chọn.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($items->hasPages())
                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
                    <div class="text-muted small mb-2 mb-md-0">
                        Trang {{ $items->currentPage() }}/{{ max($items->lastPage(), 1) }}
                        · {{ number_format($items->total()) }} học viên
                    </div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item {{ $items->onFirstPage() ? 'disabled' : '' }}">
                                <a class="page-link" href="{{ $items->url(1) }}">«</a>
                            </li>
                            <li class="page-item {{ $items->onFirstPage() ? 'disabled' : '' }}">
                                <a class="page-link" href="{{ $items->previousPageUrl() }}">‹</a>
                            </li>
                            <li class="page-item active">
                                <span class="page-link">{{ $items->currentPage() }}</span>
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
            @endif
            @else
                <p class="text-muted mb-0">Chọn <strong>mã khóa học</strong> để xem tổng hợp học viên.</p>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $('#filter_ma_hoc_vien').select2({
        theme: 'bootstrap4',
        placeholder: 'Nhập mã, tên HV hoặc khóa học...',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#filter_ma_hoc_vien').closest('.form-group'),
        minimumInputLength: 1,
        ajax: {
            url: @json(route('daotao.pdt.dat.quan-ly-phien.hoc-vien-options')),
            dataType: 'json',
            delay: 300,
            data: function (params) {
                return {
                    q: params.term || '',
                    page: params.page || 1
                };
            },
            processResults: function (data, params) {
                params.page = params.page || 1;

                return {
                    results: data.results || [],
                    pagination: {
                        more: !!(data.pagination && data.pagination.more)
                    }
                };
            },
            cache: true
        },
        language: {
            inputTooShort: function () { return 'Nhập ít nhất 1 ký tự để tìm'; },
            searching: function () { return 'Đang tìm...'; },
            noResults: function () { return 'Không tìm thấy học viên'; }
        }
    });
    $('#filter_ma_khoa_hoc').select2({
        theme: 'bootstrap4',
        placeholder: 'Chọn khóa học (bắt buộc)...',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#filter_ma_khoa_hoc').closest('.form-group'),
        language: {
            noResults: function () { return 'Không tìm thấy khóa học'; }
        }
    }).on('change', function () {
        if (this.value) {
            $('#datTongHopFilterForm').trigger('submit');
        }
    });
</script>
@endpush
