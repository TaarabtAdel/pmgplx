@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Dò trùng lịch giáo viên')

@section('content')
    <div class="d-flex flex-wrap align-items-center mb-3">
        <h5 class="mb-2 mr-3">Dò trùng lịch giáo viên</h5>
        <a href="{{ route('pmgplx.lich.gv.index') }}" class="btn btn-sm btn-outline-secondary mb-2 mr-1">← Danh sách lịch GV</a>
        <a href="{{ route('pmgplx.lich.gv.do-trong') }}" class="btn btn-sm btn-outline-success mb-2">Dò lịch trống</a>
    </div>

    <div class="card card-panel mb-3">
        <div class="card-header">Tiêu chí kiểm tra</div>
        <div class="card-body">
            <p class="text-muted small mb-3">
                Quy tắc: <strong>cùng giáo viên</strong>, <strong>trùng khung giờ</strong> (TG bắt đầu – kết thúc),
                <strong>khác mã khóa học</strong> → xung đột (không thể dạy hai khóa cùng lúc).
                Có thể gộp thêm lịch xe tập (GV trên <code>KhoaHoc_XeTap</code>).
            </p>

            <form method="GET" action="{{ route('pmgplx.lich.gv.do-trung') }}" id="form_do_trung">
                <input type="hidden" name="run" value="1">

                <div class="form-group">
                    <label class="d-block mb-2">Chế độ</label>
                    @php
                        $modes = [
                            \App\Support\PMGPLX\GiaoVienLichTrungScanner::MODE_PROBE => 'Thử một buổi — kiểm tra trước khi thêm lịch / dạy thay',
                            \App\Support\PMGPLX\GiaoVienLichTrungScanner::MODE_BY_GV => 'Quét theo giáo viên — tìm mọi cặp trùng trong khoảng ngày',
                            \App\Support\PMGPLX\GiaoVienLichTrungScanner::MODE_BY_RANGE => 'Quét theo khoảng ngày — tất cả GV (có thể lọc khóa)',
                        ];
                    @endphp
                    @foreach ($modes as $value => $label)
                        <div class="custom-control custom-radio">
                            <input type="radio" id="mode_{{ $value }}" name="mode" value="{{ $value }}"
                                   class="custom-control-input mode-radio"
                                   @checked($input['mode'] === $value)>
                            <label class="custom-control-label" for="mode_{{ $value }}">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4" data-field="ma_gv">
                        <label for="ma_gv">Giáo viên</label>
                        <select name="ma_gv" id="ma_gv" class="form-control form-control-sm">
                            <option value="">—Chọn—</option>
                            @foreach ($giaoViens as $gv)
                                <option value="{{ $gv->MaGV }}" @selected($input['ma_gv'] === $gv->MaGV)>
                                    {{ $gv->ho_ten }} ({{ $gv->MaGV }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4" data-field="ma_kh">
                        <label for="ma_kh">Khóa học (lọc / buổi thử)</label>
                        <select name="ma_kh" id="ma_kh" class="form-control form-control-sm">
                            <option value="">—Không lọc—</option>
                            @foreach ($khoaHocs as $kh)
                                <option value="{{ $kh->MaKH }}" @selected($input['ma_kh'] === $kh->MaKH)>
                                    {{ $kh->TenKH }} ({{ $kh->MaKH }})
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted mode-hint mode-hint-probe">Bắt buộc: khóa của buổi bạn định xếp.</small>
                        <small class="form-text text-muted mode-hint mode-hint-range d-none">Tùy chọn: chỉ báo xung đột có liên quan khóa này.</small>
                    </div>
                    <div class="form-group col-md-4" data-field="loai_gv">
                        <label for="loai_gv">Loại lịch GV</label>
                        <select name="loai_gv" id="loai_gv" class="form-control form-control-sm">
                            <option value="TH" @selected($input['loai_gv'] === 'TH')>Thực hành (TH)</option>
                            <option value="LT" @selected($input['loai_gv'] === 'LT')>Lý thuyết (LT)</option>
                            <option value="all" @selected($input['loai_gv'] === 'all')>TH + LT</option>
                        </select>
                    </div>
                </div>

                <div class="form-row panel-range">
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
                            Tối đa {{ \App\Support\PMGPLX\GiaoVienLichTrungScanner::MAX_RANGE_DAYS }} ngày mỗi lần quét.
                        </small>
                    </div>
                </div>

                <div class="form-row panel-probe d-none">
                    <div class="form-group col-md-4">
                        <label>Bắt đầu buổi thử</label>
                        <input type="datetime-local" name="probe_ngay_bd" class="form-control form-control-sm"
                               value="{{ $input['probe_ngay_bd'] }}">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Kết thúc buổi thử</label>
                        <input type="datetime-local" name="probe_ngay_kt" class="form-control form-control-sm"
                               value="{{ $input['probe_ngay_kt'] }}">
                    </div>
                </div>

                <div class="form-row align-items-end">
                    <div class="form-group col-md-3">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="include_xe" name="include_xe" value="1"
                                   @checked($input['include_xe'])>
                            <label class="custom-control-label" for="include_xe">Gồm lịch xe tập (GV trên xe)</label>
                        </div>
                    </div>
                    <div class="form-group col-md-3 panel-range">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="only_cross_khoa" name="only_cross_khoa" value="1"
                                   @checked($input['only_cross_khoa'])>
                            <label class="custom-control-label" for="only_cross_khoa">Chỉ khác khóa (khuyến nghị)</label>
                        </div>
                        <small class="form-text text-muted">Bỏ tick để thấy cả trùng giờ trên cùng một khóa.</small>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="trang_thai">Trạng thái lịch</label>
                        <select name="trang_thai" id="trang_thai" class="form-control form-control-sm">
                            <option value="1" @selected($input['trang_thai'] === '1')>Chỉ hiệu lực</option>
                            <option value="0" @selected($input['trang_thai'] === '0')>Không hiệu lực</option>
                            <option value="" @selected($input['trang_thai'] === '')>Tất cả</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <button type="submit" class="btn btn-primary btn-sm btn-block">Chạy kiểm tra</button>
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
                @elseif ($input['mode'] === \App\Support\PMGPLX\GiaoVienLichTrungScanner::MODE_PROBE)
                    @if (count($result['probe_hits']) === 0)
                        <div class="alert alert-success mb-0">
                            Không thấy lịch khóa khác trùng khung giờ với buổi thử.
                        </div>
                    @else
                        <div class="alert alert-danger">
                            Có <strong>{{ count($result['probe_hits']) }}</strong> buổi khóa khác trùng giờ — không nên xếp lịch này.
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Khóa trùng</th>
                                        <th>Nguồn</th>
                                        <th>Mã lịch</th>
                                        <th>Thời gian</th>
                                        <th>Ghi chú</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($result['probe_hits'] as $hit)
                                        <tr>
                                            <td>
                                                <a href="{{ route('pmgplx.lich.gv.index', ['ma_kh' => $hit['ma_kh']]) }}" target="_blank" rel="noopener">
                                                    {{ $hit['ma_kh'] }}
                                                </a>
                                            </td>
                                            <td>{{ $hit['nguon'] === 'xe' ? 'Lịch xe' : 'Lịch GV' }}</td>
                                            <td>{{ $hit['ma_lich'] }}</td>
                                            <td>
                                                @php
                                                    try {
                                                        $s = \Carbon\Carbon::parse($hit['ngay_bd']);
                                                        $e = \Carbon\Carbon::parse($hit['ngay_kt']);
                                                        $label = $s->format('d/m/Y H:i').' – '.$e->format('H:i');
                                                    } catch (\Throwable) {
                                                        $label = $hit['ngay_bd'].' – '.$hit['ngay_kt'];
                                                    }
                                                @endphp
                                                {{ $label }}
                                            </td>
                                            <td>
                                                @if (! empty($hit['bien_so_xe']))
                                                    Xe {{ $hit['bien_so_xe'] }}
                                                @else
                                                    —
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @else
                    @php
                        $stats = $result['stats'];
                    @endphp
                    <p class="mb-2">
                        Đã nạp <strong>{{ number_format($stats['slots_loaded']) }}</strong> buổi;
                        tìm thấy <strong>{{ number_format($stats['pairs_total']) }}</strong> cặp trùng
                        @if ($stats['pairs_shown'] < $stats['pairs_total'])
                            (hiển thị {{ number_format($stats['pairs_shown']) }} đầu)
                        @endif
                        .
                    </p>
                    @if ($stats['slots_truncated'])
                        <div class="alert alert-warning py-2">
                            Vượt giới hạn {{ number_format(\App\Support\PMGPLX\GiaoVienLichTrungScanner::MAX_SLOTS) }} buổi/lần — thu hẹp khoảng ngày hoặc chọn thêm GV/khóa.
                        </div>
                    @endif

                    @if ($stats['pairs_total'] === 0)
                        <div class="alert alert-success mb-0">Không phát hiện trùng lịch theo tiêu chí đã chọn.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>GV</th>
                                        <th>Khóa A</th>
                                        <th>Buổi A</th>
                                        <th>Khóa B</th>
                                        <th>Buổi B</th>
                                        <th>Giao trùng</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($result['pairs'] as $pair)
                                        @php
                                            $a = $pair['slot_a'];
                                            $b = $pair['slot_b'];
                                            $fmt = function ($slot) {
                                                try {
                                                    $s = \Carbon\Carbon::parse($slot['ngay_bd']);
                                                    $e = \Carbon\Carbon::parse($slot['ngay_kt']);
                                                    $src = ($slot['nguon'] ?? '') === 'xe' ? ' (xe' . ($slot['bien_so_xe'] ? ' '.$slot['bien_so_xe'] : '') . ')' : '';
                                                    return $s->format('d/m/Y H:i').' – '.$e->format('H:i').$src;
                                                } catch (\Throwable) {
                                                    return $slot['ngay_bd'];
                                                }
                                            };
                                        @endphp
                                        <tr>
                                            <td>
                                                {{ $pair['ten_gv'] ?: $pair['ma_gv_norm'] }}
                                                <br><span class="text-muted small">{{ $pair['ma_gv_norm'] }}</span>
                                            </td>
                                            <td>
                                                <a href="{{ route('pmgplx.lich.gv.index', ['ma_kh' => $a['ma_kh'], 'ma_gv' => $a['ma_gv']]) }}" target="_blank" rel="noopener">{{ $a['ma_kh'] }}</a>
                                            </td>
                                            <td class="small">{{ $fmt($a) }}</td>
                                            <td>
                                                <a href="{{ route('pmgplx.lich.gv.index', ['ma_kh' => $b['ma_kh'], 'ma_gv' => $b['ma_gv']]) }}" target="_blank" rel="noopener">{{ $b['ma_kh'] }}</a>
                                            </td>
                                            <td class="small">{{ $fmt($b) }}</td>
                                            <td class="small text-danger">{{ $pair['overlap_label'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    @endif
@endsection

@push('scripts')
<script>
    (function () {
        var MODE_PROBE = @json(\App\Support\PMGPLX\GiaoVienLichTrungScanner::MODE_PROBE);
        var MODE_BY_GV = @json(\App\Support\PMGPLX\GiaoVienLichTrungScanner::MODE_BY_GV);
        var MODE_BY_RANGE = @json(\App\Support\PMGPLX\GiaoVienLichTrungScanner::MODE_BY_RANGE);

        function currentMode() {
            var el = document.querySelector('input.mode-radio:checked');
            return el ? el.value : MODE_BY_GV;
        }

        function syncPanels() {
            var mode = currentMode();
            var isProbe = mode === MODE_PROBE;
            var isRange = mode === MODE_BY_RANGE;
            document.querySelectorAll('.panel-probe').forEach(function (el) {
                el.classList.toggle('d-none', !isProbe);
            });
            document.querySelectorAll('.panel-range').forEach(function (el) {
                el.classList.toggle('d-none', isProbe);
            });
            document.querySelectorAll('.mode-hint-probe').forEach(function (el) {
                el.classList.toggle('d-none', !isProbe);
            });
            document.querySelectorAll('.mode-hint-range').forEach(function (el) {
                el.classList.toggle('d-none', !isRange);
            });
            var maGvRequired = mode === MODE_PROBE || mode === MODE_BY_GV;
            document.querySelector('[data-field="ma_gv"] label').textContent = maGvRequired
                ? 'Giáo viên *'
                : 'Giáo viên (tùy chọn)';
        }

        document.querySelectorAll('.mode-radio').forEach(function (r) {
            r.addEventListener('change', syncPanels);
        });
        syncPanels();

        $('#ma_gv, #ma_kh').select2({
            theme: 'bootstrap4',
            allowClear: true,
            width: '100%',
            placeholder: 'Chọn…',
        });

        var MAX_SPAN_DAYS = @json(\App\Support\PMGPLX\GiaoVienLichTrungScanner::MAX_RANGE_DAYS);

        function pad2(n) {
            return n < 10 ? '0' + n : String(n);
        }

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
            if (!key) {
                return;
            }
            var today = startOfToday();
            var tu = today;
            var den = today;
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
                var key = presetEl.value;
                applyDatePreset(key);
                presetEl.value = '';
            });
        }
    })();
</script>
@endpush
