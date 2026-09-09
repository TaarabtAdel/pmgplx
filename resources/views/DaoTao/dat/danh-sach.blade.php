@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Chi tiết phiên DAT')

@push('styles')
<style>
    .dat-phien-row-loi {
        background-color: #fff8e1;
    }
    .dat-phien-row-loi.table-danger {
        background-color: #f8d7da;
    }
    .dat-phien-badge-wrap .badge {
        font-weight: 500;
        margin: 0 2px 2px 0;
    }
    .dat-loi-badge-multiline {
        white-space: normal;
        text-align: left;
        line-height: 1.25;
        max-width: 10.5rem;
    }
    .dat-bulk-bar {
        position: sticky;
        top: 0;
        z-index: 5;
    }
    .dat-filter-section .form-group {
        margin-bottom: 0.75rem;
    }
    @media (min-width: 768px) {
        .dat-filter-section .form-row:last-child .form-group {
            margin-bottom: 0;
        }
    }
</style>
@endpush

@section('content')
    @php
        $selectedLoi = $filters['loi'] ?? [];
        $selectedPhanLoai = $filters['phan_loai'] ?? [];
    @endphp

    <div class="card card-panel mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <span>Chi tiết phiên DAT</span>
            <div class="mt-1 mt-md-0">
                <a href="{{ route('daotao.pdt.dat.tong-hop-hoc-vien') }}" class="btn btn-sm btn-navy mr-1">
                    Tổng hợp học viên
                </a>
                <a href="{{ route('daotao.pdt.dat.dieu-kien-canh-bao') }}" class="btn btn-sm btn-outline-secondary mr-1">
                    Điều kiện cảnh báo
                </a>
                <a href="{{ route('daotao.pdt.dat.do-phien-lich-xe') }}" class="btn btn-sm btn-outline-secondary mr-1">
                    Dò phiên với lịch xe
                </a>
                <a href="{{ route('daotao.pdt.dat.phan-loai-phien') }}" class="btn btn-sm btn-outline-secondary mr-1">
                    Phân loại phiên
                </a>
                <a href="{{ route('daotao.pdt.dat.nhap-du-lieu-phien') }}" class="btn btn-sm btn-navy">Nhập dữ liệu phiên</a>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('daotao.pdt.dat.quan-ly-phien') }}" class="mb-3" id="datFilterForm">
                <div class="border rounded p-3 bg-white mb-2 dat-filter-section">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                        <strong class="small mb-2 mb-md-0">Lọc theo thông tin phiên</strong>
                        <div class="d-flex flex-wrap">
                            <button type="submit" class="btn btn-sm btn-navy mr-2 mb-2 mb-md-0">Lọc</button>
                            <a href="{{ route('daotao.pdt.dat.quan-ly-phien') }}"
                               class="btn btn-sm btn-outline-secondary mr-2 mb-2 mb-md-0">
                                Reset
                            </a>
                            <a href="{{ route('daotao.pdt.dat.quan-ly-phien.export', request()->query()) }}"
                               class="btn btn-sm btn-outline-success mb-2 mb-md-0">
                                Xuất Excel
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
                            <label class="small text-muted mb-1" for="filter_ma_khoa_hoc">Mã khóa học</label>
                            <select name="ma_khoa_hoc" id="filter_ma_khoa_hoc" class="form-control form-control-sm">
                                <option value="">— Tất cả —</option>
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
                            <label class="small text-muted mb-1" for="filter_ma_giao_vien">Mã giáo viên</label>
                            <select name="ma_giao_vien" id="filter_ma_giao_vien" class="form-control form-control-sm">
                                <option value="">— Tất cả —</option>
                                @foreach ($giaoVienOptions as $gv)
                                    <option value="{{ $gv->MaGiaoVien }}"
                                            @selected(($filters['ma_giao_vien'] ?? '') === $gv->MaGiaoVien)>
                                        @if (! empty($gv->HoTenGiaoVien))
                                            {{ $gv->HoTenGiaoVien }} ({{ $gv->MaGiaoVien }})
                                        @else
                                            {{ $gv->MaGiaoVien }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label class="small text-muted mb-1">Từ ngày</label>
                            <input type="date" name="tu_ngay" class="form-control form-control-sm"
                                   value="{{ $filters['tu_ngay'] }}">
                        </div>
                        <div class="form-group col-md-3">
                            <label class="small text-muted mb-1">Đến ngày</label>
                            <input type="date" name="den_ngay" class="form-control form-control-sm"
                                   value="{{ $filters['den_ngay'] }}">
                        </div>
                        <div class="form-group col-md-3">
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
                        <div class="form-group col-md-3">
                            <label class="small text-muted mb-1">Đạt</label>
                            <select name="dat" class="form-control form-control-sm" @disabled(! $canAnalyzeViolations)>
                                <option value="" @selected(($filters['dat'] ?? '') === '')>— Tất cả —</option>
                                <option value="dat" @selected(($filters['dat'] ?? '') === 'dat')>Đạt</option>
                                <option value="chua_dat" @selected(($filters['dat'] ?? '') === 'chua_dat')>Không đạt</option>
                            </select>
                        </div>
                    </div>
                </div>

                @if ($phanLoais->isNotEmpty())
                    <div class="border rounded p-2 bg-white mb-2">
                        <strong class="small d-block mb-2">Lọc theo phân loại</strong>
                        <div class="form-row">
                            @foreach ($phanLoais as $pl)
                                <div class="col-md-4 col-lg-3 mb-1">
                                    <label class="mb-0 small d-flex align-items-start">
                                        <input type="checkbox" name="phan_loai[]" value="{{ $pl->Id }}" class="mr-2 mt-1"
                                               @checked(in_array($pl->Id, $selectedPhanLoai, true))>
                                        <span>{{ $pl->TenPhanLoai }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="border rounded p-2 bg-light">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                        <strong class="small mb-1">Lọc theo loại lỗi / cảnh báo</strong>
                        @if ($canAnalyzeViolations)
                            <span class="text-muted small">
                                Trên {{ number_format($tongPhienLoc ?? 0) }} phiên theo bộ lọc
                            </span>
                        @else
                            <span class="text-muted small">
                                Chọn mã khóa học để xem số lượng
                            </span>
                        @endif
                    </div>
                    <div class="form-row">
                        @foreach ($loiDefinitions as $code => $def)
                            <div class="col-md-6 col-lg-4 mb-1">
                                <label class="mb-0 small d-flex align-items-start">
                                    <input type="checkbox" name="loi[]" value="{{ $code }}" class="mr-2 mt-1"
                                           @checked(in_array($code, $selectedLoi, true))
                                           @disabled(! $canAnalyzeViolations)>
                                    <span>
                                        @if (! empty($def['label_lines']))
                                            <span class="d-block">{{ $def['label_lines'][0] }}</span>
                                            <span class="d-block">{{ $def['label_lines'][1] ?? '' }}</span>
                                        @else
                                            {{ $def['label'] }}
                                        @endif
                                        @if ($canAnalyzeViolations && $loiCounts !== null)
                                            <span class="text-muted">({{ number_format($loiCounts[$code] ?? 0) }})</span>
                                        @endif
                                    </span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <div class="small text-muted mt-2">
                        @if (! $canAnalyzeViolations)
                            Chọn <strong>mã khóa học</strong> rồi bấm Lọc để hiện số lượng từng cảnh báo và đánh dấu trên bảng (tránh quét toàn bộ dữ liệu).
                        @else
                            Chọn một hoặc nhiều loại để chỉ hiện phiên có lỗi tương ứng. Bỏ chọn hết = xem tất cả.
                            <strong>Đạt</strong> = phiên không có bất kỳ cảnh báo nào ở trên (gồm dò lịch xe PMGPLX: cùng ngày, khung giờ, biển số — khi đã chọn khóa).
                            <a href="{{ route('daotao.pdt.dat.dieu-kien-canh-bao') }}">Chỉnh ngưỡng</a>
                            ·
                            <a href="{{ route('pmgplx.lich.xe.index') }}">Lịch xe tập</a>
                        @endif
                    </div>
                </div>
            </form>

            @if ($phanLoais->isEmpty())
                <div class="alert alert-info small py-2">
                    Chưa có phân loại phiên.
                    <a href="{{ route('daotao.pdt.dat.phan-loai-phien') }}">Tạo phân loại</a>
                    để gán hàng loạt cho các phiên đã chọn.
                </div>
            @endif

            <form method="POST" action="{{ route('daotao.pdt.dat.quan-ly-phien.phan-loai') }}" id="datBulkPhanLoaiForm">
                @csrf

                @if ($phanLoais->isNotEmpty())
                    <div class="border rounded p-2 mb-2 bg-light dat-bulk-bar">
                        <div class="d-flex flex-wrap align-items-center">
                            <span class="small font-weight-bold mr-3 mb-1">
                                Đã chọn: <span id="datSelectedCount">0</span> phiên
                            </span>
                            <span class="small text-muted mr-2 mb-1">Phân loại:</span>
                            <div class="d-flex flex-wrap mr-3 mb-1">
                                @foreach ($phanLoais as $pl)
                                    <label class="small mr-3 mb-0">
                                        <input type="checkbox" name="phan_loai_ids[]" value="{{ $pl->Id }}" class="mr-1">
                                        {{ $pl->TenPhanLoai }}
                                    </label>
                                @endforeach
                            </div>
                            <div class="d-flex flex-wrap align-items-center mb-1">
                                <select name="che_do" class="form-control form-control-sm mr-2" style="width:auto;">
                                    <option value="gan">Gán thêm</option>
                                    <option value="go">Bỏ gán</option>
                                    <option value="thay_the">Thay thế toàn bộ</option>
                                </select>
                                <button type="submit" class="btn btn-sm btn-navy" id="datBulkSubmit" disabled>
                                    Áp dụng phân loại
                                </button>
                            </div>
                        </div>
                        <div class="small text-muted">
                            <strong>Gán thêm</strong> giữ phân loại cũ; <strong>Bỏ gán</strong> gỡ các loại đã chọn;
                            <strong>Thay thế</strong> chỉ giữ các loại mới chọn.
                        </div>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                @if ($phanLoais->isNotEmpty())
                                    <th width="36" class="text-center">
                                        <input type="checkbox" id="datSelectAll" title="Chọn tất cả trang này">
                                    </th>
                                @endif
                                <th>#</th>
                                <th>Mã phiên học</th>
                                <th>Học viên</th>
                                <th>Khóa học</th>
                                <th>Giáo viên</th>
                                <th>Xe</th>
                                <th>Thời gian</th>
                                <th>TH (phút)</th>
                                <th>Tỉ lệ ND</th>
                                <th>Đạt</th>
                                <th>Phân loại</th>
                                <th>Cảnh báo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $item)
                                @php
                                    $loi = $violationsById[$item->Id] ?? [];
                                    $datPhien = \App\Support\DaoTao\DatDSPhienKiemTra::datPhien($violationsById, (int) $item->Id);
                                    $start = $item->ThoiGianBatDauPhienHoc;
                                    $end = $item->ThoiGianKetThucPhienHoc;
                                    $phut = ($start && $end) ? $start->diffInRealMinutes($end) : null;
                                    $coLoiNang = ! empty(array_intersect($loi, [
                                        \App\Support\DaoTao\DatDSPhienKiemTra::LOI_TI_LE_ND,
                                        \App\Support\DaoTao\DatDSPhienKiemTra::LOI_TRUNG_HV,
                                        \App\Support\DaoTao\DatDSPhienKiemTra::LOI_TRUNG_GV,
                                    ]));
                                @endphp
                                <tr @class([
                                    'dat-phien-row-loi' => $canAnalyzeViolations && $loi !== [],
                                    'table-danger' => $canAnalyzeViolations && $coLoiNang,
                                ])>
                                    @if ($phanLoais->isNotEmpty())
                                        <td class="text-center align-middle">
                                            <input type="checkbox" class="dat-phien-check" name="phien_ids[]"
                                                   value="{{ $item->Id }}">
                                        </td>
                                    @endif
                                    <td>{{ ($items->firstItem() ?? 0) + $loop->index }}</td>
                                    <td><code>{{ $item->MaPhienHoc }}</code></td>
                                    <td>
                                        <div>{{ $item->HoTenHocVien ?? '—' }}</div>
                                        <small class="text-muted">{{ $item->MaHocVien ?? '' }}</small>
                                    </td>
                                    <td>
                                        <div>
                                            {{ $item->TenKhoaHoc ?? '—' }}@if (! empty($item->LoaiKhoaHoc)) ({{ $item->LoaiKhoaHoc }})@endif
                                        </div>
                                        <small class="text-muted">{{ $item->MaKhoaHoc ?? '' }}</small>
                                    </td>
                                    <td>
                                        <div>{{ $item->HoTenGiaoVien ?? '—' }}</div>
                                        <small class="text-muted">{{ $item->MaGiaoVien ?? '' }}</small>
                                    </td>
                                    <td>{{ $item->BienSoXe ?? '—' }}</td>
                                    <td>
                                        <div>{{ $start?->format('d/m/Y H:i') ?? '—' }}</div>
                                        <small class="text-muted">{{ $end?->format('d/m/Y H:i') ?? '—' }}</small>
                                    </td>
                                    <td>
                                        @if ($phut !== null)
                                            {{ number_format($phut, 0) }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if ($item->TiLeNhanDien !== null)
                                            {{ rtrim(rtrim(number_format((float) $item->TiLeNhanDien, 2, '.', ''), '0'), '.') }}%
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if ($canAnalyzeViolations)
                                            @if ($datPhien)
                                                <span class="badge badge-success">Đạt</span>
                                            @else
                                                <span class="badge badge-secondary">Không đạt</span>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="dat-phien-badge-wrap">
                                        @forelse ($item->phanLoai as $pl)
                                            <span class="badge badge-info" title="{{ $pl->MoTa }}">{{ $pl->TenPhanLoai }}</span>
                                        @empty
                                            <span class="text-muted">—</span>
                                        @endforelse
                                    </td>
                                    <td class="dat-phien-badge-wrap">
                                        @if ($canAnalyzeViolations)
                                            @forelse ($loi as $code)
                                                @php $def = $loiDefinitions[$code] ?? null; @endphp
                                                @if ($def)
                                                    <span class="badge {{ $def['badge'] }} @if(!empty($def['label_lines'])) dat-loi-badge-multiline @endif"
                                                          title="{{ $def['label'] }}">
                                                        @if (! empty($def['label_lines']))
                                                            <span class="d-block">{{ $def['label_lines'][0] }}</span>
                                                            <span class="d-block">{{ $def['label_lines'][1] ?? '' }}</span>
                                                        @else
                                                            {{ $def['label'] }}
                                                        @endif
                                                    </span>
                                                @endif
                                            @empty
                                                <span class="text-muted">—</span>
                                            @endforelse
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $phanLoais->isNotEmpty() ? 13 : 12 }}" class="text-center py-4 text-muted">
                                        @if ($selectedLoi !== [] || $selectedPhanLoai !== [] || ($filters['dat'] ?? '') !== '' || ($filters['loai_khoa_hoc'] ?? '') !== '')
                                            Không có phiên nào khớp bộ lọc đã chọn.
                                        @else
                                            Chưa có dữ liệu phiên học.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </form>

            @if ($items->hasPages())
                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
                    <div class="text-muted small mb-2 mb-md-0">
                        Trang {{ $items->currentPage() }}/{{ max($items->lastPage(), 1) }}
                        · {{ number_format($items->total()) }} phiên
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
            noResults: function () { return 'Không tìm thấy học viên'; },
            loadingMore: function () { return 'Đang tải thêm...'; }
        }
    });
    $('#filter_ma_khoa_hoc').select2({
        theme: 'bootstrap4',
        placeholder: 'Tìm khóa học...',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#filter_ma_khoa_hoc').closest('.form-group'),
        language: {
            noResults: function () { return 'Không tìm thấy khóa học'; }
        }
    });
    $('#filter_ma_giao_vien').select2({
        theme: 'bootstrap4',
        placeholder: 'Tìm giáo viên...',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#filter_ma_giao_vien').closest('.form-group'),
        language: {
            noResults: function () { return 'Không tìm thấy giáo viên'; }
        }
    });
</script>
<script>
    (function () {
        var selectAll = document.getElementById('datSelectAll');
        var checks = document.querySelectorAll('.dat-phien-check');
        var countEl = document.getElementById('datSelectedCount');
        var submitBtn = document.getElementById('datBulkSubmit');
        var form = document.getElementById('datBulkPhanLoaiForm');

        function updateBulkState() {
            var selected = 0;
            checks.forEach(function (el) {
                if (el.checked) {
                    selected++;
                }
            });
            if (countEl) {
                countEl.textContent = String(selected);
            }
            if (submitBtn) {
                submitBtn.disabled = selected === 0;
            }
            if (selectAll) {
                selectAll.indeterminate = selected > 0 && selected < checks.length;
                selectAll.checked = checks.length > 0 && selected === checks.length;
            }
        }

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                checks.forEach(function (el) {
                    el.checked = selectAll.checked;
                });
                updateBulkState();
            });
        }

        checks.forEach(function (el) {
            el.addEventListener('change', updateBulkState);
        });

        if (form) {
            form.addEventListener('submit', function (e) {
                var selected = 0;
                checks.forEach(function (el) {
                    if (el.checked) {
                        selected++;
                    }
                });
                if (selected === 0) {
                    e.preventDefault();
                    alert('Chọn ít nhất một phiên.');
                    return;
                }
                var loaiChecked = form.querySelectorAll('input[name="phan_loai_ids[]"]:checked').length;
                if (loaiChecked === 0) {
                    e.preventDefault();
                    alert('Chọn ít nhất một phân loại.');
                }
            });
        }

        updateBulkState();
    })();
</script>
@endpush
