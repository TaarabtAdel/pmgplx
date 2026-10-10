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
            @if (session('success'))
                <div class="alert alert-success py-2">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger py-2">{!! nl2br(e(session('error'))) !!}</div>
            @endif

            <div class="d-flex flex-wrap align-items-center mb-3">
                <div class="btn-group btn-group-sm mr-2 mb-2" role="group">
                    <button type="button" class="btn btn-success" disabled title="Sắp hỗ trợ">＋ Thêm mới</button>
                    <button type="button" class="btn btn-warning" disabled title="Sắp hỗ trợ">✎ Xem - Sửa</button>
                    <button type="button" class="btn btn-danger" disabled title="Sắp hỗ trợ">✕ Xóa</button>
                </div>

                <button type="button" class="btn btn-sm btn-primary mb-2 mr-2" id="btn-them-phan-cong" disabled
                        data-toggle="modal" data-target="#modal-phan-cong-hv">
                    Thêm vào phân công
                </button>

                <div class="mb-2 mr-2">
                    <a href="{{ route('pmgplx.dm.hoc-vien.nhap-file.create') }}"
                       class="btn btn-sm btn-success">
                        Nhập học viên mới từ file
                    </a>
                    <a href="{{ route('pmgplx.dm.hoc-vien.dong-bo.form', array_filter(['ma_kh_nguon' => $filters['ma_kh']])) }}"
                       class="btn btn-sm btn-info ml-1">
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
                            <th style="width:2.5rem">
                                <input type="checkbox" id="hv-check-all" title="Chọn tất cả trang này">
                            </th>
                            <th>STT</th>
                            <th>Mã ĐK</th>
                            <th>Họ và tên</th>
                            <th>Ngày sinh</th>
                            <th>Giới tính</th>
                            <th>Số CMT</th>
                            <th>Số hồ sơ</th>
                            <th>Mã khóa học</th>
                            <th>Giáo viên</th>
                            <th>Hạng GPLX</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $index => $row)
                            @php
                                $hoTenHv = $row->HoVaTen ?: trim(($row->HoDemNLX ?? '').' '.($row->TenNLX ?? ''));
                                $maKhHv = trim((string) ($row->MaKhoaHoc ?? ''));
                                $pcKey = \App\Support\DaoTao\DatPhanCongHocVienSaver::rowKeyForPhanCong($maKhHv, (string) $row->MaDK);
                                $pc = $phanCongByRowKey[$pcKey] ?? null;
                            @endphp
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" class="hv-row-check"
                                           data-ma-dk="{{ $row->MaDK }}"
                                           data-ma-kh="{{ $maKhHv }}"
                                           data-ho-ten="{{ $hoTenHv }}"
                                           @disabled($maKhHv === '')
                                           title="{{ $maKhHv === '' ? 'Thiếu mã khóa trên hồ sơ' : 'Chọn' }}">
                                </td>
                                <td>{{ $items->firstItem() + $index }}</td>
                                <td>
                                    @include('PMGPLX.danh-muc._trang-thai-icon', ['active' => (string) $row->TrangThai === '1'])
                                    {{ $row->MaDK }}
                                </td>
                                <td>{{ $hoTenHv }}</td>
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
                                <td class="small">
                                    @if ($pc && ($pc['ma_giao_vien'] ?? '') !== '')
                                        {{ $pc['ten_giao_vien'] ?? $pc['ma_giao_vien'] }}
                                        <div class="text-muted">{{ $pc['ma_giao_vien'] }}</div>
                                        @if (($pc['bien_so_xe'] ?? '') !== '' || ($pc['bien_so_xe_tu_dong'] ?? '') !== '')
                                            <div class="text-muted">
                                                @if (($pc['bien_so_xe'] ?? '') !== '')
                                                    xe {{ $pc['bien_so_xe'] }}
                                                @endif
                                                @if (($pc['bien_so_xe_tu_dong'] ?? '') !== '')
                                                    · TD {{ $pc['bien_so_xe_tu_dong'] }}
                                                @endif
                                            </div>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $row->HangGPLX }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-4">Không có dữ liệu</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-phan-cong-hv" tabindex="-1" role="dialog" aria-labelledby="modal-phan-cong-hv-label" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <form method="POST" action="{{ route('pmgplx.dm.hoc-vien.them-phan-cong') }}" id="form-phan-cong-hv">
                @csrf
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <h5 class="modal-title" id="modal-phan-cong-hv-label">Thêm vào phân công (ĐAT)</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted mb-2">Học viên đã chọn (<span id="pc-hv-count">0</span>):</p>
                        <ul class="list-unstyled small mb-3 border rounded p-2 bg-light" id="pc-hv-list" style="max-height: 10rem; overflow-y: auto;"></ul>
                        <div id="pc-hv-hidden-fields"></div>

                        <div class="form-row">
                            <div class="form-group col-md-12">
                                <label for="pc_ma_giao_vien" class="small mb-1">Giáo viên <span class="text-danger">*</span></label>
                                <select name="ma_giao_vien" id="pc_ma_giao_vien" class="form-control form-control-sm" required>
                                    <option value="">— Chọn giáo viên —</option>
                                    @foreach ($giaoViens as $gv)
                                        <option value="{{ $gv->MaGV }}">{{ $gv->ho_ten }} ({{ $gv->MaGV }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="pc_bien_so_xe" class="small mb-1">Biển số xe</label>
                                <select name="bien_so_xe" id="pc_bien_so_xe" class="form-control form-control-sm">
                                    <option value="">— Chọn hoặc để trống —</option>
                                    @foreach ($xeTaps as $xe)
                                        <option value="{{ $xe }}">{{ $xe }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Chọn GV → tự điền từ cột GhiChu (xe gắn GV).</small>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="pc_bien_so_xe_tu_dong" class="small mb-1">Biển số xe tự động</label>
                                <select name="bien_so_xe_tu_dong" id="pc_bien_so_xe_tu_dong" class="form-control form-control-sm">
                                    <option value="">— Chọn hoặc để trống —</option>
                                    @foreach ($xeTaps as $xe)
                                        <option value="{{ $xe }}">{{ $xe }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <a href="{{ route('daotao.pdt.dat.phan-cong-hoc-vien') }}" class="btn btn-sm btn-outline-secondary mr-auto" target="_blank">Mở danh sách phân công</a>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-sm btn-primary">Lưu phân công</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    var gvBienSoByMa = @json($gvBienSoByMa ?? []);

    function syncPhanCongButtonState() {
        var n = document.querySelectorAll('.hv-row-check:checked').length;
        var btn = document.getElementById('btn-them-phan-cong');
        if (btn) {
            btn.disabled = n === 0;
        }
    }

    function rebuildPhanCongModalLists() {
        var checks = document.querySelectorAll('.hv-row-check:checked');
        var listEl = document.getElementById('pc-hv-list');
        var hiddenEl = document.getElementById('pc-hv-hidden-fields');
        var countEl = document.getElementById('pc-hv-count');
        if (! listEl || ! hiddenEl) {
            return;
        }
        listEl.innerHTML = '';
        hiddenEl.innerHTML = '';
        var i = 0;
        checks.forEach(function (cb) {
            var maDk = cb.getAttribute('data-ma-dk') || '';
            var maKh = cb.getAttribute('data-ma-kh') || '';
            var hoTen = cb.getAttribute('data-ho-ten') || '';
            var li = document.createElement('li');
            li.className = 'mb-1';
            li.textContent = hoTen + ' · ' + maDk + (maKh ? ' · ' + maKh : '');
            listEl.appendChild(li);

            ['ma_dk', 'ma_khoa_hoc', 'ho_ten'].forEach(function (field, idx) {
                var inp = document.createElement('input');
                inp.type = 'hidden';
                inp.name = 'hoc_vien[' + i + '][' + field + ']';
                inp.value = idx === 0 ? maDk : (idx === 1 ? maKh : hoTen);
                hiddenEl.appendChild(inp);
            });
            i++;
        });
        if (countEl) {
            countEl.textContent = String(i);
        }
    }

    function fillBienSoFromGiaoVien() {
        var maGv = $('#pc_ma_giao_vien').val();
        var bien = maGv && gvBienSoByMa[maGv] ? gvBienSoByMa[maGv] : '';
        if (bien !== '') {
            $('#pc_bien_so_xe').val(bien).trigger('change');
        }
    }

    document.getElementById('hv-check-all')?.addEventListener('change', function () {
        var on = this.checked;
        document.querySelectorAll('.hv-row-check:not(:disabled)').forEach(function (cb) {
            cb.checked = on;
        });
        syncPhanCongButtonState();
    });

    document.querySelectorAll('.hv-row-check').forEach(function (cb) {
        cb.addEventListener('change', syncPhanCongButtonState);
    });

    $('#modal-phan-cong-hv').on('show.bs.modal', function () {
        rebuildPhanCongModalLists();
    });

    $('#pc_ma_giao_vien, #pc_bien_so_xe, #pc_bien_so_xe_tu_dong').select2({
        theme: 'bootstrap4',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#modal-phan-cong-hv')
    });

    $('#pc_ma_giao_vien').on('change select2:select', fillBienSoFromGiaoVien);

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
