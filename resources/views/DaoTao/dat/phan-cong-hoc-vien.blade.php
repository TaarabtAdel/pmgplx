@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Phân công học viên')

@push('styles')
<style>
    .dat-pc-filter .form-group {
        margin-bottom: 0.75rem;
    }
    .dat-xoa-khoa-form .select2-container {
        width: 220px !important;
        flex: 0 0 auto;
    }
</style>
@endpush

@section('content')
    <div class="card card-panel mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <span>Phân công học viên</span>
            <div class="mt-1 mt-md-0">
                <a href="{{ route('daotao.pdt.dat.giao-vien-day-thay', array_filter(['ma_khoa_hoc' => $filters['ma_khoa_hoc'] ?? ''])) }}" class="btn btn-sm btn-outline-warning mr-1">GV dạy thay</a>
                <a href="{{ route('daotao.pdt.dat.nhap-phan-cong-hoc-vien') }}" class="btn btn-sm btn-navy mr-1">
                    Nhập từ Excel
                </a>
                <a href="{{ route('daotao.pdt.dat.quan-ly-phien') }}" class="btn btn-sm btn-outline-secondary">
                    Chi tiết phiên
                </a>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('daotao.pdt.dat.phan-cong-hoc-vien') }}" class="mb-3">
                <div class="border rounded p-3 bg-white dat-pc-filter">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                        <strong class="small mb-2 mb-md-0">Lọc phân công</strong>
                        <div class="d-flex flex-wrap">
                            <button type="submit" class="btn btn-sm btn-navy mr-2 mb-2 mb-md-0">Lọc</button>
                            <a href="{{ route('daotao.pdt.dat.phan-cong-hoc-vien') }}"
                               class="btn btn-sm btn-outline-secondary mb-2 mb-md-0">
                                Reset
                            </a>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label class="small text-muted mb-1" for="filter_ma_khoa_hoc">Mã khóa học</label>
                            <select name="ma_khoa_hoc" id="filter_ma_khoa_hoc" class="form-control form-control-sm">
                                <option value="">— Tất cả —</option>
                                @foreach ($khoaHocOptions as $maKh)
                                    <option value="{{ $maKh }}" @selected(($filters['ma_khoa_hoc'] ?? '') === $maKh)>
                                        {{ $maKh }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label class="small text-muted mb-1" for="filter_ma_giao_vien">Mã giáo viên</label>
                            <select name="ma_giao_vien" id="filter_ma_giao_vien" class="form-control form-control-sm">
                                <option value="">— Tất cả —</option>
                                @foreach ($giaoVienOptions as $maGv)
                                    @php
                                        $gv = $giaoVienNames[$maGv] ?? null;
                                        $tenGv = $gv ? trim(($gv->HoTenDem ?? '').' '.($gv->TenGV ?? '')) : '';
                                    @endphp
                                    <option value="{{ $maGv }}" @selected(($filters['ma_giao_vien'] ?? '') === $maGv)>
                                        @if ($tenGv !== '')
                                            {{ $tenGv }} ({{ $maGv }})
                                        @else
                                            {{ $maGv }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label class="small text-muted mb-1" for="filter_bien_so_xe">Biển số xe</label>
                            <select name="bien_so_xe" id="filter_bien_so_xe" class="form-control form-control-sm">
                                <option value="">— Tất cả —</option>
                                @foreach ($bienSoXeOptions as $xe)
                                    <option value="{{ $xe }}" @selected(($filters['bien_so_xe'] ?? '') === $xe)>
                                        {{ $xe }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label class="small text-muted mb-1" for="filter_tu_khoa">Mã HV / Họ tên</label>
                            <input type="text" name="tu_khoa" id="filter_tu_khoa"
                                   class="form-control form-control-sm"
                                   value="{{ $filters['tu_khoa'] ?? '' }}"
                                   placeholder="Tìm nhanh...">
                        </div>
                    </div>
                </div>
            </form>

            @if ($khoaHocOptions !== [])
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-2">
                    <span class="text-muted small mb-2 mb-md-0">
                        Tổng: <strong>{{ number_format($items->total()) }}</strong> bản ghi
                    </span>
                    <div class="d-flex flex-wrap align-items-center mb-2">
                        <form method="POST"
                              action="{{ route('daotao.pdt.dat.phan-cong-hoc-vien.destroy-by-course') }}"
                              class="d-flex align-items-center mr-3 dat-xoa-khoa-form"
                              onsubmit="return confirm('Xóa toàn bộ phân công của khóa đã chọn? Thao tác không hoàn tác.');">
                            @csrf
                            <label for="delete_ma_khoa_hoc" class="small text-muted mb-0 mr-2 text-nowrap">Xóa khóa:</label>
                            <select name="ma_khoa_hoc" id="delete_ma_khoa_hoc"
                                    class="form-control form-control-sm mr-2" required>
                                <option value="">— Chọn khóa —</option>
                                @foreach ($khoaHocOptions as $maKh)
                                    @php $cnt = (int) ($khoaHocCounts[$maKh] ?? 0); @endphp
                                    <option value="{{ $maKh }}"
                                            @selected(($filters['ma_khoa_hoc'] ?? '') === $maKh)>
                                        {{ $maKh }} ({{ number_format($cnt) }})
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-danger text-nowrap">Xóa</button>
                        </form>
                        <form method="GET" action="{{ route('daotao.pdt.dat.phan-cong-hoc-vien') }}" class="form-inline">
                            @foreach (request()->except(['per_page', 'page']) as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach
                            <label class="mr-2 mb-0 small">Số dòng/trang</label>
                            <select name="per_page" class="form-control form-control-sm" onchange="this.form.submit()">
                                @foreach ([20, 50, 100, 200] as $n)
                                    <option value="{{ $n }}" @selected((int) ($filters['per_page'] ?? 50) === $n)>{{ $n }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                </div>
            @else
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-2">
                    <span class="text-muted small mb-2 mb-md-0">
                        Tổng: <strong>{{ number_format($items->total()) }}</strong> bản ghi
                    </span>
                    <form method="GET" action="{{ route('daotao.pdt.dat.phan-cong-hoc-vien') }}" class="form-inline mb-2">
                        @foreach (request()->except(['per_page', 'page']) as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        <label class="mr-2 mb-0 small">Số dòng/trang</label>
                        <select name="per_page" class="form-control form-control-sm" onchange="this.form.submit()">
                            @foreach ([20, 50, 100, 200] as $n)
                                <option value="{{ $n }}" @selected((int) ($filters['per_page'] ?? 50) === $n)>{{ $n }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Khóa</th>
                            <th>Mã HV</th>
                            <th>Họ tên</th>
                            <th>Mã GV</th>
                            <th>Giáo viên</th>
                            <th>Xe số sàn</th>
                            <th>Xe tự động</th>
                            <th>Nhập lúc</th>
                            <th>File nguồn</th>
                            <th width="80"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            @php
                                $gv = $giaoVienNames[$item->MaGiaoVien] ?? null;
                                $tenGv = $gv ? trim(($gv->HoTenDem ?? '').' '.($gv->TenGV ?? '')) : '';
                            @endphp
                            <tr>
                                <td>{{ ($items->firstItem() ?? 0) + $loop->index }}</td>
                                <td><code>{{ $item->MaKhoaHoc }}</code></td>
                                <td><code>{{ $item->MaHocVien }}</code></td>
                                <td>{{ $item->HoTenHocVien ?: '—' }}</td>
                                <td><code>{{ $item->MaGiaoVien }}</code></td>
                                <td>{{ $tenGv !== '' ? $tenGv : '—' }}</td>
                                <td>{{ $item->BienSoXe ?: '—' }}</td>
                                <td>{{ $item->BienSoXeTuDong ?: '—' }}</td>
                                <td class="text-nowrap small">
                                    @if ($item->NgayNhap)
                                        {{ $item->NgayNhap->format('d/m/Y H:i') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $item->FileNguon ?: '—' }}</td>
                                <td class="text-nowrap">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary py-0 btn-sua-phan-cong"
                                            data-id="{{ $item->Id }}"
                                            data-ma-khoa-hoc="{{ $item->MaKhoaHoc }}"
                                            data-ma-hoc-vien="{{ $item->MaHocVien }}"
                                            data-ho-ten="{{ $item->HoTenHocVien }}"
                                            data-ma-giao-vien="{{ $item->MaGiaoVien }}"
                                            data-bien-so-xe="{{ $item->BienSoXe }}"
                                            data-bien-so-xe-tu-dong="{{ $item->BienSoXeTuDong }}">
                                        Sửa
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-4 text-muted">
                                    Chưa có phân công.
                                    <a href="{{ route('daotao.pdt.dat.nhap-phan-cong-hoc-vien') }}">Nhập từ Excel</a>
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
                    </div>
                    {{ $items->links('pagination::bootstrap-4') }}
                </div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="modalSuaPhanCong" tabindex="-1" role="dialog" aria-labelledby="modalSuaPhanCongLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form method="POST" action="{{ route('daotao.pdt.dat.nhap-phan-cong-hoc-vien.save') }}" class="modal-content" id="formSuaPhanCong">
                @csrf
                <input type="hidden" name="id" id="pc_edit_id" value="{{ old('id') }}">
                <input type="hidden" name="ma_khoa_hoc" id="pc_edit_ma_khoa_hoc" value="{{ old('ma_khoa_hoc') }}">

                <div class="modal-header py-2">
                    <h5 class="modal-title" id="modalSuaPhanCongLabel">Sửa phân công học viên</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-2">
                        <label class="small text-muted mb-0">Khóa</label>
                        <div><code id="pc_edit_khoa_display"></code></div>
                    </div>

                    <div class="form-group mb-2">
                        <label for="pc_edit_ma_hoc_vien" class="small mb-1">Mã học viên</label>
                        <input type="text" name="ma_hoc_vien" id="pc_edit_ma_hoc_vien"
                               class="form-control form-control-sm @error('ma_hoc_vien') is-invalid @enderror"
                               value="{{ old('ma_hoc_vien') }}" required>
                        @error('ma_hoc_vien')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group mb-2">
                        <label for="pc_edit_ho_ten_hoc_vien" class="small mb-1">Họ tên</label>
                        <input type="text" name="ho_ten_hoc_vien" id="pc_edit_ho_ten_hoc_vien"
                               class="form-control form-control-sm @error('ho_ten_hoc_vien') is-invalid @enderror"
                               value="{{ old('ho_ten_hoc_vien') }}">
                        @error('ho_ten_hoc_vien')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group mb-2">
                        <label for="pc_edit_ma_giao_vien" class="small mb-1">Mã giáo viên</label>
                        <input type="text" name="ma_giao_vien" id="pc_edit_ma_giao_vien"
                               class="form-control form-control-sm @error('ma_giao_vien') is-invalid @enderror"
                               value="{{ old('ma_giao_vien') }}" required>
                        @error('ma_giao_vien')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group mb-2">
                        <label for="pc_edit_bien_so_xe" class="small mb-1">Xe số sàn</label>
                        <input type="text" name="bien_so_xe" id="pc_edit_bien_so_xe"
                               class="form-control form-control-sm @error('bien_so_xe') is-invalid @enderror"
                               value="{{ old('bien_so_xe') }}">
                        @error('bien_so_xe')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group mb-0">
                        <label for="pc_edit_bien_so_xe_tu_dong" class="small mb-1">Xe tự động</label>
                        <input type="text" name="bien_so_xe_tu_dong" id="pc_edit_bien_so_xe_tu_dong"
                               class="form-control form-control-sm @error('bien_so_xe_tu_dong') is-invalid @enderror"
                               value="{{ old('bien_so_xe_tu_dong') }}">
                        @error('bien_so_xe_tu_dong')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-sm btn-navy">Lưu</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        $('#filter_ma_khoa_hoc, #filter_ma_giao_vien, #filter_bien_so_xe').select2({
            theme: 'bootstrap4',
            allowClear: true,
            width: '100%',
            placeholder: '— Tất cả —'
        });

        $('#delete_ma_khoa_hoc').select2({
            theme: 'bootstrap4',
            allowClear: true,
            width: '220px',
            placeholder: '— Chọn khóa —'
        });

        var $modal = $('#modalSuaPhanCong');

        function fillModal(data) {
            $('#pc_edit_id').val(data.id || '');
            $('#pc_edit_ma_khoa_hoc').val(data.maKhoaHoc || '');
            $('#pc_edit_khoa_display').text(data.maKhoaHoc || '—');
            $('#pc_edit_ma_hoc_vien').val(data.maHocVien || '');
            $('#pc_edit_ho_ten_hoc_vien').val(data.hoTen || '');
            $('#pc_edit_ma_giao_vien').val(data.maGiaoVien || '');
            $('#pc_edit_bien_so_xe').val(data.bienSoXe || '');
            $('#pc_edit_bien_so_xe_tu_dong').val(data.bienSoXeTuDong || '');
        }

        $(document).on('click', '.btn-sua-phan-cong', function () {
            var $btn = $(this);
            fillModal({
                id: $btn.attr('data-id'),
                maKhoaHoc: $btn.attr('data-ma-khoa-hoc'),
                maHocVien: $btn.attr('data-ma-hoc-vien'),
                hoTen: $btn.attr('data-ho-ten') || '',
                maGiaoVien: $btn.attr('data-ma-giao-vien'),
                bienSoXe: $btn.attr('data-bien-so-xe') || '',
                bienSoXeTuDong: $btn.attr('data-bien-so-xe-tu-dong') || ''
            });
            $modal.modal('show');
        });

        $modal.on('shown.bs.modal', function () {
            $('#pc_edit_ma_hoc_vien').trigger('focus');
        });

        @if ($errors->any() && old('id'))
            fillModal({
                id: @json(old('id')),
                maKhoaHoc: @json(old('ma_khoa_hoc')),
                maHocVien: @json(old('ma_hoc_vien')),
                hoTen: @json(old('ho_ten_hoc_vien', '')),
                maGiaoVien: @json(old('ma_giao_vien')),
                bienSoXe: @json(old('bien_so_xe', '')),
                bienSoXeTuDong: @json(old('bien_so_xe_tu_dong', ''))
            });
            $modal.modal('show');
        @endif
    })();
</script>
@endpush
