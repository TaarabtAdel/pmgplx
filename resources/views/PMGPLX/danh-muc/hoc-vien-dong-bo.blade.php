@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Đồng bộ học viên qua bản cũ')

@push('styles')
<style>
    .hoc-vien-dong-bo-planned-scroll {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .hoc-vien-dong-bo-planned-scroll .col-ma-dk-du-kien {
        min-width: 220px;
        width: 220px;
        white-space: nowrap;
    }
    .hoc-vien-dong-bo-preview .col-ho-ten {
        min-width: 160px;
        width: 160px;
    }
    .hoc-vien-dong-bo-planned-scroll .table {
        min-width: 780px;
    }
    .hoc-vien-dong-bo-row-conflict {
        background-color: #fff3cd;
    }
    .hoc-vien-dong-bo-row-conflict code {
        color: #856404 !important;
    }
</style>
@endpush

@section('content')
    @php
        $meta = $preview['meta'] ?? null;
        $sourceRows = $preview['source'] ?? [];
        $plannedRows = $preview['planned'] ?? [];
        $conflictCount = (int) ($meta['conflict_count'] ?? 0);
        $syncableCount = (int) ($meta['syncable_count'] ?? max(0, count($sourceRows) - $conflictCount));
        $canSync = $maKhNguon !== '' && $maKhDich !== '' && $syncableCount > 0;
        $lastBatch = $lastBatch ?? null;
        $testStudent = $meta['test_student'] ?? null;
        $canTest = $maKhDich !== '' && is_array($testStudent) && ! empty($testStudent['ma_dk_nguon']);
    @endphp

    @if (! empty($lastBatch) && ! empty($lastBatch['ma_dks']))
        <div class="card card-panel mb-3 border-success">
            <div class="card-header bg-light d-flex flex-wrap justify-content-between align-items-center">
                <span>{{ ! empty($lastBatch['is_test']) ? 'Test đồng bộ gần đây' : 'Lần đồng bộ gần đây' }}</span>
                <span class="small text-muted">{{ $lastBatch['synced_at'] ?? '' }}</span>
            </div>
            <div class="card-body d-flex flex-wrap align-items-center">
                <div class="mr-3 mb-2">
                    @if (! empty($lastBatch['is_test']))
                        Đã test đưa <strong>1</strong> học viên
                        @if (! empty($lastBatch['ho_ten']))
                            (<strong>{{ $lastBatch['ho_ten'] }}</strong>)
                        @endif
                        — mã ĐK <code>{{ $lastBatch['source_ma_dk'] ?? '' }}</code> → <code>{{ $lastBatch['target_ma_dk'] ?? '' }}</code>
                    @else
                        Đã đưa <strong>{{ number_format((int) ($lastBatch['count'] ?? 0)) }}</strong> học viên
                    @endif
                    từ khóa <code>{{ $lastBatch['ma_kh_nguon'] ?? '' }}</code>
                    sang khóa <code>{{ $lastBatch['ma_kh_dich'] ?? '' }}</code> trên bản cũ.
                </div>
                <a href="{{ route('pmgplxold.dm.hoc-vien.index', ['ma_kh' => $lastBatch['ma_kh_dich'] ?? '', 'tu_khoa' => $lastBatch['target_ma_dk'] ?? '']) }}"
                   class="btn btn-outline-secondary btn-sm mb-2 mr-2" target="_blank">
                    Xem trên bản cũ
                </a>
                <form method="POST" action="{{ route('pmgplx.dm.hoc-vien.dong-bo.khoi-phuc') }}" class="mb-2"
                      onsubmit="return confirm('Khôi phục sẽ xóa {{ number_format((int) ($lastBatch['count'] ?? 0)) }} học viên vừa đồng bộ khỏi bản cũ. Tiếp tục?');">
                    @csrf
                    <button type="submit" class="btn btn-warning">
                        Khôi phục
                    </button>
                </form>
                <span class="text-muted small ml-3 mb-2">
                    Khôi phục = xóa dữ liệu đã đưa qua phần mềm cũ ở lần đồng bộ vừa rồi.
                </span>
            </div>
        </div>
    @endif

    <div class="card card-panel mb-3">
        <div class="card-header bg-light">Hướng dẫn sử dụng</div>
        <div class="card-body pb-2">
            <ol class="mb-3 pl-3">
                <li class="mb-2">
                    Chọn <strong>Mã khóa học nguồn</strong> (phần mềm mới) — danh sách học viên bên trái sẽ hiện ngay.
                </li>
                <li class="mb-2">
                    Chọn <strong>Khóa học đích</strong> (phần mềm cũ), bấm <strong>Xem trước</strong> — bảng bên phải hiện mã ĐK dự kiến (thay tiền tố mã khóa, phần số sau gộp liền).
                </li>
                <li class="mb-2">
                    Kiểm tra dòng vàng (<strong>Đã tồn tại</strong>): những mã ĐK đã có trên bản cũ sẽ <strong>bỏ qua</strong> khi đồng bộ; dòng có dấu ✓ xanh là học viên sẽ được đưa sang.
                </li>
                <li class="mb-2">
                    (Khuyến nghị) Bấm <strong>Test 1 học viên</strong> để thử trước — nếu không ổn, dùng <strong>Khôi phục</strong> để xóa trên bản cũ.
                </li>
                <li class="mb-2">
                    Bấm <strong>Xác nhận đồng bộ sang bản cũ</strong> để đưa toàn bộ học viên còn lại (chưa trùng mã) sang phần mềm cũ.
                </li>
            </ol>
            <div class="alert alert-warning mb-0">
                <strong>Lưu ý:</strong> Nếu bên <strong>Khóa học đích</strong> chưa có khóa học cần đưa qua thì cần <strong>tạo khóa học trên phần mềm cũ trước</strong>, sau đó quay lại chọn khóa đích và xem trước.
            </div>
        </div>
    </div>

    <div class="card card-panel mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Đồng bộ học viên qua bản cũ</span>
            <a href="{{ route('pmgplx.dm.hoc-vien.index') }}" class="btn btn-sm btn-outline-secondary">← Danh sách học viên</a>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('pmgplx.dm.hoc-vien.dong-bo.form') }}" id="form-chon-khoa">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-5">
                        <label for="ma_kh_nguon">Mã khóa học nguồn <span class="text-muted">(phần mềm mới)</span></label>
                        <select name="ma_kh_nguon" id="ma_kh_nguon" class="form-control form-control-sm" required>
                            <option value=""></option>
                            @foreach ($khoaHocsNguon as $kh)
                                @php
                                    $tenKhNguon = trim((string) ($kh->TenKH ?? ''));
                                    $labelKhNguon = ($tenKhNguon !== '' ? $tenKhNguon : $kh->MaKH).' ('.$kh->MaKH.')';
                                @endphp
                                <option value="{{ $kh->MaKH }}" data-khoa-label="{{ $labelKhNguon }}" @selected($maKhNguon === $kh->MaKH)>
                                    {{ $labelKhNguon }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-5">
                        <label for="ma_kh_dich">Khóa học đích <span class="text-muted">(phần mềm cũ)</span></label>
                        <select name="ma_kh_dich" id="ma_kh_dich" class="form-control form-control-sm">
                            <option value=""></option>
                            @foreach ($khoaHocsDich as $kh)
                                @php
                                    $tenKhDich = trim((string) ($kh->TenKH ?? ''));
                                    $labelKhDich = ($tenKhDich !== '' ? $tenKhDich : $kh->MaKH).' ('.$kh->MaKH.')';
                                @endphp
                                <option value="{{ $kh->MaKH }}" data-khoa-label="{{ $labelKhDich }}" @selected($maKhDich === $kh->MaKH)>
                                    {{ $labelKhDich }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary btn-block">Xem trước</button>
                    </div>
                </div>
            </form>

            @if ($meta)
                <div class="small text-muted mt-2 mb-0">
                    Khóa nguồn: <strong>{{ $meta['ten_kh_nguon'] ?? $meta['ma_kh_nguon'] }}</strong>
                    ({{ number_format((int) ($meta['count'] ?? 0)) }} học viên)
                    @if ($meta['ma_kh_dich'] ?? null)
                        — Khóa đích: <strong>{{ ($meta['ten_kh_dich'] ?? '') !== '' ? $meta['ten_kh_dich'] : $meta['ma_kh_dich'] }} ({{ $meta['ma_kh_dich'] }})</strong>
                        @if (($meta['ma_dk_prefix_nguon'] ?? null) && ($meta['ma_dk_prefix_dich'] ?? null))
                            — Mã ĐK dự kiến: thay <code>{{ $meta['ma_dk_prefix_nguon'] }}</code> → <code>{{ $meta['ma_dk_prefix_dich'] }}</code>
                        @endif
                    @else
                        — <em>Chọn khóa đích để xem mã ĐK dự kiến.</em>
                    @endif
                </div>
            @endif
        </div>
    </div>

    @if ($maKhNguon !== '')
        @if ($maKhDich !== '' && $conflictCount > 0)
            <div class="alert alert-warning mb-3">
                <strong>Lưu ý:</strong> {{ number_format($conflictCount) }} mã ĐK đã tồn tại trên bản cũ (dòng vàng bảng bên phải) — sẽ <strong>bỏ qua</strong> khi đồng bộ.
                Còn {{ number_format($syncableCount) }} học viên sẽ được đưa sang.
            </div>
        @endif

        <div class="row">
            <div class="col-lg-6 mb-3">
                <div class="card card-panel h-100">
                    <div class="card-header bg-light">
                        Học viên — phần mềm mới
                        @if ($meta)
                            <span class="badge badge-secondary ml-1">{{ number_format((int) ($meta['count'] ?? 0)) }}</span>
                        @endif
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive hoc-vien-dong-bo-preview">
                            <table class="table table-sm table-bordered table-striped table-hover table-data mb-0">
                                <thead>
                                    <tr>
                                        <th>STT</th>
                                        <th>Mã ĐK</th>
                                        <th class="col-ho-ten">Họ và tên</th>
                                        <th>Số CMT</th>
                                        <th>Số hồ sơ</th>
                                        <th>Hạng</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($sourceRows as $index => $row)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td><code>{{ $row['ma_dk'] }}</code></td>
                                            <td class="col-ho-ten">{{ $row['ho_ten'] }}</td>
                                            <td>{{ $row['so_cmt'] }}</td>
                                            <td>{{ $row['so_ho_so'] }}</td>
                                            <td>{{ $row['hang_gplx'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4">Không có học viên trong khóa nguồn</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-3">
                <div class="card card-panel h-100">
                    <div class="card-header bg-light">
                        Dự kiến đưa sang phần mềm cũ
                        @if ($maKhDich !== '' && $meta)
                            <span class="badge badge-info ml-1">{{ number_format((int) ($meta['count'] ?? 0)) }}</span>
                        @endif
                    </div>
                    <div class="card-body p-0">
                        @if ($maKhDich === '')
                            <div class="p-3 text-muted">Chọn <strong>mã khóa học đích</strong> và bấm <strong>Xem trước</strong> để hiện mã ĐK dự kiến (thay tiền tố mã khóa).</div>
                        @else
                            <div class="table-responsive hoc-vien-dong-bo-planned-scroll hoc-vien-dong-bo-preview">
                                <table class="table table-sm table-bordered table-striped table-hover table-data mb-0">
                                    <thead>
                                        <tr>
                                            <th>STT</th>
                                            <th class="col-ma-dk-du-kien">Mã ĐK dự kiến</th>
                                            <th class="col-ho-ten">Họ và tên</th>
                                            <th>Số CMT</th>
                                            <th>Mã khóa học</th>
                                            <th>Hạng</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($plannedRows as $index => $row)
                                            <tr @class(['hoc-vien-dong-bo-row-conflict' => ! empty($row['ton_tai'])])>
                                                <td>
                                                    @if (empty($row['ton_tai']))
                                                        <span class="text-success font-weight-bold" title="Chưa tồn tại trên bản cũ">✓</span>
                                                    @endif
                                                    {{ $index + 1 }}
                                                </td>
                                                <td class="col-ma-dk-du-kien">
                                                    <code @class(['text-primary' => empty($row['ton_tai']), 'text-warning' => ! empty($row['ton_tai'])])>{{ $row['ma_dk'] ?? '—' }}</code>
                                                    @if (! empty($row['ton_tai']))
                                                        <span class="badge badge-warning ml-1">Đã tồn tại</span>
                                                    @endif
                                                </td>
                                                <td class="col-ho-ten">{{ $row['ho_ten'] }}</td>
                                                <td>{{ $row['so_cmt'] }}</td>
                                                <td>{{ $row['ma_khoa_hoc'] }}</td>
                                                <td>{{ $row['hang_gplx'] }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-4">Không có dữ liệu</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if ($maKhDich !== '' && count($sourceRows) > 0 && $conflictCount > 0 && $syncableCount === 0)
            <div class="card card-panel">
                <div class="card-body text-muted">
                    Tất cả học viên đã có mã ĐK trên bản cũ — không còn ai để đồng bộ thêm.
                </div>
            </div>
        @endif

        @if ($canTest || $canSync)
            <div class="card card-panel">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center">
                        @if ($canTest)
                            <form method="POST" action="{{ route('pmgplx.dm.hoc-vien.dong-bo.test-mot') }}" class="mb-2 mr-3"
                                  onsubmit="return confirm('Test đồng bộ 1 học viên: {{ $testStudent['ho_ten'] ?? '' }}\n{{ $testStudent['ma_dk_nguon'] ?? '' }} → {{ $testStudent['ma_dk'] ?? '' }}\n\nTiếp tục?');">
                                @csrf
                                <input type="hidden" name="ma_kh_nguon" value="{{ $maKhNguon }}">
                                <input type="hidden" name="ma_kh_dich" value="{{ $maKhDich }}">
                                <input type="hidden" name="ma_dk_nguon" value="{{ $testStudent['ma_dk_nguon'] }}">
                                <button type="submit" class="btn btn-outline-primary">
                                    Test 1 học viên
                                </button>
                            </form>
                        @endif

                        @if ($canSync)
                            <form method="POST" action="{{ route('pmgplx.dm.hoc-vien.dong-bo.store') }}" class="mb-2 mr-3"
                                  onsubmit="return confirm('Đồng bộ {{ number_format($syncableCount) }} học viên từ khóa {{ $maKhNguon }} sang khóa {{ $maKhDich }} trên bản cũ?{{ $conflictCount > 0 ? '\nBỏ qua '.number_format($conflictCount).' mã ĐK đã tồn tại.' : '' }}');">
                                @csrf
                                <input type="hidden" name="ma_kh_nguon" value="{{ $maKhNguon }}">
                                <input type="hidden" name="ma_kh_dich" value="{{ $maKhDich }}">
                                <button type="submit" class="btn btn-info">
                                    Xác nhận đồng bộ sang bản cũ
                                </button>
                            </form>
                        @endif
                    </div>

                    @if ($canTest)
                        <div class="small text-muted mt-1">
                            Test sẽ đồng bộ học viên đầu tiên chưa trùng mã:
                            <strong>{{ $testStudent['ho_ten'] ?? '' }}</strong>
                            (<code>{{ $testStudent['ma_dk_nguon'] ?? '' }}</code> → <code>{{ $testStudent['ma_dk'] ?? '' }}</code>).
                            Sau test dùng <strong>Khôi phục</strong> để xóa trên bản cũ nếu cần.
                        </div>
                    @elseif ($canSync)
                        <div class="small text-muted mt-1">
                            Mã ĐK: thay tiền tố khóa, phần số sau gộp liền (vd. <code>45003-20260622090221</code>).
                            @if ($conflictCount > 0)
                                Dòng vàng = đã có trên bản cũ, sẽ bỏ qua.
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endif
    @endif
@endsection

@push('scripts')
<script>
    function initKhoaHocSelect2(selector, placeholder) {
        var $el = $(selector);
        $el.select2({
            theme: 'bootstrap4',
            placeholder: placeholder,
            allowClear: true,
            width: '100%',
            dropdownParent: $el.closest('.form-group'),
            language: {
                noResults: function () { return 'Không tìm thấy khóa học'; }
            },
            templateResult: function (data) {
                if (!data.id) {
                    return data.text;
                }
                var label = $(data.element).data('khoa-label');
                return label || data.text;
            },
            templateSelection: function (data) {
                if (!data.id) {
                    return placeholder;
                }
                var label = $(data.element).data('khoa-label');
                return label || data.text;
            }
        });
    }

    initKhoaHocSelect2('#ma_kh_nguon', '— Chọn khóa nguồn —');
    initKhoaHocSelect2('#ma_kh_dich', '— Chọn khóa đích —');

    $('#ma_kh_nguon').on('change', function () {
        if ($(this).val()) {
            $('#form-chon-khoa').submit();
        }
    });
</script>
@endpush
