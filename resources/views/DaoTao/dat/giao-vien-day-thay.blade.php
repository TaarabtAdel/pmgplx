@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Giáo viên dạy thay')

@section('content')
    <div class="card card-panel mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <span>Giáo viên dạy thay</span>
            <div class="mt-1 mt-md-0">
                <a href="{{ route('daotao.pdt.dat.phan-cong-hoc-vien') }}" class="btn btn-sm btn-outline-primary mr-1">
                    Phân công học viên
                </a>
                <a href="{{ route('daotao.pdt.dat.quan-ly-phien') }}" class="btn btn-sm btn-outline-secondary">
                    Chi tiết phiên
                </a>
            </div>
        </div>
        <div class="card-body">
            <p class="small text-muted mb-3">
                Khai báo GV dạy thay theo <strong>khóa học + GV chính</strong>, áp dụng cho mọi học viên cùng khóa và cùng GV.
                Phiên DAT trong khoảng ngày sẽ so mã GV phiên với GV dạy thay (cảnh báo
                <strong>Giáo viên khác phân công HV</strong>).
            </p>

            <form method="GET" action="{{ route('daotao.pdt.dat.giao-vien-day-thay') }}" class="form-inline mb-3" id="gvtKhoaForm">
                <label class="small text-muted mb-0 mr-2 text-nowrap" for="filter_ma_khoa_hoc">Khóa học</label>
                <select name="ma_khoa_hoc" id="filter_ma_khoa_hoc" class="form-control form-control-sm mr-2" style="min-width: 220px;">
                    <option value="">— Chọn khóa —</option>
                    @foreach ($khoaHocOptions as $maKh)
                        <option value="{{ $maKh }}" @selected($selectedKhoa === $maKh)>{{ $maKh }}</option>
                    @endforeach
                </select>
                <span class="small text-muted">Chọn khóa để hiện danh sách GV chính.</span>
            </form>

            @if ($selectedKhoa !== '')
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-striped table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>GV chính</th>
                                <th width="90" class="text-center">Số HV</th>
                                <th width="110" class="text-center">Số khai báo</th>
                                <th width="100"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($giaoVienGocRows as $gvRow)
                                @php
                                    $gvGoc = $giaoVienNames[$gvRow['ma_giao_vien_goc']] ?? null;
                                    $tenGvGoc = $gvGoc ? trim(($gvGoc->HoTenDem ?? '').' '.($gvGoc->TenGV ?? '')) : '';
                                @endphp
                                <tr>
                                    <td>
                                        @if ($tenGvGoc !== '')
                                            {{ $tenGvGoc }} (<code>{{ $gvRow['ma_giao_vien_goc'] }}</code>)
                                        @else
                                            <code>{{ $gvRow['ma_giao_vien_goc'] }}</code>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ number_format($gvRow['so_hv']) }}</td>
                                    <td class="text-center">
                                        @if ($gvRow['so_khai_bao'] === 0)
                                            <span class="text-muted">—</span>
                                        @else
                                            {{ number_format($gvRow['so_khai_bao']) }}
                                        @endif
                                    </td>
                                    <td class="text-nowrap">
                                        <button type="button"
                                                class="btn btn-sm btn-warning py-0 btn-giao-vien-thay"
                                                data-ma-khoa-hoc="{{ $gvRow['ma_khoa_hoc'] }}"
                                                data-ma-giao-vien-goc="{{ $gvRow['ma_giao_vien_goc'] }}"
                                                data-ten-giao-vien-goc="{{ $tenGvGoc }}"
                                                data-substitutes='@json($gvRow['substitutes'])'>
                                            Quản lý
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-muted text-center py-4">
                                        Không có phân công nào trong khóa <code>{{ $selectedKhoa }}</code>.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted mb-0">Chọn khóa học ở trên để bắt đầu.</p>
            @endif
        </div>
    </div>

    <div class="modal fade" id="modalGiaoVienThay" tabindex="-1" role="dialog" aria-labelledby="modalGiaoVienThayLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title" id="modalGiaoVienThayLabel">Giáo viên dạy thay</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="small mb-3">
                        <strong>Khóa:</strong> <code id="gvt_khoa"></code>
                        ·
                        <strong>GV chính:</strong> <span id="gvt_gv_chinh"></span>
                    </div>

                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Giáo viên dạy thay</th>
                                    <th>Từ ngày</th>
                                    <th>Đến ngày</th>
                                    <th width="70"></th>
                                </tr>
                            </thead>
                            <tbody id="gvt_list_body">
                                <tr>
                                    <td colspan="4" class="text-muted text-center py-3">Chưa có giáo viên dạy thay.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <form method="POST" action="{{ route('daotao.pdt.dat.giao-vien-day-thay.store') }}" id="formGiaoVienThay">
                        @csrf
                        <input type="hidden" name="ma_khoa_hoc" id="gvt_form_ma_khoa_hoc" value="{{ old('ma_khoa_hoc') }}">
                        <input type="hidden" name="ma_giao_vien_goc" id="gvt_form_ma_giao_vien_goc" value="{{ old('ma_giao_vien_goc') }}">
                        <div class="border rounded p-3 bg-light">
                            <strong class="small d-block mb-2">Thêm giáo viên dạy thay</strong>
                            <div class="form-row">
                                <div class="form-group col-md-5 mb-2">
                                    <label for="gvt_ma_giao_vien" class="small mb-1">Giáo viên dạy thay</label>
                                    <select name="ma_giao_vien" id="gvt_ma_giao_vien"
                                            class="form-control form-control-sm @error('ma_giao_vien') is-invalid @enderror"
                                            required>
                                        <option value="">— Chọn giáo viên —</option>
                                        @foreach ($giaoVienSelectOptions as $gvOption)
                                            @php
                                                $tenGvOption = trim(($gvOption->HoTenDem ?? '').' '.($gvOption->TenGV ?? ''));
                                            @endphp
                                            <option value="{{ $gvOption->MaGV }}"
                                                    @selected(old('ma_giao_vien') === $gvOption->MaGV)>
                                                @if ($tenGvOption !== '')
                                                    {{ $tenGvOption }} ({{ $gvOption->MaGV }})
                                                @else
                                                    {{ $gvOption->MaGV }}
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('ma_giao_vien')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group col-md-3 mb-2">
                                    <label for="gvt_tu_ngay" class="small mb-1">Từ ngày</label>
                                    <input type="date" name="tu_ngay" id="gvt_tu_ngay"
                                           class="form-control form-control-sm @error('tu_ngay') is-invalid @enderror"
                                           value="{{ old('tu_ngay') }}" required>
                                    @error('tu_ngay')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group col-md-4 mb-2">
                                    <label for="gvt_den_ngay" class="small mb-1">Đến ngày</label>
                                    <input type="date" name="den_ngay" id="gvt_den_ngay"
                                           class="form-control form-control-sm @error('den_ngay') is-invalid @enderror"
                                           value="{{ old('den_ngay') }}">
                                    <small class="text-muted">Để trống = mở đến khi có bản ghi mới</small>
                                    @error('den_ngay')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="text-right mt-2">
                                <button type="submit" class="btn btn-sm btn-navy px-4">Thêm</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        var $gvtModal = $('#modalGiaoVienThay');
        var selectedKhoa = @json($selectedKhoa);
        var gvtDestroyUrlBase = @json(route('daotao.pdt.dat.giao-vien-day-thay.destroy', ['id' => 0]));
        var csrfToken = @json(csrf_token());
        var gvtTenByMa = @json(
            $giaoVienSelectOptions->mapWithKeys(function ($gv): array {
                $ten = trim(($gv->HoTenDem ?? '').' '.($gv->TenGV ?? ''));

                return [(string) $gv->MaGV => $ten];
            })->all()
        );

        $('#filter_ma_khoa_hoc').select2({
            theme: 'bootstrap4',
            allowClear: true,
            width: '220px',
            placeholder: '— Chọn khóa —'
        });

        $('#filter_ma_khoa_hoc').on('change', function () {
            $('#gvtKhoaForm').submit();
        });

        function escapeHtml(text) {
            return $('<div>').text(text || '').html();
        }

        function renderGvThayCell(maGv) {
            var ten = (gvtTenByMa[maGv] || '').trim();
            if (ten !== '') {
                return escapeHtml(ten) + ' (<code>' + escapeHtml(maGv) + '</code>)';
            }

            return '<code>' + escapeHtml(maGv) + '</code>';
        }

        function formatIsoDate(iso) {
            if (!iso) {
                return '—';
            }

            var parts = iso.split('-');
            if (parts.length !== 3) {
                return iso;
            }

            return parts[2] + '/' + parts[1] + '/' + parts[0];
        }

        function renderSubstituteList(substitutes) {
            var $body = $('#gvt_list_body');
            $body.empty();

            if (!substitutes || substitutes.length === 0) {
                $body.append('<tr><td colspan="4" class="text-muted text-center py-3">Chưa có giáo viên dạy thay.</td></tr>');
                return;
            }

            substitutes.forEach(function (row) {
                var deleteUrl = gvtDestroyUrlBase.replace(/0$/, String(row.id));
                if (selectedKhoa) {
                    deleteUrl += (deleteUrl.indexOf('?') >= 0 ? '&' : '?') + 'ma_khoa_hoc=' + encodeURIComponent(selectedKhoa);
                }
                var denNgay = row.den_ngay ? formatIsoDate(row.den_ngay) : '—';
                $body.append(
                    '<tr>' +
                        '<td>' + renderGvThayCell(row.ma_giao_vien) + '</td>' +
                        '<td>' + formatIsoDate(row.tu_ngay) + '</td>' +
                        '<td>' + denNgay + '</td>' +
                        '<td class="text-nowrap">' +
                            '<form method="POST" action="' + deleteUrl + '" class="d-inline" onsubmit="return confirm(\'Xóa giáo viên dạy thay này?\');">' +
                                '<input type="hidden" name="_token" value="' + csrfToken + '">' +
                                '<input type="hidden" name="_method" value="DELETE">' +
                                '<button type="submit" class="btn btn-sm btn-outline-danger py-0">Xóa</button>' +
                            '</form>' +
                        '</td>' +
                    '</tr>'
                );
            });
        }

        function fillGiaoVienThayModal(data) {
            $('#gvt_form_ma_khoa_hoc').val(data.maKhoaHoc || '');
            $('#gvt_form_ma_giao_vien_goc').val(data.maGiaoVienGoc || '');
            $('#gvt_khoa').text(data.maKhoaHoc || '—');

            var maGvGoc = data.maGiaoVienGoc || '';
            var tenGvGoc = (data.tenGiaoVienGoc || '').trim();
            if (tenGvGoc !== '') {
                $('#gvt_gv_chinh').html(escapeHtml(tenGvGoc) + ' (<code>' + escapeHtml(maGvGoc) + '</code>)');
            } else {
                $('#gvt_gv_chinh').html('<code>' + escapeHtml(maGvGoc || '—') + '</code>');
            }

            $('#gvt_ma_giao_vien').val(null).trigger('change');
            renderSubstituteList(data.substitutes || []);
        }

        $('#gvt_ma_giao_vien').select2({
            theme: 'bootstrap4',
            placeholder: 'Chọn giáo viên...',
            allowClear: true,
            width: '100%',
            dropdownParent: $gvtModal,
            language: {
                noResults: function () { return 'Không tìm thấy giáo viên'; }
            }
        });

        $(document).on('click', '.btn-giao-vien-thay', function () {
            var $btn = $(this);
            var substitutes = [];

            try {
                substitutes = JSON.parse($btn.attr('data-substitutes') || '[]');
            } catch (e) {
                substitutes = [];
            }

            fillGiaoVienThayModal({
                maKhoaHoc: $btn.attr('data-ma-khoa-hoc'),
                maGiaoVienGoc: $btn.attr('data-ma-giao-vien-goc'),
                tenGiaoVienGoc: $btn.attr('data-ten-giao-vien-goc') || '',
                substitutes: substitutes
            });
            $gvtModal.modal('show');
        });

        @if ($errors->any() && old('ma_khoa_hoc') && old('ma_giao_vien_goc'))
            fillGiaoVienThayModal({
                maKhoaHoc: @json(old('ma_khoa_hoc')),
                maGiaoVienGoc: @json(old('ma_giao_vien_goc')),
                tenGiaoVienGoc: '',
                substitutes: []
            });
            $('#gvt_ma_giao_vien').val(@json(old('ma_giao_vien'))).trigger('change');
            $('#gvt_tu_ngay').val(@json(old('tu_ngay')));
            $('#gvt_den_ngay').val(@json(old('den_ngay')));
            $gvtModal.modal('show');
        @endif
    })();
</script>
@endpush
