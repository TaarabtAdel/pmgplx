@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Xem trước nhập lịch từ file')

@push('styles')
<style>
    .lich-import-preview td.cell-phuc-tap { background: #00e5e5 !important; }
    .lich-import-preview td.cell-ban-dem { background: #c77dff !important; color: #111; }
    .lich-import-preview td.cell-co-tai { background: #ff4d4d !important; color: #fff; font-weight: 600; }
    .lich-import-preview td.cell-doc-qc { background: #00c853 !important; color: #fff; font-weight: 600; }
    .lich-import-preview td.cell-tu-dong { background: #9eb6c7 !important; }
    .lich-import-preview td.cell-cao-toc { background: #ffe566 !important; }
    .lich-import-preview tr.row-off > td { background: #fff3cd !important; }
    .lich-import-preview tr.row-off td.cell-phuc-tap,
    .lich-import-preview tr.row-off td.cell-ban-dem,
    .lich-import-preview tr.row-off td.cell-co-tai,
    .lich-import-preview tr.row-off td.cell-doc-qc,
    .lich-import-preview tr.row-off td.cell-tu-dong,
    .lich-import-preview tr.row-off td.cell-cao-toc {
        background: #fff3cd !important;
        color: inherit;
        font-weight: inherit;
    }
</style>
@endpush

@section('content')
    @php
        $okCount = (int) ($preview['meta']['ok_count'] ?? 0);
        $offCount = (int) ($preview['meta']['off_count'] ?? 0);
        $teacherCount = (int) ($preview['meta']['teacher_count'] ?? 0);
        $cellClass = fn (?string $text) => \App\Support\PMGPLX\LichExcelCellStyle::classFor($text);
    @endphp

    <div class="card card-panel">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Xem trước — Nhập lịch từ file</span>
            <a href="{{ route('pmgplx.lich.nhap-file.cancel') }}" class="btn btn-sm btn-outline-secondary">← Quay lại chọn file</a>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <div><strong>File:</strong> {{ $preview['file_name'] ?? '' }}</div>
                <div><strong>Số giáo viên:</strong> {{ $teacherCount }}</div>
                <div>
                    <strong>Tổng buổi:</strong>
                    sẽ lưu <strong class="text-success">{{ $okCount }}</strong>,
                    bỏ qua <strong class="text-warning">{{ $offCount }}</strong> ngày nghỉ (ô nội dung trống)
                </div>
            </div>

            <div class="alert alert-warning mb-3">
                Dòng nền vàng là <strong>ngày nghỉ</strong> (cột nội dung trống) — sẽ <strong>không lưu</strong> vào phần mềm.
            </div>

            <div class="mb-3 small">
                <span class="badge mr-1" style="background:#00e5e5;color:#111;">PHỨC TẠP</span>
                <span class="badge mr-1" style="background:#c77dff;color:#111;">BAN ĐÊM</span>
                <span class="badge mr-1" style="background:#ff4d4d;">CÓ TẢI</span>
                <span class="badge mr-1" style="background:#00c853;">DỐC, QC</span>
                <span class="badge mr-1" style="background:#9eb6c7;color:#111;">TỰ ĐỘNG</span>
                <span class="badge mr-1" style="background:#ffe566;color:#111;">Cao tốc</span>
                <span class="badge mr-1" style="background:#fff3cd;color:#111;border:1px solid #e0c56a;">Ngày nghỉ</span>
            </div>

            @foreach ($preview['teachers'] as $gv)
                <div class="mb-4">
                    <h5 class="section-title mb-2">
                        Giáo viên {{ $gv['index'] }}:
                        {{ $gv['ten_gv'] !== '' ? $gv['ten_gv'] : '—' }}
                        @if (($gv['ma_gv'] ?? '') !== '')
                            ({{ $gv['ma_gv'] }})
                        @endif
                        @if (($gv['bien_so_xe'] ?? '') !== '')
                            — {{ $gv['bien_so_xe'] }}
                        @endif
                    </h5>
                    <div class="mb-2 small">
                        <span class="mr-3"><strong>Mã GV:</strong> {{ $gv['ma_gv'] !== '' ? $gv['ma_gv'] : '—' }}</span>
                        <span class="mr-3"><strong>Mã khóa học:</strong> {{ $gv['ma_kh'] !== '' ? $gv['ma_kh'] : '—' }}</span>
                        <span class="text-muted">Sẽ lưu {{ $gv['ok_count'] }} buổi · bỏ qua {{ $gv['off_count'] }} ngày nghỉ</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover mb-0 lich-import-preview">
                            <thead class="thead-light">
                                <tr>
                                    <th>STT</th>
                                    <th>Thời gian</th>
                                    <th>Nội dung</th>
                                    <th>Chi tiết</th>
                                    <th>Bắt đầu</th>
                                    <th>Kết thúc</th>
                                    <th>Ghi chú</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($gv['rows'] as $i => $row)
                                    <tr class="{{ !empty($row['is_off']) ? 'row-off' : '' }}">
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ $row['ngay'] }}</td>
                                        <td class="{{ $cellClass($row['noi_dung']) }}">{{ $row['noi_dung'] }}</td>
                                        <td class="{{ $cellClass($row['chi_tiet']) }}">{{ $row['chi_tiet'] }}</td>
                                        <td>{{ $row['bat_dau'] }}</td>
                                        <td>{{ $row['ket_thuc'] }}</td>
                                        <td>
                                            @if (!empty($row['is_off']))
                                                <span class="text-warning font-weight-bold">Ngày nghỉ</span>
                                            @else
                                                <span class="text-success">Sẽ lưu</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-3">Không có dòng dữ liệu</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach

            <form method="POST" action="{{ route('pmgplx.lich.nhap-file.to-gv') }}" class="mt-2">
                @csrf
                <button type="submit" class="btn btn-navy btn-lg">Xem trước lưu vào phần mềm</button>
                <a href="{{ route('pmgplx.lich.nhap-file.cancel') }}" class="btn btn-outline-secondary btn-lg ml-2">Hủy</a>
            </form>
        </div>
    </div>
@endsection
