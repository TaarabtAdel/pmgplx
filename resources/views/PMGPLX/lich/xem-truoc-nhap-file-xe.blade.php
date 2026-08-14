@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Xem trước lịch xe tập lái (nhập file)')

@push('styles')
<style>
    tr.row-skip-save > td {
        background: #fff3cd !important;
    }
    tr.row-tu-dong > td {
        background: #e9d5ff !important;
    }
    tr.row-will-update > td {
        background: #cce5ff !important;
    }
</style>
@endpush

@section('content')
    @php
        $okCount = (int) ($preview['meta']['xe_ok_count'] ?? count($rows));
        $conflictCount = (int) ($preview['meta']['xe_conflict_count'] ?? 0);
        $skipCount = (int) ($preview['meta']['xe_skip_count'] ?? 0);
        $khoaHocList = $preview['meta']['khoa_hoc_list'] ?? [];
        $ngayList = collect($rows)
            ->map(fn ($r) => \Carbon\Carbon::parse($r['NgayBD'])->format('d/m/Y'))
            ->unique()
            ->values();
        $tenGvs = collect($rows)
            ->map(fn ($r) => $r['TenGV'].' ('.$r['MaGV'].')')
            ->unique()
            ->values();
        $tuDongCount = collect($rows)->filter(
            fn ($r) => \App\Support\PMGPLX\LichExcelCellStyle::containsTuDong($r['noi_dung'] ?? '')
        )->count();
        $updateModeXe = ! empty($preview['meta']['update_mode_xe']);
        $updateCount = (int) ($preview['meta']['xe_update_count'] ?? 0);
    @endphp

    <div class="card card-panel">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Xem trước Lịch xe tập lái — Nhập từ file</span>
            <a href="{{ route('pmgplx.lich.nhap-file.preview-gv') }}" class="btn btn-sm btn-outline-secondary">← Quay lại lịch GV</a>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <div><strong>File:</strong> {{ $preview['file_name'] ?? '' }}</div>
                <div><strong>Khóa học:</strong> {{ implode(', ', $khoaHocList) }}</div>
                <div><strong>Giáo viên:</strong> {{ $tenGvs->implode(', ') }}</div>
                <div><strong>Ngày:</strong> {{ $ngayList->implode(', ') }}</div>
                <div>
                    <strong>Tổng buổi:</strong> {{ count($rows) }} —
                    sẽ lưu <strong class="text-success">{{ $okCount }}</strong>,
                    bỏ qua <strong class="text-danger">{{ $conflictCount }}</strong> trùng
                    @if ($updateModeXe && $updateCount > 0)
                        , <strong class="text-primary">{{ $updateCount }}</strong> cập nhật
                    @endif
                    @if ($skipCount > 0)
                        , <strong class="text-warning">{{ $skipCount }}</strong> bỏ qua
                    @endif
                </div>
            </div>

            <div class="alert alert-info mb-2">
                Địa điểm tự gán theo nội dung Excel (Hình/Ôn luyện STL → Sân tập; Cabin → Trung tâm; còn lại → Tuyến đường). Có thể sửa trên dòng.
            </div>

            @if ($tuDongCount > 0)
                <div class="alert mb-2" style="background:#e9d5ff;border:1px solid #c77dff;color:#111;">
                    Dòng nền <strong>tím</strong> có <strong>Tự động</strong> trong cột <strong>Nội dung</strong>
                    ({{ $tuDongCount }} buổi) — vui lòng <strong>dò lại xe tập</strong> trước khi tiếp tục.
                </div>
            @endif

            @if ($skipCount > 0)
                <div class="alert alert-warning mb-2">
                    Dòng nền vàng có <strong>CABIN</strong> hoặc <strong>Bổ Sung</strong> trong Nội dung – Chi tiết — sẽ không lưu lịch xe.
                </div>
            @endif

            @if ($conflictCount > 0 && ! $updateModeXe)
                <div class="alert alert-warning">
                    Dòng nền đỏ đã trùng lịch (GV hoặc xe). Khi lưu sẽ bỏ qua các dòng này (trừ khi bật <strong>Chế độ cập nhật</strong>).
                </div>
            @endif

            @if ($updateModeXe)
                <div class="alert alert-primary">
                    Đang bật <strong>Chế độ cập nhật</strong> — dòng trùng lịch xe sẽ được <strong>cập nhật</strong> thay vì bỏ qua.
                </div>
            @endif

            <form method="POST" action="{{ route('pmgplx.lich.nhap-file.to-db') }}" id="formConfirmNhapFileXe">
                @csrf
                <div class="custom-control custom-checkbox mb-3">
                    <input type="checkbox" class="custom-control-input" id="cheDoCapNhatXe" name="che_do_cap_nhat" value="1"
                           @checked(old('che_do_cap_nhat', $updateModeXe))>
                    <label class="custom-control-label" for="cheDoCapNhatXe">
                        <strong>Chế độ cập nhật</strong> — dòng trùng lịch xe sẽ được <strong>cập nhật</strong> thay vì bỏ qua / thêm mới
                    </label>
                </div>
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>STT</th>
                                <th style="min-width: 10rem;">Xe tập</th>
                                <th>Mã GV</th>
                                <th>Giáo viên</th>
                                <th class="col-noi-dung-chi-tiet">Nội dung – Chi tiết</th>
                                <th style="min-width: 12rem;">Địa điểm</th>
                                <th style="min-width: 13rem;">TG bắt đầu</th>
                                <th style="min-width: 13rem;">TG kết thúc</th>
                                <th style="min-width: 9rem;">Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $i => $row)
                                @php
                                    $skipSave = !empty($row['skip_save']);
                                    $isTuDong = \App\Support\PMGPLX\LichExcelCellStyle::containsTuDong($row['noi_dung'] ?? '');
                                    $rowClass = $skipSave
                                        ? 'row-skip-save'
                                        : (! empty($row['will_update'])
                                            ? 'row-will-update'
                                            : (! empty($row['conflict']) ? 'table-danger' : ($isTuDong ? 'row-tu-dong' : '')));
                                    $hasXeTrung = ! $skipSave && (! empty($row['conflict']) || ! empty($row['will_update']));
                                @endphp
                                <tr class="{{ $rowClass }}" @if ($hasXeTrung) data-xe-trung="1" @endif>
                                    <td>{{ $i + 1 }}</td>
                                    <td>
                                        <input type="hidden" name="rows[{{ $i }}][source_key]" value="{{ $row['source_key'] ?? '' }}">
                                        <input type="hidden" name="rows[{{ $i }}][MaKH]" value="{{ $row['MaKH'] }}">
                                        @if ($skipSave)
                                            <input type="hidden" name="rows[{{ $i }}][BienSoXe]" value="">
                                            <span class="text-muted small">—</span>
                                        @else
                                        <select
                                            name="rows[{{ $i }}][BienSoXe]"
                                            class="form-control form-control-sm select-xe-preview"
                                            data-ma-gv="{{ $row['MaGV'] }}"
                                            required
                                        >
                                            <option value="">-- Chọn xe --</option>
                                            @php $curXe = (string) ($row['BienSoXe'] ?? ''); @endphp
                                            @if ($curXe !== '' && ! $xeTaps->contains($curXe))
                                                <option value="{{ $curXe }}" selected>{{ $curXe }}</option>
                                            @endif
                                            @foreach ($xeTaps as $xe)
                                                <option value="{{ $xe }}" @selected($curXe === $xe)>{{ $xe }}</option>
                                            @endforeach
                                        </select>
                                        @endif
                                    </td>
                                    <td>
                                        <input type="text" name="rows[{{ $i }}][MaGV]" class="form-control form-control-sm"
                                               value="{{ $row['MaGV'] }}" readonly required>
                                    </td>
                                    <td>
                                        <input type="text" name="rows[{{ $i }}][TenGV]" class="form-control form-control-sm"
                                               value="{{ $row['TenGV'] }}" readonly required>
                                    </td>
                                    <td class="col-noi-dung-chi-tiet">
                                        @include('PMGPLX.lich._noi-dung-chi-tiet', [
                                            'noiDung' => $row['noi_dung'] ?? '',
                                            'chiTiet' => $row['chi_tiet'] ?? '',
                                        ])
                                    </td>
                                    <td>
                                        @if ($skipSave)
                                            <input type="hidden" name="rows[{{ $i }}][DiaDiem]" value="">
                                            <span class="text-muted small">—</span>
                                        @else
                                        <select name="rows[{{ $i }}][DiaDiem]" class="form-control form-control-sm" required>
                                            <option value="">-- Chọn địa điểm --</option>
                                            @foreach ($diaDiems as $dd)
                                                <option value="{{ $dd }}" @selected(($row['DiaDiem'] ?? '') === $dd)>{{ $dd }}</option>
                                            @endforeach
                                            @if (($row['DiaDiem'] ?? '') !== '' && ! in_array($row['DiaDiem'], $diaDiems, true))
                                                <option value="{{ $row['DiaDiem'] }}" selected>{{ $row['DiaDiem'] }}</option>
                                            @endif
                                        </select>
                                        @endif
                                    </td>
                                    <td>
                                        @include('PMGPLX.lich._lich-datetime-inputs', [
                                            'name' => 'rows['.$i.'][NgayBD]',
                                            'value' => $row['NgayBD'],
                                            'required' => true,
                                        ])
                                    </td>
                                    <td>
                                        @include('PMGPLX.lich._lich-datetime-inputs', [
                                            'name' => 'rows['.$i.'][NgayKT]',
                                            'value' => $row['NgayKT'],
                                            'required' => true,
                                        ])
                                    </td>
                                    <td>
                                        @if ($skipSave)
                                            <span class="text-warning font-weight-bold">{{ $row['ghi_chu'] ?? 'Bỏ qua' }}</span>
                                        @elseif (! empty($row['will_update']))
                                            <span class="text-primary font-weight-bold ghi-chu-xe">Sẽ cập nhật</span>
                                        @elseif (!empty($row['conflict']))
                                            <span class="text-danger font-weight-bold ghi-chu-xe">Đã thêm vào lịch</span>
                                        @elseif (trim((string) ($row['BienSoXe'] ?? '')) === '')
                                            <span class="text-warning font-weight-bold">Chưa gắn xe</span>
                                        @else
                                            <span class="text-success">Sẽ lưu mới</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <button type="submit" class="btn btn-navy btn-lg">Xem trước dữ liệu lưu DB</button>
                <a href="{{ route('pmgplx.lich.nhap-file.preview-gv') }}" class="btn btn-outline-secondary btn-lg ml-2">← Quay lại lịch GV</a>
                <a href="{{ route('pmgplx.lich.nhap-file.preview') }}" class="btn btn-outline-secondary btn-lg ml-2">← Quay lại Excel</a>
                <a href="{{ route('pmgplx.lich.nhap-file.cancel') }}" class="btn btn-outline-danger btn-lg ml-2">Hủy</a>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $('.select-xe-preview').select2({
        theme: 'bootstrap4',
        placeholder: 'Tìm biển số xe...',
        allowClear: true,
        width: '100%',
        dropdownParent: $(document.body)
    });

    $(document).on('change', '.select-xe-preview', function () {
        var $src = $(this);
        var maGv = $src.data('ma-gv');
        var val = $src.val() || '';
        $('.select-xe-preview').not($src).each(function () {
            var $el = $(this);
            if (String($el.data('ma-gv')) === String(maGv) && !$el.prop('disabled')) {
                $el.val(val).trigger('change.select2');
            }
        });
    });

    function applyCheDoCapNhatXeUi(on) {
        $('tr[data-xe-trung="1"]').each(function () {
            var $tr = $(this);
            var $note = $tr.find('.ghi-chu-xe');
            if (on) {
                $tr.removeClass('table-danger').addClass('row-will-update');
                $note.removeClass('text-danger').addClass('text-primary').text('Sẽ cập nhật');
            } else {
                $tr.removeClass('row-will-update').addClass('table-danger');
                $note.removeClass('text-primary').addClass('text-danger').text('Đã thêm vào lịch');
            }
        });
    }

    $('#cheDoCapNhatXe').on('change', function () {
        applyCheDoCapNhatXeUi($(this).is(':checked'));
    });

    if ($('#cheDoCapNhatXe').is(':checked')) {
        applyCheDoCapNhatXeUi(true);
    }
</script>
@endpush
