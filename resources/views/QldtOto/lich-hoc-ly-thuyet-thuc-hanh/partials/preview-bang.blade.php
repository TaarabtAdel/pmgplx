@php
    $tieuDe = 'LỊCH HỌC LÝ THUYẾT, THỰC HÀNH KHÓA '.mb_strtoupper($tenKhoaDoc ?: '…');
@endphp

<div class="card border mb-3 lich-lt-th-preview">
    <div class="card-header py-2 small font-weight-bold text-center">
        {{ $tieuDe }}
        <span class="d-block text-muted font-weight-normal">Xem trước mẫu {{ $nhanHang[$hang] ?? $hang }} (theo file Excel)</span>
    </div>
    <div class="card-body p-2">
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0 lich-lt-th-preview-table">
                <thead>
                    <tr class="text-center align-middle">
                        <th rowspan="2" style="width: 2.5rem">STT</th>
                        <th rowspan="2">Nội dung bài học</th>
                        <th rowspan="2" style="width: 3.5rem">Số giờ<br>(h)</th>
                        <th colspan="2">Thời gian học</th>
                        <th rowspan="2" style="min-width: 8rem">Hình thức học</th>
                        <th rowspan="2" style="min-width: 7rem">Địa điểm đào tạo</th>
                        <th rowspan="2" style="min-width: 6rem">Ghi chú</th>
                        <th rowspan="2" style="min-width: 11rem">Gợi ý · nguồn</th>
                    </tr>
                    <tr class="text-center small">
                        <th style="width: 5.5rem">Từ ngày</th>
                        <th style="width: 5.5rem">Đến ngày</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($previewDong as $dong)
                        @php
                            $anOt = (bool) ($dong['gop_an_ot'] ?? false);
                            $rowspan = (int) ($dong['gop_rowspan'] ?? 0);
                        @endphp
                        <tr>
                            <td class="text-center align-middle">{{ $dong['stt'] }}</td>
                            <td class="small text-left align-middle">{{ $dong['noi_dung'] }}</td>
                            <td class="text-center align-middle">{{ $dong['so_gio'] !== '' ? $dong['so_gio'] : '—' }}</td>
                            @unless ($anOt)
                                @if ($rowspan > 1)
                                    <td rowspan="{{ $rowspan }}" class="text-center text-nowrap small font-weight-bold align-middle">{{ $dong['tu_ngay'] ?: '—' }}</td>
                                    <td rowspan="{{ $rowspan }}" class="text-center text-nowrap small font-weight-bold align-middle">{{ $dong['den_ngay'] ?: '—' }}</td>
                                    <td rowspan="{{ $rowspan }}" class="small align-middle">{{ $dong['hinh_thuc'] ?: '—' }}</td>
                                    <td rowspan="{{ $rowspan }}" class="small align-middle">{{ $dong['dia_diem'] ?: '—' }}</td>
                                    <td rowspan="{{ $rowspan }}" class="small align-middle">{{ $dong['ghi_chu'] ?: '—' }}</td>
                                    <td rowspan="{{ $rowspan }}" class="small text-muted text-left align-middle">{!! $dong['goi_y_nguon'] !!}</td>
                                @else
                                    <td class="text-center text-nowrap small font-weight-bold">{{ $dong['tu_ngay'] ?: '—' }}</td>
                                    <td class="text-center text-nowrap small font-weight-bold">{{ $dong['den_ngay'] ?: '—' }}</td>
                                    <td class="small">{{ $dong['hinh_thuc'] ?: '—' }}</td>
                                    <td class="small">{{ $dong['dia_diem'] ?: '—' }}</td>
                                    <td class="small">{{ $dong['ghi_chu'] ?: '—' }}</td>
                                    <td class="small text-muted text-left">{!! $dong['goi_y_nguon'] !!}</td>
                                @endif
                            @endunless
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-3">Chưa có dữ liệu xem trước.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="text-muted small mb-0 mt-2">
            Sửa ngày ở bảng «Điều chỉnh mốc» phía trên rồi chọn lại khoá hoặc xuất Excel.
        </p>
    </div>
</div>

@push('styles')
<style>
    .lich-lt-th-preview-table { font-size: 0.72rem; }
    .lich-lt-th-preview-table thead th {
        background: #f8f9fa;
        vertical-align: middle !important;
    }
    .lich-lt-th-preview-table td, .lich-lt-th-preview-table th {
        padding: 0.25rem 0.35rem;
    }
</style>
@endpush
