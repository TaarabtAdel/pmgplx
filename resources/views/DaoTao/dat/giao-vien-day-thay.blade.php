@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Giáo viên dạy thay')

@section('content')
    <div class="card card-panel mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <span>Giáo viên dạy thay</span>
            <div class="mt-1 mt-md-0">
                <a href="{{ route('daotao.pdt.dat.xe-day-thay', array_filter(['ma_khoa_hoc' => $selectedKhoa])) }}" class="btn btn-sm btn-outline-warning mr-1">
                    Xe dạy thay
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
                Khai báo GV dạy thay theo <strong>khóa học + GV chính</strong>, áp dụng cho mọi học viên cùng khóa và cùng GV.
                Phiên DAT trong khoảng ngày sẽ so mã GV phiên với GV dạy thay (cảnh báo
                <strong>Giáo viên khác phân công HV</strong>).
            </p>

            <form method="GET" action="{{ route('daotao.pdt.dat.giao-vien-day-thay') }}" class="form-inline mb-3" id="gvtKhoaForm">
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
                <span class="small text-muted">Chọn khóa để hiện danh sách GV chính.</span>
            </form>

            @if ($selectedKhoa !== '')
                @if (! empty($coKhaiBaoThay))
                    <div class="mb-3">
                        <a href="{{ route('daotao.pdt.dat.giao-vien-day-thay.preview-apply-lich', ['ma_khoa_hoc' => $selectedKhoa]) }}"
                           class="btn btn-sm btn-navy">
                            Áp dụng vào lịch PMGPLX (xem trước)
                        </a>
                        <span class="small text-muted ml-2">
                            Cập nhật <code>KhoaHoc_GiaoVien</code> và <code>KhoaHoc_XeTap</code> theo khai báo dạy thay.
                            Xóa khai báo sẽ hoàn GV gốc trên lịch (nếu đã áp dụng).
                        </span>
                    </div>
                @endif
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-striped table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>GV chính</th>
                                <th width="90" class="text-center">Số HV</th>
                                <th>Giáo viên dạy thay (từ – đến)</th>
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
                                    <td class="small">
                                        @if ($gvRow['so_khai_bao'] === 0)
                                            <span class="text-muted">—</span>
                                        @else
                                            @foreach ($gvRow['substitutes'] as $sub)
                                                @php
                                                    $gvSub = $giaoVienNames[$sub['ma_giao_vien']] ?? null;
                                                    $tenSub = $gvSub ? trim(($gvSub->HoTenDem ?? '').' '.($gvSub->TenGV ?? '')) : '';
                                                    $tuSub = ! empty($sub['tu_ngay']) ? \Carbon\Carbon::parse($sub['tu_ngay'])->format('d/m/Y') : '—';
                                                    $denSub = ! empty($sub['den_ngay'])
                                                        ? \Carbon\Carbon::parse($sub['den_ngay'])->format('d/m/Y')
                                                        : 'khi có khai báo mới';
                                                @endphp
                                                <div class="mb-1">
                                                    @if ($tenSub !== '')
                                                        <strong>{{ $tenSub }}</strong>
                                                        (<code>{{ $sub['ma_giao_vien'] }}</code>)
                                                    @else
                                                        <code>{{ $sub['ma_giao_vien'] }}</code>
                                                    @endif
                                                    · từ <span class="text-nowrap">{{ $tuSub }}</span>
                                                    đến <span class="text-nowrap">{{ $denSub }}</span>
                                                    · <span class="text-nowrap">{{ \App\Support\DaoTao\DatPhanCongGiaoVienThayResolver::nhanKhungGio($sub['tu_gio'] ?? null, $sub['den_gio'] ?? null) }}</span>
                                                </div>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td class="text-nowrap">
                                        <button type="button"
                                                class="btn btn-sm btn-warning py-0 btn-giao-vien-thay"
                                                data-ma-khoa-hoc="{{ $gvRow['ma_khoa_hoc'] }}"
                                                data-ma-giao-vien-goc="{{ $gvRow['ma_giao_vien_goc'] }}"
                                                data-ten-giao-vien-goc="{{ $tenGvGoc }}"
                                                data-substitutes='@json($gvRow['substitutes'])'
                                                data-teaching-spans='@json($gvRow['teaching_spans'] ?? [])'>
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
                <div class="modal-header py-2 align-items-start">
                    <div class="pr-4 flex-grow-1">
                        <h5 class="modal-title mb-1" id="modalGiaoVienThayLabel">Giáo viên dạy thay</h5>
                        <div id="gvt_modal_headline" class="small mb-0"></div>
                    </div>
                    <button type="button" class="close mt-0" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="small mb-2">
                        <strong>Khóa:</strong> <code id="gvt_khoa"></code>
                        ·
                        <strong>Người được dạy thay:</strong> <span id="gvt_gv_chinh"></span>
                    </div>

                    <div id="gvt_sub_summary" class="alert alert-info small py-2 mb-3 d-none"></div>

                    <p class="small font-weight-bold mb-2">Danh sách giáo viên đang dạy thay</p>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Giáo viên dạy thay</th>
                                    <th>Khoảng dạy thay</th>
                                    <th width="130"></th>
                                </tr>
                            </thead>
                            <tbody id="gvt_list_body">
                                <tr>
                                    <td colspan="3" class="text-muted text-center py-3">Chưa có giáo viên dạy thay.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <form method="POST" action="{{ route('daotao.pdt.dat.giao-vien-day-thay.store') }}" id="formGiaoVienThay"
                          onsubmit="return confirmGvtStoreLich();">
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
                                    <small class="text-muted d-block">Để trống = mở đến khi có bản ghi mới (vẫn trong khoảng đang dạy khóa)</small>
                                    @error('den_ngay')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-3 mb-2">
                                    <label for="gvt_tu_gio" class="small mb-1">Giờ bắt đầu</label>
                                    <input type="time" name="tu_gio" id="gvt_tu_gio" step="60"
                                           class="form-control form-control-sm @error('tu_gio') is-invalid @enderror"
                                           value="{{ old('tu_gio') }}">
                                    @error('tu_gio')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group col-md-3 mb-2">
                                    <label for="gvt_den_gio" class="small mb-1">Giờ kết thúc</label>
                                    <input type="time" name="den_gio" id="gvt_den_gio" step="60"
                                           class="form-control form-control-sm @error('den_gio') is-invalid @enderror"
                                           value="{{ old('den_gio') }}">
                                    @error('den_gio')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-2 d-flex align-items-end">
                                    <small class="text-muted">
                                        Áp dụng cho mỗi ngày trong khoảng ngày trên: buổi nào có giờ bắt đầu nằm trong khung này thì đổi sang GV dạy thay.
                                        <strong>Để trống cả hai = thay cả ngày.</strong>
                                    </small>
                                </div>
                            </div>
                            <p id="gvt_date_bounds_hint" class="small text-muted mb-2"></p>
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input" id="gvt_ap_dung_lich" name="ap_dung_lich" value="1">
                                <label class="custom-control-label small" for="gvt_ap_dung_lich">
                                    Áp dụng khai báo này vào lịch PMGPLX sau khi lưu (các buổi trùng ngày, GV vẫn là GV gốc)
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
        var $gvtModal = $('#modalGiaoVienThay');
        var selectedKhoa = @json($selectedKhoa);
        var gvtDestroyUrlBase = @json(route('daotao.pdt.dat.giao-vien-day-thay.destroy', ['id' => 0]));
        var gvtApplyUrlBase = @json(route('daotao.pdt.dat.giao-vien-day-thay.apply-khai-bao-lich', ['id' => 0]));
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

        function appendMaKhoaQuery(url, maKhoa) {
            if (!maKhoa) {
                return url;
            }

            return url + (url.indexOf('?') >= 0 ? '&' : '?') + 'ma_khoa_hoc=' + encodeURIComponent(maKhoa);
        }

        function confirmGvtStoreLich() {
            if (!$('#gvt_ap_dung_lich').is(':checked')) {
                return true;
            }

            return confirm('Lưu khai báo và áp dụng GV dạy thay vào lịch PMGPLX (GPLX_BAN_MOI)?');
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

        function formatKhungGio(tuGio, denGio) {
            if (!tuGio && !denGio) {
                return 'cả ngày';
            }

            return (tuGio || '00:00') + ' – ' + (denGio || '24:00');
        }

        function formatKhoangDayThay(tuNgay, denNgay, tuGio, denGio) {
            return 'Từ <strong>' + formatIsoDate(tuNgay) + '</strong> đến <strong>' + escapeHtml(formatDenNgayLabel(denNgay)) + '</strong>' +
                '<br><span class="text-muted">Khung giờ: <strong>' + escapeHtml(formatKhungGio(tuGio, denGio)) + '</strong></span>';
        }

        function tenGiaoVienThay(maGv) {
            var ten = (gvtTenByMa[maGv] || '').trim();
            if (ten !== '') {
                return ten;
            }

            return maGv || '—';
        }

        function formatDenNgayWithFallback(denNgay, emptyLabel) {
            if (denNgay) {
                return formatIsoDate(denNgay);
            }

            return emptyLabel;
        }

        function formatDuocDayThayTuDen(tuNgay, denNgay) {
            return 'từ <strong>' + formatIsoDate(tuNgay) + '</strong> đến <strong>' +
                escapeHtml(formatDenNgayWithFallback(denNgay, 'khi có khai báo mới')) + '</strong>';
        }

        function formatDangDayKhoaTuDen(tuNgay, denNgay) {
            return 'từ <strong>' + formatIsoDate(tuNgay) + '</strong> đến <strong>' +
                escapeHtml(formatDenNgayWithFallback(denNgay, '—')) + '</strong>';
        }

        function renderTeachingSpanLines(tenDuocThay, teachingSpans) {
            if (!teachingSpans || teachingSpans.length === 0) {
                return '<div class="mb-1 text-muted">' +
                    '<strong>' + escapeHtml(tenDuocThay) + '</strong>: chưa xác định khoảng đang dạy khóa (thiếu phân công đào tạo / lịch PMGPLX).</div>';
            }

            return teachingSpans.map(function (span) {
                return '<div class="mb-1"><strong>' + escapeHtml(tenDuocThay) + '</strong> đang dạy khóa này ' +
                    formatDangDayKhoaTuDen(span.tu_ngay, span.den_ngay) + '</div>';
            }).join('');
        }

        function renderSubstituteHeadlineLines(tenDuocThay, substitutes) {
            if (!substitutes || substitutes.length === 0) {
                return '<div class="mb-1 text-muted">' +
                    '<strong>' + escapeHtml(tenDuocThay) + '</strong> chưa có khoảng thời gian được dạy thay.</div>';
            }

            return substitutes.map(function (row) {
                var tenThay = tenGiaoVienThay(row.ma_giao_vien);
                return '<div class="mb-1"><strong>' + escapeHtml(tenDuocThay) + '</strong> được dạy thay ' +
                    formatDuocDayThayTuDen(row.tu_ngay, row.den_ngay) +
                    ' <span class="text-muted">(' + escapeHtml(formatKhungGio(row.tu_gio, row.den_gio)) + ')</span>' +
                    ' <span class="text-muted">· GV dạy thay: <strong>' + escapeHtml(tenThay) + '</strong></span></div>';
            }).join('');
        }

        function renderModalHeadline(substitutes, teachingSpans, tenGvGoc, maGvGoc) {
            var $title = $('#modalGiaoVienThayLabel');
            var $headline = $('#gvt_modal_headline');
            var tenDuocThay = (tenGvGoc || '').trim() || maGvGoc || '—';

            $title.text('Giáo viên dạy thay — ' + tenDuocThay);

            var html = renderTeachingSpanLines(tenDuocThay, teachingSpans) +
                renderSubstituteHeadlineLines(tenDuocThay, substitutes);

            if (!substitutes || substitutes.length === 0) {
                html += '<div class="small text-muted mt-1">Thêm GV dạy thay và khoảng ngày ở form bên dưới.</div>';
            }

            $headline.html(html);
        }

        function renderSubstituteSummary(substitutes) {
            var $box = $('#gvt_sub_summary');
            $box.addClass('d-none').empty();
        }

        function renderSubstituteList(substitutes) {
            var $body = $('#gvt_list_body');
            $body.empty();

            if (!substitutes || substitutes.length === 0) {
                $body.append('<tr><td colspan="3" class="text-muted text-center py-3">Chưa có giáo viên dạy thay.</td></tr>');
                return;
            }

            substitutes.forEach(function (row) {
                var deleteUrl = appendMaKhoaQuery(gvtDestroyUrlBase.replace(/0$/, String(row.id)), selectedKhoa);
                var applyUrl = appendMaKhoaQuery(gvtApplyUrlBase.replace(/0$/, String(row.id)), selectedKhoa);
                $body.append(
                    '<tr>' +
                        '<td>' + renderGvThayCell(row.ma_giao_vien) + '</td>' +
                        '<td class="small">' + formatKhoangDayThay(row.tu_ngay, row.den_ngay, row.tu_gio, row.den_gio) + '</td>' +
                        '<td class="text-nowrap">' +
                            '<form method="POST" action="' + applyUrl + '" class="d-inline mr-1" onsubmit="return confirm(\'Áp dụng khai báo này vào lịch PMGPLX?\');">' +
                                '<input type="hidden" name="_token" value="' + csrfToken + '">' +
                                '<button type="submit" class="btn btn-sm btn-navy py-0">Áp lịch</button>' +
                            '</form>' +
                            '<form method="POST" action="' + deleteUrl + '" class="d-inline" onsubmit="return confirm(\'Xóa khai báo và hoàn GV gốc trên lịch PMGPLX (các buổi đang là GV dạy thay)?\');">' +
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

        function syncGvtDenNgayMin() {
            var $tu = $('#gvt_tu_ngay');
            var $den = $('#gvt_den_ngay');
            var floor = $tu.attr('min') || '';
            var tu = $tu.val() || '';
            if (tu && (!floor || tu > floor)) {
                floor = tu;
            }
            if (floor) {
                $den.attr('min', floor);
            }
        }

        function applyGvtTeachingDateBounds(teachingSpans) {
            var env = teachingEnvelope(teachingSpans);
            var $tu = $('#gvt_tu_ngay');
            var $den = $('#gvt_den_ngay');
            var $hint = $('#gvt_date_bounds_hint');
            var $submit = $('#formGiaoVienThay button[type="submit"]');

            if (!env) {
                $tu.removeAttr('min').removeAttr('max').prop('disabled', true);
                $den.removeAttr('min').removeAttr('max').prop('disabled', true);
                $hint.text('Chưa xác định khoảng đang dạy khóa — không thể thêm dạy thay.');
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
            syncGvtDenNgayMin();

            var label = formatIsoDate(env.min) + ' – ' + (env.max ? formatIsoDate(env.max) : '…');
            $hint.text('Từ ngày / đến ngày phải nằm trong khoảng đang dạy khóa: ' + label + '.');
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

            var substitutes = data.substitutes || [];
            var teachingSpans = data.teachingSpans || [];
            renderModalHeadline(substitutes, teachingSpans, tenGvGoc, maGvGoc);
            applyGvtTeachingDateBounds(teachingSpans);
            $('#gvt_ap_dung_lich').prop('checked', false);
            $('#gvt_tu_gio').val('');
            $('#gvt_den_gio').val('');
            $('#gvt_ma_giao_vien').val(null).trigger('change');
            renderSubstituteSummary(substitutes);
            renderSubstituteList(substitutes);
        }

        $(document).on('change', '#gvt_tu_ngay', function () {
            syncGvtDenNgayMin();
        });

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

            fillGiaoVienThayModal({
                maKhoaHoc: $btn.attr('data-ma-khoa-hoc'),
                maGiaoVienGoc: $btn.attr('data-ma-giao-vien-goc'),
                tenGiaoVienGoc: $btn.attr('data-ten-giao-vien-goc') || '',
                substitutes: substitutes,
                teachingSpans: teachingSpans
            });
            $gvtModal.modal('show');
        });

        @if ($errors->any() && old('ma_khoa_hoc') && old('ma_giao_vien_goc'))
            @php
                $gvtOldTeachingSpans = \App\Support\DaoTao\DatGiaoVienKhoaTeachingSpan::spansForCourseGiaoVien(
                    (string) old('ma_khoa_hoc'),
                    (string) old('ma_giao_vien_goc')
                );
            @endphp
            fillGiaoVienThayModal({
                maKhoaHoc: @json(old('ma_khoa_hoc')),
                maGiaoVienGoc: @json(old('ma_giao_vien_goc')),
                tenGiaoVienGoc: '',
                substitutes: [],
                teachingSpans: @json($gvtOldTeachingSpans)
            });
            $('#gvt_ma_giao_vien').val(@json(old('ma_giao_vien'))).trigger('change');
            $('#gvt_tu_ngay').val(@json(old('tu_ngay')));
            $('#gvt_den_ngay').val(@json(old('den_ngay')));
            $('#gvt_tu_gio').val(@json(old('tu_gio')));
            $('#gvt_den_gio').val(@json(old('den_gio')));
            $gvtModal.modal('show');
        @endif
    })();
</script>
@endpush
