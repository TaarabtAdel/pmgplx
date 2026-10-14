@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Phân công học viên')

@push('styles')
<style>
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
                <a href="{{ route('daotao.pdt.dat.giao-vien-day-thay', array_filter(['ma_khoa_hoc' => $maKhoaHoc ?? ''])) }}" class="btn btn-sm btn-outline-warning mr-1">GV dạy thay</a>
                <a href="{{ route('daotao.pdt.dat.xe-day-thay', array_filter(['ma_khoa_hoc' => $maKhoaHoc ?? ''])) }}" class="btn btn-sm btn-outline-warning mr-1">Xe dạy thay</a>
                <a href="{{ route('daotao.pdt.dat.nhap-phan-cong-hoc-vien') }}" class="btn btn-sm btn-navy mr-1">Nhập từ Excel</a>
                <a href="{{ route('daotao.pdt.dat.quan-ly-phien') }}" class="btn btn-sm btn-outline-secondary">Chi tiết phiên</a>
            </div>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success py-2">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger py-2">{!! nl2br(e(session('error'))) !!}</div>
            @endif

            <form method="GET" action="{{ route('daotao.pdt.dat.phan-cong-hoc-vien') }}" class="mb-3">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-5 mb-2">
                        <label for="filter_ma_khoa_hoc" class="small mb-1">Khóa học <span class="text-danger">*</span></label>
                        <select name="ma_khoa_hoc" id="filter_ma_khoa_hoc" class="form-control form-control-sm" required>
                            <option value="">— Chọn khóa —</option>
                            @foreach ($khoaHocOptions as $kh)
                                <option value="{{ $kh->MaKhoaHoc }}" @selected(($maKhoaHoc ?? '') === $kh->MaKhoaHoc)>
                                    @if ($kh->TenKhoaHoc !== '')
                                        {{ $kh->TenKhoaHoc }} ({{ $kh->MaKhoaHoc }})
                                    @else
                                        {{ $kh->MaKhoaHoc }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Chọn khóa để xem phân công và danh sách học viên. GV lấy từ <a href="{{ route('pmgplx.lich.xe.index') }}" target="_blank" rel="noopener">lịch xe tập</a> khóa.</small>
                    </div>
                    @if (($maKhoaHoc ?? '') !== '')
                        <div class="form-group col-md-3 mb-2">
                            <label for="filter_tu_khoa" class="small mb-1">Lọc học viên (mã / họ tên)</label>
                            <input type="text" name="tu_khoa" id="filter_tu_khoa"
                                   class="form-control form-control-sm"
                                   value="{{ $hocVienFilters['tu_khoa'] ?? '' }}"
                                   placeholder="Tùy chọn...">
                        </div>
                        <div class="form-group col-md-4 mb-2 d-flex align-items-end">
                            <div class="custom-control custom-checkbox mb-1">
                                <input type="checkbox" class="custom-control-input" id="filter_an_da_phan_cong"
                                       name="an_da_phan_cong" value="1"
                                       @checked($hocVienFilters['an_da_phan_cong'] ?? false)>
                                <label class="custom-control-label small" for="filter_an_da_phan_cong">
                                    Ẩn học viên đã phân công (trong khóa này)
                                </label>
                            </div>
                        </div>
                        <div class="form-group col-md-3 mb-2">
                            <button type="submit" class="btn btn-sm btn-navy btn-block">Áp dụng</button>
                            <a href="{{ route('daotao.pdt.dat.phan-cong-hoc-vien', ['ma_khoa_hoc' => $maKhoaHoc]) }}"
                               class="btn btn-sm btn-outline-secondary btn-block mt-1">Bỏ lọc HV</a>
                        </div>
                    @else
                        <div class="form-group col-md-3 mb-2">
                            <button type="submit" class="btn btn-sm btn-navy btn-block">Xem</button>
                        </div>
                    @endif
                </div>
            </form>

            @if (($maKhoaHoc ?? '') === '')
                <p class="text-muted mb-0">Chọn khóa học để hiển thị phân công theo giáo viên và danh sách học viên khóa.</p>
            @else
                @if (($phanCongTrungMaHocVien ?? []) !== [])
                    <div class="alert alert-warning py-2 mb-3">
                        <strong>Trùng phân công:</strong> một số mã học viên gắn nhiều giáo viên hoặc nhiều dòng trong khóa.
                        <ul class="mb-0 pl-3 small mt-1">
                            @foreach ($phanCongTrungMaHocVien as $dup)
                                <li>
                                    Mã <code>{{ $dup['ma_hoc_vien'] }}</code>
                                    — {{ $dup['count'] }} dòng
                                    @if (($dup['ma_giao_vien_list'] ?? []) !== [])
                                        · GV: {{ implode(', ', $dup['ma_giao_vien_list']) }}
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <p class="small text-muted mb-2">Mỗi học viên (mã ĐK) trong khóa chỉ được phân công <strong>một</strong> giáo viên. Gán sang GV khác cần xóa phân công cũ trước.</p>

                @if ($khoaHocOptions->isNotEmpty())
                    <div class="d-flex flex-wrap align-items-center justify-content-end mb-2">
                        <form method="POST"
                              action="{{ route('daotao.pdt.dat.phan-cong-hoc-vien.destroy-by-course') }}"
                              class="d-flex align-items-center dat-xoa-khoa-form"
                              onsubmit="return confirm('Xóa toàn bộ phân công của khóa {{ $maKhoaHoc }}?');">
                            @csrf
                            <input type="hidden" name="ma_khoa_hoc" value="{{ $maKhoaHoc }}">
                            <button type="submit" class="btn btn-sm btn-outline-danger text-nowrap">Xóa toàn bộ phân công khóa</button>
                        </form>
                    </div>
                @endif

                <div class="card border mb-3">
                    <div class="card-header p-0" id="heading-phan-cong-theo-gv">
                        <button class="btn btn-link btn-block text-left px-3 py-2" type="button"
                                data-toggle="collapse" data-target="#collapse-phan-cong-theo-gv"
                                aria-expanded="true" aria-controls="collapse-phan-cong-theo-gv">
                            Phân công theo giáo viên
                            @if (($tenKhoa ?? '') !== '')
                                — {{ $tenKhoa }}
                            @endif
                            <code class="small ml-1">{{ $maKhoaHoc }}</code>
                            <span class="badge badge-secondary ml-1">{{ count($phanCongTheoGv) }} GV</span>
                        </button>
                    </div>
                    <div id="collapse-phan-cong-theo-gv" class="collapse show" aria-labelledby="heading-phan-cong-theo-gv">
                        <div class="card-body pt-2 pb-2">
                            @if ($phanCongTheoGv === [])
                                <p class="text-muted small mb-0">Không có giáo viên trên lịch xe tập khóa này.</p>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead class="thead-light">
                                            <tr>
                                                <th style="width: 22%">Giáo viên</th>
                                                <th>Học viên</th>
                                                <th style="width: 5rem" class="text-center">Xóa</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($phanCongTheoGv as $gvGroup)
                                                @php $hvList = $gvGroup['hoc_vien'] ?? []; @endphp
                                                @if ($hvList === [])
                                                    <tr>
                                                        <td>
                                                            <strong>{{ $gvGroup['ten_giao_vien'] ?? '' }}</strong>
                                                            <div class="small text-muted">
                                                                <code>{{ $gvGroup['ma_giao_vien'] ?? '' }}</code>
                                                            </div>
                                                        </td>
                                                        <td class="text-muted small">— Chưa phân công học viên —</td>
                                                        <td></td>
                                                    </tr>
                                                @else
                                                    @foreach ($hvList as $hvIndex => $hv)
                                                        <tr>
                                                            @if ($hvIndex === 0)
                                                                <td rowspan="{{ count($hvList) }}" class="align-top">
                                                                    <strong>{{ $gvGroup['ten_giao_vien'] ?? '' }}</strong>
                                                                    <div class="small text-muted">
                                                                        <code>{{ $gvGroup['ma_giao_vien'] ?? '' }}</code>
                                                                    </div>
                                                                </td>
                                                            @endif
                                                            <td>
                                                                {{ $hv['ho_ten'] ?? '' }}
                                                                <span class="text-muted small">(<code>{{ $hv['ma_hoc_vien'] ?? '' }}</code>)</span>
                                                            </td>
                                                            <td class="text-center align-middle">
                                                                <form method="POST"
                                                                      action="{{ route('daotao.pdt.dat.phan-cong-hoc-vien.destroy', $hv['id']) }}"
                                                                      onsubmit="return confirm('Xóa học viên này khỏi phân công giáo viên {{ $gvGroup['ten_giao_vien'] ?? '' }}?');">
                                                                    @csrf
                                                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0" title="Xóa khỏi phân công">×</button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card border">
                    <div class="card-header py-2">Học viên khóa {{ $maKhoaHoc }}</div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap align-items-center mb-3">
                            <button type="button" class="btn btn-sm btn-primary mb-2 mr-2" id="btn-them-phan-cong" disabled
                                    data-toggle="modal" data-target="#modal-phan-cong-hv">
                                Thêm vào phân công
                            </button>
                            <div class="ml-auto d-flex flex-wrap align-items-center mb-2">
                                <span class="mr-3 small">Tổng: <strong>{{ number_format($hocVienItems->total()) }}</strong></span>
                                <form method="GET" action="{{ route('daotao.pdt.dat.phan-cong-hoc-vien') }}" class="form-inline">
                                    <input type="hidden" name="ma_khoa_hoc" value="{{ $maKhoaHoc }}">
                                    <input type="hidden" name="tu_khoa" value="{{ $hocVienFilters['tu_khoa'] ?? '' }}">
                                    @if ($hocVienFilters['an_da_phan_cong'] ?? false)
                                        <input type="hidden" name="an_da_phan_cong" value="1">
                                    @endif
                                    <label class="mr-2 mb-0 small">Số dòng/trang</label>
                                    <select name="per_page" class="form-control form-control-sm" onchange="this.form.submit()">
                                        @foreach ([20, 50, 100, 200] as $n)
                                            <option value="{{ $n }}" @selected((int) ($hocVienFilters['per_page'] ?? 50) === $n)>{{ $n }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered table-striped table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width:2.5rem">
                                            <input type="checkbox" id="hv-check-all" title="Chọn tất cả trang này">
                                        </th>
                                        <th>STT</th>
                                        <th>Mã ĐK</th>
                                        <th>Họ và tên</th>
                                        <th>Số CMT</th>
                                        <th>Số hồ sơ</th>
                                        <th>Giáo viên (phân công)</th>
                                        <th>Hạng</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($hocVienItems as $index => $row)
                                        @php
                                            $hoTenHv = $row->HoVaTen ?: trim(($row->HoDemNLX ?? '').' '.($row->TenNLX ?? ''));
                                            $maKhHv = trim((string) ($row->MaKhoaHoc ?? ''));
                                            $pcKey = \App\Support\DaoTao\DatPhanCongHocVienSaver::rowKeyForPhanCong($maKhHv, (string) $row->MaDK);
                                            $pc = $phanCongByRowKey[$pcKey] ?? null;
                                            $maGvPcNorm = ($pc && ($pc['ma_giao_vien'] ?? '') !== '')
                                                ? \App\Support\DaoTao\DatPhanCongHocVienSaver::normalizeMaGiaoVien($pc['ma_giao_vien'])
                                                : '';
                                            $tenGvPc = ($pc['ten_giao_vien'] ?? '') !== '' ? $pc['ten_giao_vien'] : ($pc['ma_giao_vien'] ?? '');
                                        @endphp
                                        <tr>
                                            <td class="text-center">
                                                <input type="checkbox" class="hv-row-check"
                                                       data-ma-dk="{{ $row->MaDK }}"
                                                       data-ma-kh="{{ $maKhHv }}"
                                                       data-ho-ten="{{ $hoTenHv }}"
                                                       data-ma-gv-pc="{{ $maGvPcNorm }}"
                                                       data-ten-gv-pc="{{ $tenGvPc }}">
                                            </td>
                                            <td>{{ $hocVienItems->firstItem() + $index }}</td>
                                            <td><code>{{ $row->MaDK }}</code></td>
                                            <td>{{ $hoTenHv }}</td>
                                            <td>{{ $row->SoCMT }}</td>
                                            <td>{{ $row->SoHoSo }}</td>
                                            <td class="small">
                                                @if ($pc && ($pc['ma_giao_vien'] ?? '') !== '')
                                                    {{ $pc['ten_giao_vien'] ?? $pc['ma_giao_vien'] }}
                                                    <div class="text-muted"><code>{{ $pc['ma_giao_vien'] }}</code></div>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td>{{ $row->HangGPLX }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-4 text-muted">Không có học viên khóa này.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if ($hocVienItems->hasPages())
                            <div class="d-flex justify-content-end mt-3">
                                {{ $hocVienItems->links('pagination::bootstrap-4') }}
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="modal-phan-cong-hv" tabindex="-1" role="dialog" aria-labelledby="modal-phan-cong-hv-label" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <form method="POST" action="{{ route('daotao.pdt.dat.phan-cong-hoc-vien.them') }}" id="form-phan-cong-hv">
                @csrf
                <input type="hidden" name="ma_khoa_hoc" value="{{ $maKhoaHoc ?? '' }}">
                <input type="hidden" name="tu_khoa" value="{{ $hocVienFilters['tu_khoa'] ?? '' }}">
                <input type="hidden" name="per_page" value="{{ $hocVienFilters['per_page'] ?? 50 }}">
                @if ($hocVienFilters['an_da_phan_cong'] ?? false)
                    <input type="hidden" name="an_da_phan_cong" value="1">
                @endif
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <h5 class="modal-title" id="modal-phan-cong-hv-label">Thêm vào phân công</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="small text-muted mb-2">Học viên đã chọn (<span id="pc-hv-count">0</span>):</p>
                        <div class="alert alert-warning py-2 small d-none mb-2" id="pc-hv-conflict-warn" role="alert"></div>
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

    function normalizeMaGvClient(ma) {
        ma = String(ma || '').trim();
        if (ma === '') {
            return '';
        }
        var digits = ma.replace(/\D+/g, '');
        if (digits !== '' && digits.length <= 8) {
            while (digits.length < 8) {
                digits = '0' + digits;
            }
            return digits;
        }
        return ma;
    }

    function collectPhanCongConflicts(targetMaGv) {
        var target = normalizeMaGvClient(targetMaGv);
        var conflicts = [];
        document.querySelectorAll('.hv-row-check:checked').forEach(function (cb) {
            var assigned = cb.getAttribute('data-ma-gv-pc') || '';
            if (assigned === '' || assigned === target) {
                return;
            }
            conflicts.push({
                hoTen: cb.getAttribute('data-ho-ten') || '',
                maDk: cb.getAttribute('data-ma-dk') || '',
                tenGv: cb.getAttribute('data-ten-gv-pc') || assigned,
                maGv: assigned
            });
        });
        return conflicts;
    }

    function rebuildPhanCongModalLists() {
        var checks = document.querySelectorAll('.hv-row-check:checked');
        var listEl = document.getElementById('pc-hv-list');
        var hiddenEl = document.getElementById('pc-hv-hidden-fields');
        var countEl = document.getElementById('pc-hv-count');
        var warnEl = document.getElementById('pc-hv-conflict-warn');
        if (! listEl || ! hiddenEl) {
            return;
        }
        listEl.innerHTML = '';
        hiddenEl.innerHTML = '';
        if (warnEl) {
            warnEl.classList.add('d-none');
            warnEl.textContent = '';
        }
        var i = 0;
        var targetGv = $('#pc_ma_giao_vien').val();
        var conflicts = collectPhanCongConflicts(targetGv);
        if (warnEl && conflicts.length > 0) {
            warnEl.classList.remove('d-none');
            warnEl.textContent = conflicts.map(function (c) {
                return c.hoTen + ' (' + c.maDk + ') đang thuộc GV ' + c.tenGv;
            }).join(' · ');
        }
        checks.forEach(function (cb) {
            var maDk = cb.getAttribute('data-ma-dk') || '';
            var maKh = cb.getAttribute('data-ma-kh') || '';
            var hoTen = cb.getAttribute('data-ho-ten') || '';
            var li = document.createElement('li');
            li.className = 'mb-1';
            var assigned = cb.getAttribute('data-ma-gv-pc') || '';
            var suffix = assigned !== '' && assigned !== normalizeMaGvClient(targetGv)
                ? ' — đã GV khác'
                : '';
            li.textContent = hoTen + ' · ' + maDk + suffix;
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
        document.querySelectorAll('.hv-row-check').forEach(function (cb) {
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

    $('#pc_ma_giao_vien').on('change select2:select', function () {
        rebuildPhanCongModalLists();
    });

    document.getElementById('form-phan-cong-hv')?.addEventListener('submit', function (e) {
        var targetGv = $('#pc_ma_giao_vien').val();
        if (! targetGv) {
            return;
        }
        var conflicts = collectPhanCongConflicts(targetGv);
        if (conflicts.length === 0) {
            return;
        }
        e.preventDefault();
        var lines = conflicts.map(function (c) {
            return '• ' + c.hoTen + ' (' + c.maDk + ') → đang phân công GV ' + c.tenGv;
        });
        alert('Không thể gán thêm giáo viên khác cho cùng học viên:\n\n' + lines.join('\n')
            + '\n\nXóa phân công cũ (× trên bảng GV) rồi thử lại.');
    });

    $('#filter_ma_khoa_hoc').select2({
        theme: 'bootstrap4',
        allowClear: true,
        width: '100%',
        placeholder: '— Chọn khóa —'
    });

    $('#pc_ma_giao_vien, #pc_bien_so_xe, #pc_bien_so_xe_tu_dong').select2({
        theme: 'bootstrap4',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#modal-phan-cong-hv')
    });

    $('#pc_ma_giao_vien').on('change select2:select', fillBienSoFromGiaoVien);
</script>
@endpush
