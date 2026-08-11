@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Quản lý giáo viên')

@section('content')
    <div class="card card-panel">
        <div class="card-header">Thông tin tìm kiếm giáo viên</div>
        <div class="card-body">
            <form method="GET" action="{{ route('pmgplx.dm.giao-vien.index') }}">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-3">
                        <label>Mã|Tên|CMT|Điện thoại</label>
                        <input type="text" class="form-control form-control-sm" name="tu_khoa" value="{{ $filters['tu_khoa'] }}" placeholder="Nhập từ khóa">
                    </div>
                    <div class="form-group col-md-2">
                        <label>Hạng GPLX</label>
                        <input type="text" class="form-control form-control-sm" name="hang_gplx" value="{{ $filters['hang_gplx'] }}" placeholder="VD: B2, C">
                    </div>
                    <div class="form-group col-md-2">
                        <label for="filter_bien_so_xe">Xe</label>
                        <select name="bien_so_xe" id="filter_bien_so_xe" class="form-control form-control-sm">
                            <option value="">—Tất cả—</option>
                            @foreach ($xeTaps as $xe)
                                <option value="{{ $xe }}" @selected(($filters['bien_so_xe'] ?? '') === $xe)>{{ $xe }}</option>
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
                    <div class="form-group col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary btn-block">Tìm kiếm</button>
                        <a href="{{ route('pmgplx.dm.giao-vien.index') }}" class="btn btn-sm btn-outline-secondary btn-block mt-1" title="Làm mới">↻</a>
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
                    <form method="GET" action="{{ route('pmgplx.dm.giao-vien.index') }}" class="form-inline mr-3">
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
                            <th>Xe</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $index => $row)
                            @php $bienSo = trim((string) ($row->GhiChu ?? '')); @endphp
                            <tr>
                                <td>{{ $items->firstItem() + $index }}</td>
                                <td>
                                    @include('PMGPLX.danh-muc._trang-thai-icon', ['active' => (bool) $row->TrangThai])
                                    {{ $row->MaGV }}
                                </td>
                                <td>{{ $row->ho_ten }}</td>
                                <td>
                                    @php
                                        $ns = (string) ($row->NgaySinh ?? '');
                                        // GiaoVien lưu NgaySinh dạng ddmmyyyy
                                        echo \App\Support\PMGPLX\NgayVn::format($ns, 'ddmmyyyy');
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
                                <td class="text-center js-no-row-select">
                                    @if ($bienSo !== '')
                                        <button type="button"
                                            class="btn btn-link btn-sm p-0 text-primary btn-gan-xe"
                                            data-ma-gv="{{ $row->MaGV }}"
                                            data-ten-gv="{{ $row->ho_ten }}"
                                            data-bien-so="{{ $bienSo }}"
                                            title="Đổi xe">
                                            {{ $bienSo }}
                                        </button>
                                    @else
                                        <button type="button"
                                            class="btn btn-sm btn-outline-success btn-gan-xe"
                                            data-ma-gv="{{ $row->MaGV }}"
                                            data-ten-gv="{{ $row->ho_ten }}"
                                            data-bien-so=""
                                            title="Gắn xe">＋</button>
                                    @endif
                                </td>
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

    <div class="modal fade" id="modalGanXe" tabindex="-1" role="dialog" aria-labelledby="modalGanXeLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form method="POST" action="{{ route('pmgplx.dm.giao-vien.gan-xe') }}" class="modal-content">
                @csrf
                <input type="hidden" name="ma_gv" id="gan_xe_ma_gv">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalGanXeLabel">Gắn xe cho giáo viên</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Giáo viên: <strong id="gan_xe_ten_gv"></strong></p>
                    <div class="form-group mb-0">
                        <label for="gan_xe_bien_so">Biển số xe</label>
                        <select name="bien_so_xe" id="gan_xe_bien_so" class="form-control" required>
                            <option value="">-- Chọn xe --</option>
                            @foreach ($xeTaps as $xe)
                                <option value="{{ $xe }}">{{ $xe }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
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

        var $modal = $('#modalGanXe');
        var $select = $('#gan_xe_bien_so');
        var select2Ready = false;

        function ensureSelect2() {
            if (select2Ready) return;
            $select.select2({
                theme: 'bootstrap4',
                placeholder: 'Tìm biển số xe...',
                allowClear: true,
                width: '100%',
                dropdownParent: $modal
            });
            select2Ready = true;
        }

        $(document).on('click', '.btn-gan-xe', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var $btn = $(this);
            $('#gan_xe_ma_gv').val($btn.attr('data-ma-gv') || '');
            $('#gan_xe_ten_gv').text(($btn.attr('data-ten-gv') || '') + ' (' + ($btn.attr('data-ma-gv') || '') + ')');

            $modal.data('pending-bien-so', $btn.attr('data-bien-so') || '');
            $modal.modal('show');
        });

        $(document).on('click', '.js-no-row-select', function (e) {
            e.stopPropagation();
        });

        $modal.on('shown.bs.modal', function () {
            ensureSelect2();
            var bienSo = $modal.data('pending-bien-so') || '';
            $select.val(bienSo).trigger('change');
        });
    })();
</script>
@endpush
