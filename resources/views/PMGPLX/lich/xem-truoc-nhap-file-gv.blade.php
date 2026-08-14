@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Xem trước lịch giáo viên (nhập file)')

@push('styles')
<style>
    tr.row-skip-save > td {
        background: #fff3cd !important;
    }
    tr.row-will-update > td {
        background: #cce5ff !important;
    }
</style>
@endpush

@section('content')
    @php
        $okCount = (int) ($preview['meta']['gv_ok_count'] ?? count($rows));
        $conflictCount = (int) ($preview['meta']['gv_conflict_count'] ?? 0);
        $skipCount = (int) ($preview['meta']['gv_skip_count'] ?? 0);
        $khoaHocList = $preview['meta']['khoa_hoc_list'] ?? [];
        $ngayList = $preview['meta']['ngay_list'] ?? [];
        $updateMode = ! empty($preview['meta']['update_mode']);
        $updateCount = (int) ($preview['meta']['gv_update_count'] ?? 0);
    @endphp

    <div class="card card-panel">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Xem trước Lịch giáo viên — Nhập từ file</span>
            <a href="{{ route('pmgplx.lich.nhap-file.preview') }}" class="btn btn-sm btn-outline-secondary">← Quay lại Excel</a>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <div><strong>File:</strong> {{ $preview['file_name'] ?? '' }}</div>
                <div><strong>Khóa học:</strong> {{ implode(', ', $khoaHocList) }}</div>
                <div><strong>Môn học:</strong> {{ $preview['meta']['ten_mon_hoc'] ?? 'Thực hành lái xe' }}</div>
                <div><strong>Ngày:</strong> {{ implode(', ', $ngayList) }}</div>
                <div>
                    <strong>Tổng buổi:</strong> {{ count($rows) }} —
                    sẽ lưu <strong class="text-success">{{ $okCount }}</strong>,
                    bỏ qua <strong class="text-danger">{{ $conflictCount }}</strong> trùng
                    @if ($updateMode && $updateCount > 0)
                        , <strong class="text-primary">{{ $updateCount }}</strong> cập nhật
                    @endif
                    @if ($skipCount > 0)
                        , <strong class="text-warning">{{ $skipCount }}</strong> Bổ Sung
                    @endif
                </div>
            </div>

            <div class="alert alert-info">
                Không có địa điểm. Có thể sửa trực tiếp trên từng dòng trước khi tiếp tục.
                Thời gian nhập theo <strong>24 giờ</strong> (ví dụ <code>05:59</code>, <code>13:59</code>).
            </div>

            @if ($skipCount > 0)
                <div class="alert alert-warning">
                    Dòng nền vàng có <strong>Bổ Sung</strong> trong Nội dung – Chi tiết — sẽ không lưu lịch giáo viên.
                </div>
            @endif

            @if ($conflictCount > 0 && ! $updateMode)
                <div class="alert alert-warning">
                    Dòng nền đỏ đã trùng lịch giáo viên. Khi lưu sẽ bỏ qua các dòng này (trừ khi bật <strong>Chế độ cập nhật</strong>).
                </div>
            @endif

            @if ($updateMode)
                <div class="alert alert-primary">
                    Đang bật <strong>Chế độ cập nhật</strong> — dòng trùng lịch GV sẽ được <strong>cập nhật</strong> thay vì bỏ qua.
                </div>
            @endif

            <form method="POST" action="{{ route('pmgplx.lich.nhap-file.to-xe') }}" id="formNhapFileGv">
                @csrf
                <div class="custom-control custom-checkbox mb-3">
                    <input type="checkbox" class="custom-control-input" id="cheDoCapNhat" name="che_do_cap_nhat" value="1"
                           @checked(old('che_do_cap_nhat', $updateMode))>
                    <label class="custom-control-label" for="cheDoCapNhat">
                        <strong>Chế độ cập nhật</strong> — dòng trùng lịch GV sẽ được <strong>cập nhật</strong> thay vì bỏ qua / thêm mới
                    </label>
                </div>
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>STT</th>
                                <th>Mã KH</th>
                                <th>Mã GV</th>
                                <th>Tên giáo viên</th>
                                <th>Môn học</th>
                                <th class="col-noi-dung-chi-tiet">Nội dung – Chi tiết</th>
                                <th style="min-width: 13rem;">TG bắt đầu</th>
                                <th style="min-width: 13rem;">TG kết thúc</th>
                                <th style="min-width: 9rem;">Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $i => $row)
                                @php
                                    $skipSave = ! empty($row['skip_save']);
                                    $selectedMaMonHoc = $row['MaMonHoc'] ?? $defaultMaMonHoc ?? null;
                                    $rowClass = $skipSave
                                        ? 'row-skip-save'
                                        : (! empty($row['will_update'])
                                            ? 'row-will-update'
                                            : (! empty($row['conflict']) ? 'table-danger' : ''));
                                    $hasGvTrung = ! $skipSave && (! empty($row['conflict']) || ! empty($row['will_update']));
                                @endphp
                                <tr class="{{ $rowClass }}" data-row-index="{{ $i }}" @if ($hasGvTrung) data-gv-trung="1" @endif>
                                    <td>{{ $i + 1 }}</td>
                                    <td>
                                        <input type="hidden" name="rows[{{ $i }}][source_key]" value="{{ $row['source_key'] ?? '' }}">
                                        <input type="text" name="rows[{{ $i }}][MaKH]" class="form-control form-control-sm"
                                               value="{{ $row['MaKH'] }}" readonly required>
                                    </td>
                                    <td>
                                        <input type="text" name="rows[{{ $i }}][MaGV]" class="form-control form-control-sm"
                                               value="{{ $row['MaGV'] }}" readonly required>
                                    </td>
                                    <td>
                                        <input type="text" name="rows[{{ $i }}][TenGV]" class="form-control form-control-sm"
                                               value="{{ $row['TenGV'] }}" readonly required>
                                    </td>
                                    <td>
                                        @if ($skipSave)
                                            <input type="hidden" name="rows[{{ $i }}][MaMonHoc]" value="{{ $selectedMaMonHoc }}">
                                            <span class="text-muted small">—</span>
                                        @else
                                        <select name="rows[{{ $i }}][MaMonHoc]" class="form-control form-control-sm" required>
                                            <option value="">-- Chọn môn học --</option>
                                            @foreach ($monHocs as $mh)
                                                @php
                                                    $maMh = (int) $mh->MaMH;
                                                    $tenMh = trim((string) $mh->TenMH);
                                                    $isSelected = $selectedMaMonHoc !== null && (int) $selectedMaMonHoc === $maMh;
                                                    if (! $isSelected && $selectedMaMonHoc === null) {
                                                        $isSelected = mb_stripos($tenMh, 'Thực hành lái xe') !== false;
                                                    }
                                                @endphp
                                                <option value="{{ $maMh }}" @selected($isSelected)>
                                                    {{ $tenMh }} ({{ $maMh }})
                                                </option>
                                            @endforeach
                                        </select>
                                        @endif
                                    </td>
                                    <td class="col-noi-dung-chi-tiet">
                                        @include('PMGPLX.lich._noi-dung-chi-tiet', [
                                            'noiDung' => $row['noi_dung'] ?? '',
                                            'chiTiet' => $row['chi_tiet'] ?? '',
                                        ])
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
                                            <span class="text-primary font-weight-bold ghi-chu-gv">Sẽ cập nhật</span>
                                        @elseif (! empty($row['conflict']))
                                            <span class="text-danger font-weight-bold ghi-chu-gv">Đã thêm vào lịch</span>
                                        @else
                                            <span class="text-success">Sẽ lưu mới</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <button type="submit" class="btn btn-navy btn-lg">Tiếp tục — Xem trước lịch xe tập lái</button>
                <a href="{{ route('pmgplx.lich.nhap-file.preview') }}" class="btn btn-outline-secondary btn-lg ml-2">← Quay lại Excel</a>
                <a href="{{ route('pmgplx.lich.nhap-file.cancel') }}" class="btn btn-outline-danger btn-lg ml-2">Hủy</a>
            </form>
        </div>
    </div>
@endsection

@include('PMGPLX.lich._nhap-file-chunk-submit')

@push('scripts')
<script>
    function readGvRowsFromForm($form, start, end) {
        var rows = [];
        $form.find('tbody tr[data-row-index]').each(function () {
            var i = parseInt($(this).data('rowIndex'), 10);
            if (isNaN(i) || i < start || i >= end) {
                return;
            }
            rows.push({
                source_key: $form.find('[name="rows[' + i + '][source_key]"]').val() || '',
                MaKH: $form.find('[name="rows[' + i + '][MaKH]"]').val() || '',
                MaGV: $form.find('[name="rows[' + i + '][MaGV]"]').val() || '',
                TenGV: $form.find('[name="rows[' + i + '][TenGV]"]').val() || '',
                MaMonHoc: $form.find('[name="rows[' + i + '][MaMonHoc]"]').val() || '',
                NgayBD: $form.find('[name="rows[' + i + '][NgayBD]"]').val() || '',
                NgayKT: $form.find('[name="rows[' + i + '][NgayKT]"]').val() || ''
            });
        });
        return rows;
    }

    lichNhapFileChunkSubmit({
        $form: $('#formNhapFileGv'),
        totalRows: {{ count($rows) }},
        readRows: function (start, end) {
            return readGvRowsFromForm($('#formNhapFileGv'), start, end);
        },
        cheDoCapNhatSelector: '#cheDoCapNhat',
        progressLabel: 'Đang lưu lịch giáo viên'
    });

    function applyCheDoCapNhatUi(on) {
        $('tr[data-gv-trung="1"]').each(function () {
            var $tr = $(this);
            var $note = $tr.find('.ghi-chu-gv');
            if (on) {
                $tr.removeClass('table-danger').addClass('row-will-update');
                $note.removeClass('text-danger').addClass('text-primary').text('Sẽ cập nhật');
            } else {
                $tr.removeClass('row-will-update').addClass('table-danger');
                $note.removeClass('text-primary').addClass('text-danger').text('Đã thêm vào lịch');
            }
        });
    }

    $('#cheDoCapNhat').on('change', function () {
        applyCheDoCapNhatUi($(this).is(':checked'));
    });

    if ($('#cheDoCapNhat').is(':checked')) {
        applyCheDoCapNhatUi(true);
    }
</script>
@endpush
