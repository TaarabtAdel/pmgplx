@php
    use App\Support\QldtOto\LichHocLyThuyetThucHanhLichNgay;
    use App\Support\QldtOto\LichHocThucHanhGiaoVienLayout;

    $laB01 = ($hang ?? '') === LichHocLyThuyetThucHanhLichNgay::HANG_B_TU_DONG;
    $gvXem = ($danhSachGv ?? [])[0] ?? null;
    $dongXem = $gvXem
        ? LichHocThucHanhGiaoVienLayout::dongRaFile($gvXem['buoi'] ?? [], $hang ?? LichHocLyThuyetThucHanhLichNgay::HANG_C)
        : [];
@endphp

@if ($gvXem !== null && $dongXem !== [])
    <h6 class="small font-weight-bold mb-2 mt-3">
        Xem trước lịch TH trên file
        @if ($laB01)
            (mẫu <strong>THUC HANH B11</strong> — hạng <strong>B số tự động</strong>)
        @else
            (mẫu hạng <strong>C</strong>)
        @endif
        — GV: {{ $gvXem['ten_gv'] }}
        <span class="text-muted font-weight-normal">(các GV khác cùng cấu trúc)</span>
    </h6>
    <div class="table-responsive mb-3" style="max-height: 22rem; overflow-y: auto;">
        <table class="table table-sm table-bordered mb-0" style="font-size: 0.75rem;">
            <thead class="thead-light sticky-top">
                <tr>
                    <th>Thứ</th>
                    <th>Ngày</th>
                    @if ($laB01)
                        <th>C · Số giờ</th>
                        <th>D · Môn học</th>
                        <th>E · Địa điểm</th>
                        <th>F · Ghi chú</th>
                        <th>G</th>
                        <th>H</th>
                    @else
                        <th>D · Số giờ</th>
                        <th>E · Nội dung</th>
                        <th>F · Địa điểm</th>
                        <th>G · Ghi chú</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($dongXem as $d)
                    <tr>
                        <td>{{ $d['thu'] }}</td>
                        <td class="text-nowrap">{{ $d['ngay']->format('d/m/Y') }}</td>
                        @if ($laB01)
                            <td class="text-right">{{ $d['so_gio'] !== null && $d['so_gio'] > 0 ? $d['so_gio'] : '' }}</td>
                            <td style="white-space: pre-wrap;">{{ $d['mon_hoc'] }}</td>
                            <td>{{ $d['dia_diem'] }}</td>
                            <td style="white-space: pre-wrap;">{{ $d['ghi_chu_f'] }}</td>
                            <td>{{ $d['cot_g'] }}</td>
                            <td>{{ $d['cot_h'] }}</td>
                        @else
                            <td class="text-right">{{ $d['so_gio'] !== null && $d['so_gio'] > 0 ? $d['so_gio'] : '' }}</td>
                            <td>{{ $d['mon_hoc'] }}</td>
                            <td>{{ $d['dia_diem'] }}</td>
                            <td>{{ $d['ghi_chu_f'] }}</td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif