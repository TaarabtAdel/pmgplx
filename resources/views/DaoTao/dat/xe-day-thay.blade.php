@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Xe dạy thay')

@section('content')
    <div class="card card-panel mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <span>Xe dạy thay</span>
            <div class="mt-1 mt-md-0">
                <a href="{{ route('daotao.pdt.dat.giao-vien-day-thay', array_filter(['ma_khoa_hoc' => $selectedKhoa])) }}" class="btn btn-sm btn-outline-warning mr-1">
                    GV dạy thay
                </a>
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
                Khai báo xe thay theo <strong>khóa học + GV chính + xe gốc</strong> (biển trên phân công HV).
                Phiên DAT trong khoảng ngày sẽ so biển số phiên với xe thay (cảnh báo
                <strong>Xe khác phân công HV</strong>). Xe tự động trên phân công vẫn được chấp nhận.
            </p>

            <form method="GET" action="{{ route('daotao.pdt.dat.xe-day-thay') }}" class="form-inline mb-3" id="xdtKhoaForm">
                <label class="small text-muted mb-0 mr-2 text-nowrap" for="filter_ma_khoa_hoc">Khóa học</label>
                <select name="ma_khoa_hoc" id="filter_ma_khoa_hoc" class="form-control form-control-sm mr-2" style="min-width: 220px;">
                    <option value="">— Chọn khóa —</option>
                    @foreach ($khoaHocOptions as $kh)
                        <option value="{{ $kh->MaKhoaHoc }}" @selected($selectedKhoa === $kh->MaKhoaHoc)>
                            @if ($kh->TenKhoaHoc !== '')
                                {{ $kh->TenKhoaHoc }} ({{ $kh->MaKhoaHoc }})
                            @else
                                {{ $kh->MaKhoaHoc }}
                            @endif
                        </option>
                    @endforeach
                </select>
                <span class="small text-muted">Chọn khóa để hiện nhóm GV + xe gốc.</span>
            </form>

            @if ($selectedKhoa !== '')
                @if (! empty($coKhaiBaoThay))
                    <div class="mb-3">
                        <a href="{{ route('daotao.pdt.dat.xe-day-thay.preview-apply-lich', ['ma_khoa_hoc' => $selectedKhoa]) }}"
                           class="btn btn-sm btn-navy">
                            Áp dụng vào lịch PMGPLX (xem trước)
                        </a>
                        <span class="small text-muted ml-2">
                            Chỉ cập nhật <code>BienSoXe</code> trên <code>KhoaHoc_GiaoVien</code> và <code>KhoaHoc_XeTap</code> — không đổi GV.
                            Xóa khai báo sẽ hoàn biển xe gốc trên lịch (nếu đã áp dụng).
                        </span>
                    </div>
                @endif
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-striped table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>GV chính</th>
                                <th>Xe gốc</th>
                                <th width="90" class="text-center">Số HV</th>
                                <th>Xe thay (từ – đến)</th>
                                <th width="100"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($xeGocRows as $xeRow)
                                @php
                                    $gvGoc = $giaoVienNames[$xeRow['ma_giao_vien_goc']] ?? null;
                                    $tenGvGoc = $gvGoc ? trim(($gvGoc->HoTenDem ?? '').' '.($gvGoc->TenGV ?? '')) : '';
                                @endphp
                                <tr>
                                    <td>
                                        @if ($tenGvGoc !== '')
                                            {{ $tenGvGoc }} (<code>{{ $xeRow['ma_giao_vien_goc'] }}</code>)
                                        @else
                                            <code>{{ $xeRow['ma_giao_vien_goc'] }}</code>
                                        @endif
                                    </td>
                                    <td><code>{{ $xeRow['bien_so_xe_goc'] }}</code></td>
                                    <td class="text-center">{{ number_format($xeRow['so_hv']) }}</td>
                                    <td class="small">
                                        @if ($xeRow['so_khai_bao'] === 0)
                                            <span class="text-muted">—</span>
                                        @else
                                            @foreach ($xeRow['substitutes'] as $sub)
                                                @php
                                                    $tuSub = ! empty($sub['tu_ngay']) ? \Carbon\Carbon::parse($sub['tu_ngay'])->format('d/m/Y') : '—';
                                                    $denSub = ! empty($sub['den_ngay'])
                                                        ? \Carbon\Carbon::parse($sub['den_ngay'])->format('d/m/Y')
                                                        : 'khi có khai báo mới';
                                                @endphp
                                                <div class="mb-1">
                                                    <code>{{ $sub['bien_so_xe'] }}</code>
                                                    · từ <span class="text-nowrap">{{ $tuSub }}</span>
                                                    đến <span class="text-nowrap">{{ $denSub }}</span>
                                                </div>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td class="text-nowrap">
                                        <button type="button"
                                                class="btn btn-sm btn-warning py-0 btn-xe-thay"
                                                data-ma-khoa-hoc="{{ $xeRow['ma_khoa_hoc'] }}"
                                                data-ma-giao-vien-goc="{{ $xeRow['ma_giao_vien_goc'] }}"
                                                data-ten-giao-vien-goc="{{ $tenGvGoc }}"
                                                data-bien-so-xe-goc="{{ $xeRow['bien_so_xe_goc'] }}"
                                                data-substitutes='@json($xeRow['substitutes'])'
                                                data-teaching-spans='@json($xeRow['teaching_spans'] ?? [])'>
                                            Quản lý
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-muted text-center py-4">
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

    <div class="modal fade" id="modalXeThay" tabindex="-1" role="dialog" aria-labelledby="modalXeThayLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header py-2 align-items-start">
                    <div class="pr-4 flex-grow-1">
                        <h5 class="modal-title mb-1" id="modalXeThayLabel">Xe dạy thay</h5>
                        <div id="xdt_modal_headline" class="small mb-0"></div>
                    </div>
                    <button type="button" class="close mt-0" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="small mb-2">
                        <strong>Khóa:</strong> <code id="xdt_khoa"></code>
                        ·
                        <strong>Xe được thay:</strong> <code id="xdt_xe_goc"></code>
                        ·
                        <strong>GV:</strong> <span id="xdt_gv_chinh"></span>
                    </div>

                    <div id="xdt_sub_summary" class="alert alert-info small py-2 mb-3 d-none"></div>

                    <p class="small font-weight-bold mb-2">Danh sách khai báo xe thay</p>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Xe thay</th>
                                    <th>Khoảng dùng xe</th>
                                    <th width="130"></th>
                                </tr>
                            </thead>
                            <tbody id="xdt_list_body">
                                <tr>
                                    <td colspan="3" class="text-muted text-center py-3">Chưa có xe thay.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <form method="POST" action="{{ route('daotao.pdt.dat.xe-day-thay.store') }}" id="formXeThay"
                          onsubmit="return confirmXdtStoreLich();">
                        @csrf
                        <input type="hidden" name="ma_khoa_hoc" id="xdt_form_ma_khoa_hoc" value="{{ old('ma_khoa_hoc') }}">
                        <input type="hidden" name="ma_giao_vien_goc" id="xdt_form_ma_giao_vien_goc" value="{{ old('ma_giao_vien_goc') }}">
                        <input type="hidden" name="bien_so_xe_goc" id="xdt_form_bien_so_xe_goc" value="{{ old('bien_so_xe_goc') }}">
                        <div class="border rounded p-3 bg-light">
                            <strong class="small d-block mb-2">Thêm xe thay</strong>
                            <div class="form-row">
                                <div class="form-group col-md-5 mb-2">
                                    <label for="xdt_bien_so_xe" class="small mb-1">Xe thay</label>
                                    <select name="bien_so_xe" id="xdt_bien_so_xe"
                                            class="form-control form-control-sm @error('bien_so_xe') is-invalid @enderror"
                                            required>
                                        <option value="">— Chọn xe —</option>
                                        @foreach ($xeSelectOptions as $bienSo)
                                            <option value="{{ $bienSo }}"
                                                    @selected(old('bien_so_xe') === $bienSo)>
                                                {{ $bienSo }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Danh sách từ PMGPLX (<code>XeTap</code>) + biển trên phân công khóa; có thể gõ biển mới.</small>
                                    @error('bien_so_xe')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group col-md-3 mb-2">
                                    <label for="xdt_tu_ngay" class="small mb-1">Từ ngày</label>
                                    <input type="date" name="tu_ngay" id="xdt_tu_ngay"
                                           class="form-control form-control-sm @error('tu_ngay') is-invalid @enderror"
                                           value="{{ old('tu_ngay') }}" required>
                                    @error('tu_ngay')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group col-md-4 mb-2">
                                    <label for="xdt_den_ngay" class="small mb-1">Đến ngày</label>
                                    <input type="date" name="den_ngay" id="xdt_den_ngay"
                                           class="form-control form-control-sm @error('den_ngay') is-invalid @enderror"
                                           value="{{ old('den_ngay') }}">
                                    <small class="text-muted d-block">Để trống = mở đến khi có bản ghi mới (vẫn trong khoảng đang dạy khóa)</small>
                                    @error('den_ngay')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <p id="xdt_date_bounds_hint" class="small text-muted mb-2"></p>
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input" id="xdt_ap_dung_lich" name="ap_dung_lich" value="1">
                                <label class="custom-control-label small" for="xdt_ap_dung_lich">
                                    Áp dụng khai báo này vào lịch PMGPLX sau khi lưu (các buổi trùng ngày, biển vẫn là xe gốc trên lịch)
                                </label>
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
        var $xdtModal = $('#modalXeThay');
        var selectedKhoa = @json($selectedKhoa);
        var xdtDestroyUrlBase = @json(route('daotao.pdt.dat.xe-day-thay.destroy', ['id' => 0]));
        var xdtApplyUrlBase = @json(route('daotao.pdt.dat.xe-day-thay.apply-khai-bao-lich', ['id' => 0]));
        var csrfToken = @json(csrf_token());

        $('#filter_ma_khoa_hoc').select2({
            theme: 'bootstrap4',
            allowClear: true,
            width: '220px',
            placeholder: '— Chọn khóa —'
        });

        $('#filter_ma_khoa_hoc').on('change', function () {
            $('#xdtKhoaForm').submit();
        });

        function escapeHtml(text) {
            return $('<div>').text(text || '').html();
        }

        function appendMaKhoaQuery(url, maKhoa) {
            if (!maKhoa) {
                return url;
            }

            return url + (url.indexOf('?') >= 0 ? '&' : '?') + 'ma_khoa_hoc=' + encodeURIComponent(maKhoa);
        }

        function confirmXdtStoreLich() {
            if (!$('#xdt_ap_dung_lich').is(':checked')) {
                return true;
            }

            return confirm('Lưu khai báo và áp dụng xe thay vào lịch PMGPLX (GPLX_BAN_MOI)?');
        }

        function formatIsoDate(iso) {
            if (!iso) {
                return '—';
            }
            var parts = String(iso).split('T')[0].split('-');
            if (parts.length !== 3) {
                return iso;
            }
            return parts[2] + '/' + parts[1] + '/' + parts[0];
        }

        function formatDenNgayLabel(denNgay) {
            if (denNgay) {
                return formatIsoDate(denNgay);
            }

            return 'khi có khai báo mới';
        }

        function formatKhoangXeThay(tuNgay, denNgay) {
            return 'Từ <strong>' + formatIsoDate(tuNgay) + '</strong> đến <strong>' + escapeHtml(formatDenNgayLabel(denNgay)) + '</strong>';
        }

        function formatDenNgayWithFallback(denNgay, emptyLabel) {
            if (denNgay) {
                return formatIsoDate(denNgay);
            }

            return emptyLabel;
        }

        function formatDuocThayTuDen(tuNgay, denNgay) {
            return 'từ <strong>' + formatIsoDate(tuNgay) + '</strong> đến <strong>' +
                escapeHtml(formatDenNgayWithFallback(denNgay, 'khi có khai báo mới')) + '</strong>';
        }

        function formatDangDayKhoaTuDen(tuNgay, denNgay) {
            return 'từ <strong>' + formatIsoDate(tuNgay) + '</strong> đến <strong>' +
                escapeHtml(formatDenNgayWithFallback(denNgay, '—')) + '</strong>';
        }

        function renderTeachingSpanLines(xeGoc, tenGv, maGv, teachingSpans) {
            var xeLabel = xeGoc || '—';
            var tenGvLabel = (tenGv || '').trim() || maGv || '—';
            var gvPart = ' (GV <strong>' + escapeHtml(tenGvLabel) + '</strong>)';

            if (!teachingSpans || teachingSpans.length === 0) {
                return '<div class="mb-1 text-muted"><code>' + escapeHtml(xeLabel) + '</code>' + gvPart +
                    ': chưa xác định khoảng đang dạy khóa (thiếu phân công đào tạo / lịch PMGPLX).</div>';
            }

            return teachingSpans.map(function (span) {
                return '<div class="mb-1"><code>' + escapeHtml(xeLabel) + '</code>' + gvPart +
                    ' đang dùng trên khóa này ' + formatDangDayKhoaTuDen(span.tu_ngay, span.den_ngay) + '</div>';
            }).join('');
        }

        function renderSubstituteHeadlineLines(xeGoc, substitutes) {
            var xeLabel = xeGoc || '—';

            if (!substitutes || substitutes.length === 0) {
                return '<div class="mb-1 text-muted"><code>' + escapeHtml(xeLabel) + '</code> chưa có khoảng thời gian được thay xe.</div>';
            }

            return substitutes.map(function (row) {
                return '<div class="mb-1"><code>' + escapeHtml(xeLabel) + '</code> được thay xe ' +
                    formatDuocThayTuDen(row.tu_ngay, row.den_ngay) +
                    ' <span class="text-muted">· xe thay: <code>' + escapeHtml(row.bien_so_xe) + '</code></span></div>';
            }).join('');
        }

        function renderModalHeadline(substitutes, teachingSpans, xeGoc, tenGvGoc, maGvGoc) {
            var $title = $('#modalXeThayLabel');
            var $headline = $('#xdt_modal_headline');
            var xeLabel = xeGoc || '—';

            $title.text('Xe dạy thay — ' + xeLabel);

            var html = renderTeachingSpanLines(xeLabel, tenGvGoc, maGvGoc, teachingSpans) +
                renderSubstituteHeadlineLines(xeLabel, substitutes);

            if (!substitutes || substitutes.length === 0) {
                html += '<div class="small text-muted mt-1">Thêm xe thay và khoảng ngày ở form bên dưới.</div>';
            }

            $headline.html(html);
        }

        function renderSubstituteSummary(substitutes) {
            var $box = $('#xdt_sub_summary');
            $box.addClass('d-none').empty();
        }

        function renderSubstituteList(substitutes) {
            var $body = $('#xdt_list_body');
            $body.empty();

            if (!substitutes || substitutes.length === 0) {
                $body.append('<tr><td colspan="3" class="text-muted text-center py-3">Chưa có xe thay.</td></tr>');
                return;
            }

            substitutes.forEach(function (row) {
                var deleteUrl = appendMaKhoaQuery(xdtDestroyUrlBase.replace(/0$/, String(row.id)), selectedKhoa);
                var applyUrl = appendMaKhoaQuery(xdtApplyUrlBase.replace(/0$/, String(row.id)), selectedKhoa);
                $body.append(
                    '<tr>' +
                        '<td><code>' + escapeHtml(row.bien_so_xe) + '</code></td>' +
                        '<td class="small">' + formatKhoangXeThay(row.tu_ngay, row.den_ngay) + '</td>' +
                        '<td class="text-nowrap">' +
                            '<form method="POST" action="' + applyUrl + '" class="d-inline mr-1" onsubmit="return confirm(\'Áp dụng khai báo này vào lịch PMGPLX (đổi biển xe)?\');">' +
                                '<input type="hidden" name="_token" value="' + csrfToken + '">' +
                                '<button type="submit" class="btn btn-sm btn-navy py-0">Áp lịch</button>' +
                            '</form>' +
                            '<form method="POST" action="' + deleteUrl + '" class="d-inline" onsubmit="return confirm(\'Xóa khai báo và hoàn biển xe gốc trên lịch PMGPLX (các buổi đang là xe thay)?\');">' +
                                '<input type="hidden" name="_token" value="' + csrfToken + '">' +
                                '<input type="hidden" name="_method" value="DELETE">' +
                                '<button type="submit" class="btn btn-sm btn-outline-danger py-0">Xóa</button>' +
                            '</form>' +
                        '</td>' +
                    '</tr>'
                );
            });
        }

        function teachingEnvelope(spans) {
            if (!spans || spans.length === 0) {
                return null;
            }

            var min = null;
            var max = null;
            var openEnd = false;

            spans.forEach(function (span) {
                var tu = String(span.tu_ngay || '').split('T')[0];
                if (!tu) {
                    return;
                }
                if (min === null || tu < min) {
                    min = tu;
                }
                if (!span.den_ngay) {
                    openEnd = true;
                } else {
                    var den = String(span.den_ngay).split('T')[0];
                    if (max === null || den > max) {
                        max = den;
                    }
                }
            });

            if (min === null) {
                return null;
            }

            return { min: min, max: openEnd ? null : max };
        }

        function syncXdtDenNgayMin() {
            var $tu = $('#xdt_tu_ngay');
            var $den = $('#xdt_den_ngay');
            var floor = $tu.attr('min') || '';
            var tu = $tu.val() || '';
            if (tu && (!floor || tu > floor)) {
                floor = tu;
            }
            if (floor) {
                $den.attr('min', floor);
            }
        }

        function applyXdtTeachingDateBounds(teachingSpans) {
            var env = teachingEnvelope(teachingSpans);
            var $tu = $('#xdt_tu_ngay');
            var $den = $('#xdt_den_ngay');
            var $hint = $('#xdt_date_bounds_hint');
            var $submit = $('#formXeThay button[type="submit"]');

            if (!env) {
                $tu.removeAttr('min').removeAttr('max').prop('disabled', true);
                $den.removeAttr('min').removeAttr('max').prop('disabled', true);
                $hint.text('Chưa xác định khoảng đang dạy khóa — không thể thêm xe thay.');
                $submit.prop('disabled', true);
                return;
            }

            $tu.prop('disabled', false);
            $den.prop('disabled', false);
            $submit.prop('disabled', false);
            $tu.attr('min', env.min);
            if (env.max) {
                $tu.attr('max', env.max);
                $den.attr('max', env.max);
            } else {
                $tu.removeAttr('max');
                $den.removeAttr('max');
            }
            syncXdtDenNgayMin();

            var label = formatIsoDate(env.min) + ' – ' + (env.max ? formatIsoDate(env.max) : '…');
            $hint.text('Từ ngày / đến ngày phải nằm trong khoảng đang dạy khóa: ' + label + '.');
        }

        function fillXeThayModal(data) {
            $('#xdt_form_ma_khoa_hoc').val(data.maKhoaHoc || '');
            $('#xdt_form_ma_giao_vien_goc').val(data.maGiaoVienGoc || '');
            $('#xdt_form_bien_so_xe_goc').val(data.bienSoXeGoc || '');
            $('#xdt_khoa').text(data.maKhoaHoc || '—');
            $('#xdt_xe_goc').text(data.bienSoXeGoc || '—');

            var maGvGoc = data.maGiaoVienGoc || '';
            var tenGvGoc = (data.tenGiaoVienGoc || '').trim();
            var xeGoc = data.bienSoXeGoc || '';
            if (tenGvGoc !== '') {
                $('#xdt_gv_chinh').html(escapeHtml(tenGvGoc) + ' (<code>' + escapeHtml(maGvGoc) + '</code>)');
            } else {
                $('#xdt_gv_chinh').html('<code>' + escapeHtml(maGvGoc || '—') + '</code>');
            }

            var substitutes = data.substitutes || [];
            var teachingSpans = data.teachingSpans || [];
            renderModalHeadline(substitutes, teachingSpans, xeGoc, tenGvGoc, maGvGoc);
            applyXdtTeachingDateBounds(teachingSpans);
            $('#xdt_ap_dung_lich').prop('checked', false);

            $('#xdt_bien_so_xe').val(null).trigger('change');
            renderSubstituteSummary(substitutes);
            renderSubstituteList(substitutes);
        }

        $(document).on('change', '#xdt_tu_ngay', function () {
            syncXdtDenNgayMin();
        });

        $('#xdt_bien_so_xe').select2({
            theme: 'bootstrap4',
            placeholder: 'Chọn hoặc gõ biển số...',
            allowClear: true,
            width: '100%',
            dropdownParent: $xdtModal,
            tags: true,
            createTag: function (params) {
                var term = $.trim(params.term);
                if (term === '') {
                    return null;
                }
                return { id: term, text: term };
            },
            language: {
                noResults: function () { return 'Gõ biển số rồi Enter để thêm'; }
            }
        });

        $(document).on('click', '.btn-xe-thay', function () {
            var $btn = $(this);
            var substitutes = [];
            var teachingSpans = [];
            try {
                substitutes = JSON.parse($btn.attr('data-substitutes') || '[]');
            } catch (e) {
                substitutes = [];
            }
            try {
                teachingSpans = JSON.parse($btn.attr('data-teaching-spans') || '[]');
            } catch (e) {
                teachingSpans = [];
            }

            fillXeThayModal({
                maKhoaHoc: $btn.attr('data-ma-khoa-hoc'),
                maGiaoVienGoc: $btn.attr('data-ma-giao-vien-goc'),
                tenGiaoVienGoc: $btn.attr('data-ten-giao-vien-goc') || '',
                bienSoXeGoc: $btn.attr('data-bien-so-xe-goc'),
                substitutes: substitutes,
                teachingSpans: teachingSpans
            });
            $xdtModal.modal('show');
        });

        @if ($errors->any() && old('ma_khoa_hoc') && old('ma_giao_vien_goc') && old('bien_so_xe_goc'))
            @php
                $xdtOldTeachingSpans = \App\Support\DaoTao\DatGiaoVienKhoaTeachingSpan::spansForCourseGiaoVien(
                    (string) old('ma_khoa_hoc'),
                    (string) old('ma_giao_vien_goc')
                );
            @endphp
            fillXeThayModal({
                maKhoaHoc: @json(old('ma_khoa_hoc')),
                maGiaoVienGoc: @json(old('ma_giao_vien_goc')),
                tenGiaoVienGoc: '',
                bienSoXeGoc: @json(old('bien_so_xe_goc')),
                substitutes: [],
                teachingSpans: @json($xdtOldTeachingSpans)
            });
            $('#xdt_bien_so_xe').val(@json(old('bien_so_xe'))).trigger('change');
            $('#xdt_tu_ngay').val(@json(old('tu_ngay')));
            $('#xdt_den_ngay').val(@json(old('den_ngay')));
            $xdtModal.modal('show');
        @endif
    })();
</script>
@endpush
