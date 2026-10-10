@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Dò lịch trống giáo viên')

@section('content')
    @php
        $pairRows = $input['pairs'] ?? [['ma_kh' => '', 'ma_gv_sang' => '', 'ma_gv_chieu' => '']];
        if ($pairRows === []) {
            $pairRows = [['ma_kh' => '', 'ma_gv_sang' => '', 'ma_gv_chieu' => '']];
        }
        $boQuaLoai = $input['bo_qua_loai'] ?? [];
        $minFreePhut = $input['min_free_phut'] ?? '';
        $loaiFilters = \App\Support\PMGPLX\LichXeLoaiGhiChu::allowedDoTrongHideDayFilters();
    @endphp

    <div class="d-flex flex-wrap align-items-center mb-3">
        <h5 class="mb-2 mr-3">Dò lịch trống giáo viên</h5>
        <a href="{{ route('pmgplx.lich.gv.index') }}" class="btn btn-sm btn-outline-secondary mb-2 mr-1">← Danh sách lịch GV</a>
        <a href="{{ route('pmgplx.lich.gv.do-trung') }}" class="btn btn-sm btn-outline-secondary mb-2">Dò trùng lịch</a>
    </div>

    <div class="card card-panel mb-3">
        <div class="card-header">Tiêu chí</div>
        <div class="card-body">
            <p class="text-muted small mb-3">
                Lấy <strong>thời gian bận</strong> từ <strong>lịch xe tập</strong> (<a href="{{ route('pmgplx.lich.xe.index') }}">pmgplx/lich/xe-tap</a>) theo từng cặp: khóa + GV sáng + GV chiều.
                <strong>Thời gian rảnh</strong> tính cho <strong>cả cặp</strong> (hợp nhất bận của hai GV trong ngày), ví dụ sáng 7:30–11:30 và chiều 13:30–17:30 → rảnh: đầu ngày đến 7:30, 11:31–13:29, 17:31 đến giờ giới hạn.
                <strong>Bỏ qua loại</strong> và <strong>rảnh ≥ phút</strong> dùng chung: tick loại → ẩn ngày có buổi xe khớp (một trong hai GV);
                <strong>Ngày nghỉ (lịch trống)</strong> → chỉ từ <strong>NgayKG</strong> đến <strong>NgayBG</strong> khóa: ẩn ngày cặp không có buổi xe; trước khai giảng / sau bế giảng (nếu vẫn nằm trong khoảng quét) không áp dụng tiêu chí này; rảnh ≥ X → ẩn ngày không có khe rảnh đủ dài (0 = không lọc).
                Khung ngày {{ $input['gio_bat_dau'] ?? '06:00' }}–{{ $input['gio_ket_thuc'] ?? '22:00' }}; sau mỗi buổi bận cộng thêm 1 phút (vd. hết 10:00 → rảnh từ 10:01).
            </p>

            <form method="GET" action="{{ route('pmgplx.lich.gv.do-trong') }}" id="do-trong-form">
                <input type="hidden" name="run" value="1">

                <div class="d-flex flex-wrap align-items-center mb-2">
                    <label class="mb-0 font-weight-bold">Khóa học — Giáo viên</label>
                    <button type="button" class="btn btn-sm btn-outline-primary ml-2" id="pair-add">+ Thêm cặp</button>
                </div>
                <p class="small text-muted mb-2">Chọn <strong>GV sáng</strong> → tự gợi ý <strong>GV chiều</strong> trùng biển số xe với sáng trong khóa (có thể sửa tay).</p>

                <div id="pair-rows" class="mb-2">
                    @foreach ($pairRows as $pair)
                        <div class="pair-row form-row align-items-end mb-2">
                            <div class="form-group col-md-4 mb-2">
                                <label class="small mb-1">Khóa học <span class="text-danger">*</span></label>
                                <select name="pair_ma_kh[]" class="form-control form-control-sm pair-ma-kh" required>
                                    <option value="">— Chọn khóa —</option>
                                    @foreach ($khoaHocs as $kh)
                                        <option value="{{ $kh->MaKH }}" @selected(($pair['ma_kh'] ?? '') === $kh->MaKH)>
                                            {{ $kh->TenKH }} ({{ $kh->MaKH }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-3 mb-2">
                                <label class="small mb-1">Giáo viên sáng <span class="text-danger">*</span></label>
                                <select name="pair_ma_gv_sang[]" class="form-control form-control-sm pair-ma-gv-sang" required>
                                    <option value="">— Chọn GV —</option>
                                    @foreach ($giaoViens as $gv)
                                        <option value="{{ $gv->MaGV }}" @selected(($pair['ma_gv_sang'] ?? '') === $gv->MaGV)>
                                            {{ $gv->ho_ten }} ({{ $gv->MaGV }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-3 mb-2">
                                <label class="small mb-1">Giáo viên chiều <span class="text-danger">*</span></label>
                                <select name="pair_ma_gv_chieu[]" class="form-control form-control-sm pair-ma-gv-chieu" required>
                                    <option value="">— Chọn GV —</option>
                                    @foreach ($giaoViens as $gv)
                                        <option value="{{ $gv->MaGV }}" @selected(($pair['ma_gv_chieu'] ?? '') === $gv->MaGV)>
                                            {{ $gv->ho_ten }} ({{ $gv->MaGV }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-2 mb-2">
                                <label class="small mb-1 d-block">&nbsp;</label>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-block pair-remove" title="Xóa cặp">×</button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="form-row mb-3 border-top pt-3">
                    <div class="form-group col-md-6 mb-2">
                        <label class="small mb-1">Bỏ qua loại</label>
                        @include('PMGPLX.lich.partials.pair-loai-checkboxes', [
                            'selected' => $boQuaLoai,
                            'loaiFilters' => $loaiFilters,
                            'inputName' => 'bo_qua_loai',
                        ])
                    </div>
                    <div class="form-group col-md-6 mb-2">
                        <label for="min_free_phut" class="small mb-1">Rảnh ≥ (phút)</label>
                        <input type="number" name="min_free_phut" id="min_free_phut"
                               class="form-control form-control-sm do-trong-min-free-input"
                               min="0" max="960" step="1" placeholder="0 = không lọc"
                               value="{{ $minFreePhut !== '' && $minFreePhut !== null ? (int) $minFreePhut : '' }}">
                        <small class="form-text text-muted mb-0">Chỉ hiện ngày có ít nhất một khung rảnh ≥ số phút này.</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="khoang_ngay_preset">Chọn nhanh</label>
                        <select id="khoang_ngay_preset" class="form-control form-control-sm">
                            <option value="">— Tự nhập từ / đến —</option>
                            <option value="past93">93 ngày vừa qua → hôm nay</option>
                            <option value="next93">Hôm nay → 93 ngày tới</option>
                            <option value="center93">93 ngày quanh hôm nay (giữa)</option>
                            <option value="this_month">Tháng này</option>
                            <option value="last_month">Tháng trước</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="tu_ngay">Từ ngày</label>
                        <input type="date" name="tu_ngay" id="tu_ngay" class="form-control form-control-sm"
                               value="{{ $input['tu_ngay'] }}">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="den_ngay">Đến ngày</label>
                        <input type="date" name="den_ngay" id="den_ngay" class="form-control form-control-sm"
                               value="{{ $input['den_ngay'] }}">
                    </div>
                    <div class="form-group col-md-3">
                        <label class="d-block">&nbsp;</label>
                        <small class="form-text text-muted mt-0">
                            Để trống → <strong>NgayKG</strong> sớm nhất và <strong>NgayBG</strong> muộn nhất của các khóa trong các cặp
                            (khóa không có NgayBG → đến hôm nay). Tối đa {{ \App\Support\PMGPLX\GiaoVienLichTrongScanner::MAX_RANGE_DAYS }} ngày/lần.
                        </small>
                    </div>
                </div>

                <div class="form-row align-items-end">
                    <div class="form-group col-md-2">
                        <label for="gio_bat_dau">Bắt đầu khung ngày</label>
                        <input type="time" name="gio_bat_dau" id="gio_bat_dau" class="form-control form-control-sm"
                               value="{{ $input['gio_bat_dau'] ?? '06:00' }}">
                    </div>
                    <div class="form-group col-md-2">
                        <label for="gio_ket_thuc">Giới hạn cuối ngày</label>
                        <input type="time" name="gio_ket_thuc" id="gio_ket_thuc" class="form-control form-control-sm"
                               value="{{ $input['gio_ket_thuc'] ?? '22:00' }}">
                    </div>
                    <div class="form-group col-md-2">
                        <label for="trang_thai">Trạng thái lịch</label>
                        <select name="trang_thai" id="trang_thai" class="form-control form-control-sm">
                            <option value="1" @selected(($input['trang_thai'] ?? '1') === '1')>Chỉ hiệu lực</option>
                            <option value="0" @selected(($input['trang_thai'] ?? '') === '0')>Không hiệu lực</option>
                            <option value="" @selected(($input['trang_thai'] ?? '') === '')>Tất cả</option>
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <button type="submit" class="btn btn-primary btn-sm btn-block">Tính lịch trống</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if ($submitted && $result !== null)
        <div class="card card-panel">
            <div class="card-header">Kết quả</div>
            <div class="card-body">
                @if (! $result['ok'])
                    <div class="alert alert-warning mb-0">
                        <ul class="mb-0 pl-3">
                            @foreach ($result['errors'] as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    @php $stats = $result['stats']; @endphp
                    <p class="small text-muted mb-2">
                        {{ number_format($stats['pair_count'] ?? $stats['gv_count'] ?? 0) }} cặp khóa–GV ×
                        {{ number_format($stats['day_count'] ?? 0) }} ngày —
                        {{ number_format($stats['rows_shown'] ?? 0) }} dòng
                        @if (! empty($stats['rows_truncated']))
                            (cắt ở {{ number_format(\App\Support\PMGPLX\GiaoVienLichTrongScanner::MAX_ROWS) }} dòng)
                        @endif
                        · {{ $stats['bo_qua_loai_label'] ?? \App\Support\PMGPLX\LichXeLoaiGhiChu::boQuaLoaiFiltersLabel($input['bo_qua_loai'] ?? []) }}
                        @if (! empty($stats['min_free_phut']))
                            · rảnh ≥ {{ number_format($stats['min_free_phut']) }} phút
                        @endif
                    </p>
                    @if (! empty($stats['slots_truncated']))
                        <div class="alert alert-warning py-2">Quá nhiều buổi lịch — thu hẹp khóa, GV hoặc khoảng ngày.</div>
                    @endif
                    @if (! empty($result['notice']))
                        <div class="alert alert-info py-2 mb-2">{{ $result['notice'] }}</div>
                    @endif
                    @if (! empty($result['resolved_range']))
                        @php $rr = $result['resolved_range']; @endphp
                        <p class="small mb-2">
                            Khoảng quét:
                            <strong>{{ \Carbon\Carbon::parse($rr['tu_ngay'])->format('d/m/Y') }}</strong>
                            –
                            <strong>{{ \Carbon\Carbon::parse($rr['den_ngay'])->format('d/m/Y') }}</strong>
                        </p>
                    @endif

                    @if (($result['groups'] ?? []) === [])
                        <div class="alert alert-success mb-0">Không có dòng để hiển thị.</div>
                    @else
                        @foreach ($result['groups'] as $gvGroup)
                            <div class="mb-4 pb-2 border-bottom">
                                <h6 class="mb-2">
                                    <span class="text-muted font-weight-normal">Cặp #{{ $gvGroup['pair_line'] ?? '?' }}:</span>
                                    {{ $gvGroup['ten_kh'] ?? '' }}
                                    <span class="text-muted font-weight-normal small">({{ $gvGroup['ma_kh'] ?? '' }})</span>
                                    · Sáng: {{ $gvGroup['ten_gv_sang'] ?? '' }}
                                    <span class="text-muted font-weight-normal small">({{ $gvGroup['ma_gv_sang_norm'] ?? '' }})</span>
                                    · Chiều: {{ $gvGroup['ten_gv_chieu'] ?? '' }}
                                    <span class="text-muted font-weight-normal small">({{ $gvGroup['ma_gv_chieu_norm'] ?? '' }})</span>
                                    <span class="badge badge-light ml-1">{{ count($gvGroup['days']) }} ngày</span>
                                </h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered table-striped mb-0">
                                        <thead class="thead-light">
                                            <tr>
                                                <th style="width: 8rem">Ngày</th>
                                                <th>{{ $gvGroup['ten_gv_sang'] ?? 'GV sáng' }}</th>
                                                <th>Nội dung A</th>
                                                <th>{{ $gvGroup['ten_gv_chieu'] ?? 'GV chiều' }}</th>
                                                <th>Nội dung B</th>
                                                <th>Thời gian rảnh</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($gvGroup['days'] as $dayRow)
                                                <tr>
                                                    <td class="text-nowrap">{{ $dayRow['ngay_label'] }}</td>
                                                    <td class="small">
                                                        @forelse ($dayRow['busy_a_segments'] ?? [] as $seg)
                                                            <div @class(['mb-1' => ! $loop->last])>{{ $seg['time'] }}</div>
                                                        @empty
                                                            {{ $dayRow['busy_a_label'] ?? '—' }}
                                                        @endforelse
                                                    </td>
                                                    <td class="small text-left">
                                                        @forelse ($dayRow['busy_a_segments'] ?? [] as $seg)
                                                            <div @class(['mb-1' => ! $loop->last])>{{ $seg['noi_dung'] }}</div>
                                                        @empty
                                                            {{ $dayRow['noi_dung_a_label'] ?? '—' }}
                                                        @endforelse
                                                    </td>
                                                    <td class="small">
                                                        @forelse ($dayRow['busy_b_segments'] ?? [] as $seg)
                                                            <div @class(['mb-1' => ! $loop->last])>{{ $seg['time'] }}</div>
                                                        @empty
                                                            {{ $dayRow['busy_b_label'] ?? '—' }}
                                                        @endforelse
                                                    </td>
                                                    <td class="small text-left">
                                                        @forelse ($dayRow['busy_b_segments'] ?? [] as $seg)
                                                            <div @class(['mb-1' => ! $loop->last])>{{ $seg['noi_dung'] }}</div>
                                                        @empty
                                                            {{ $dayRow['noi_dung_b_label'] ?? '—' }}
                                                        @endforelse
                                                    </td>
                                                    <td class="small text-success">{{ $dayRow['free_label'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    @endif
                @endif
            </div>
        </div>
    @endif

    <template id="pair-row-template">
        <div class="pair-row form-row align-items-end mb-2">
            <div class="form-group col-md-4 mb-2">
                <label class="small mb-1">Khóa học <span class="text-danger">*</span></label>
                <select name="pair_ma_kh[]" class="form-control form-control-sm pair-ma-kh" required>
                    <option value="">— Chọn khóa —</option>
                    @foreach ($khoaHocs as $kh)
                        <option value="{{ $kh->MaKH }}">{{ $kh->TenKH }} ({{ $kh->MaKH }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-3 mb-2">
                <label class="small mb-1">Giáo viên sáng <span class="text-danger">*</span></label>
                <select name="pair_ma_gv_sang[]" class="form-control form-control-sm pair-ma-gv-sang" required>
                    <option value="">— Chọn GV —</option>
                    @foreach ($giaoViens as $gv)
                        <option value="{{ $gv->MaGV }}">{{ $gv->ho_ten }} ({{ $gv->MaGV }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-3 mb-2">
                <label class="small mb-1">Giáo viên chiều <span class="text-danger">*</span></label>
                <select name="pair_ma_gv_chieu[]" class="form-control form-control-sm pair-ma-gv-chieu" required>
                    <option value="">— Chọn GV —</option>
                    @foreach ($giaoViens as $gv)
                        <option value="{{ $gv->MaGV }}">{{ $gv->ho_ten }} ({{ $gv->MaGV }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-2 mb-2">
                <label class="small mb-1 d-block">&nbsp;</label>
                <button type="button" class="btn btn-sm btn-outline-danger btn-block pair-remove" title="Xóa cặp">×</button>
            </div>
        </div>
    </template>
@endsection

@push('styles')
<style>
    .do-trong-min-free-input {
        max-width: 8rem;
    }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        function initPairSelect2($scope) {
            $scope.find('.pair-ma-kh, .pair-ma-gv-sang, .pair-ma-gv-chieu').each(function () {
                var $el = $(this);
                if ($el.hasClass('select2-hidden-accessible')) {
                    $el.select2('destroy');
                }
                $el.select2({
                    theme: 'bootstrap4',
                    allowClear: true,
                    width: '100%',
                    placeholder: 'Chọn…',
                });
            });
        }

        function updateRemoveButtons() {
            var rows = document.querySelectorAll('#pair-rows .pair-row');
            rows.forEach(function (row) {
                var btn = row.querySelector('.pair-remove');
                if (btn) {
                    btn.disabled = rows.length <= 1;
                }
            });
        }

        var gvCapChieuByKhoaSang = @json($gvCapChieuByKhoaSang ?? []);

        function normalizeMaKhKey(maKh) {
            return String(maKh || '').trim().toUpperCase();
        }

        function syncChieuFromSang($row) {
            var maKh = normalizeMaKhKey($row.find('.pair-ma-kh').val());
            var sangVal = $row.find('.pair-ma-gv-sang').val();
            var $chieu = $row.find('.pair-ma-gv-chieu');
            if (! maKh || ! sangVal) {
                return;
            }
            var bySang = (gvCapChieuByKhoaSang[maKh] || {})[sangVal];
            if (bySang === undefined || bySang === null || bySang === '') {
                return;
            }
            $chieu.val(bySang).trigger('change');
        }

        initPairSelect2($('#pair-rows'));
        updateRemoveButtons();

        $('#pair-rows').on('change select2:select', '.pair-ma-gv-sang, .pair-ma-kh', function () {
            syncChieuFromSang($(this).closest('.pair-row'));
        });

        document.getElementById('pair-add').addEventListener('click', function () {
            var tpl = document.getElementById('pair-row-template');
            var node = tpl.content.cloneNode(true);
            document.getElementById('pair-rows').appendChild(node);
            var $last = $('#pair-rows .pair-row').last();
            initPairSelect2($last);
            updateRemoveButtons();
        });

        document.getElementById('pair-rows').addEventListener('click', function (e) {
            if (! e.target.classList.contains('pair-remove') || e.target.disabled) {
                return;
            }
            var row = e.target.closest('.pair-row');
            if (! row) {
                return;
            }
            $(row).find('.pair-ma-kh, .pair-ma-gv-sang, .pair-ma-gv-chieu').select2('destroy');
            row.remove();
            updateRemoveButtons();
        });

        var MAX_SPAN_DAYS = @json(\App\Support\PMGPLX\GiaoVienLichTrongScanner::MAX_RANGE_DAYS);

        function pad2(n) { return n < 10 ? '0' + n : String(n); }
        function formatDateLocal(d) {
            return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate());
        }
        function startOfToday() {
            var t = new Date();
            return new Date(t.getFullYear(), t.getMonth(), t.getDate());
        }
        function addDays(d, days) {
            var x = new Date(d.getTime());
            x.setDate(x.getDate() + days);
            return x;
        }
        function applyDatePreset(key) {
            if (!key) return;
            var today = startOfToday();
            var tu = today, den = today;
            var spanInclusive = MAX_SPAN_DAYS;
            if (key === 'past93') {
                tu = addDays(today, -(spanInclusive - 1));
                den = today;
            } else if (key === 'next93') {
                tu = today;
                den = addDays(today, spanInclusive - 1);
            } else if (key === 'center93') {
                var before = Math.floor((spanInclusive - 1) / 2);
                var after = spanInclusive - 1 - before;
                tu = addDays(today, -before);
                den = addDays(today, after);
            } else if (key === 'this_month') {
                tu = new Date(today.getFullYear(), today.getMonth(), 1);
                den = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            } else if (key === 'last_month') {
                tu = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                den = new Date(today.getFullYear(), today.getMonth(), 0);
            }
            document.getElementById('tu_ngay').value = formatDateLocal(tu);
            document.getElementById('den_ngay').value = formatDateLocal(den);
        }
        var presetEl = document.getElementById('khoang_ngay_preset');
        if (presetEl) {
            presetEl.addEventListener('change', function () {
                applyDatePreset(presetEl.value);
                presetEl.value = '';
            });
        }
    })();
</script>
@endpush
