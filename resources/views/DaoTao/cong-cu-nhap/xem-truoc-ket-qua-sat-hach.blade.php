@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Xem trước — Nhập kết quả sát hạch')

@section('content')
    @php
        $meta = $preview['meta'] ?? [];
        $rows = $preview['rows'] ?? [];
    @endphp

    <style>
        .kq-sat-hach-table { font-size: 0.8125rem; }
        .kq-sat-hach-table td, .kq-sat-hach-table th { vertical-align: middle; }
        .row-will-update { background: #fff8e6; }
        .row-skip { background: #f8f9fa; }
        .row-error { background: #fde8e8; }
        .mon-cap-nhat { color: #856404; font-weight: 600; }
    </style>

    <div class="card card-panel mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <span>Xem trước — Nhập kết quả sát hạch</span>
            <a href="{{ route('daotao.pdt.cong-cu-nhap.nhap-ket-qua-sat-hach.cancel') }}" class="btn btn-sm btn-outline-secondary">← Chọn file khác</a>
        </div>
        <div class="card-body">
            <div><strong>File:</strong> {{ $preview['file_name'] ?? '' }}</div>
            <div><strong>Sheet:</strong> {{ $preview['sheet_name'] ?? '' }}</div>
            <div>
                <strong>Dòng file:</strong> {{ number_format((int) ($meta['record_count'] ?? count($rows))) }}
                · <strong>Khớp PMGPLX:</strong> {{ number_format((int) ($meta['pmglx_matched'] ?? 0)) }}
                · <strong>Đã có thu tiền:</strong> {{ number_format((int) ($meta['matched'] ?? 0)) }}
                · <strong>Sẽ thêm thu tiền:</strong> {{ number_format((int) ($meta['will_add_thu_tien'] ?? 0)) }}
                · <strong>Sẽ cập nhật KQ:</strong> {{ number_format((int) ($meta['will_update'] ?? 0)) }}
                · <strong>Lượt môn:</strong> {{ number_format((int) ($meta['mon_cap_nhat'] ?? 0)) }}
            </div>

            @if (session('success'))
                <div class="alert alert-success small py-2 mt-2 mb-0">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger small py-2 mt-2 mb-0">{{ session('error') }}</div>
            @endif
        </div>
    </div>

    <div class="card card-panel mb-3">
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 70vh;">
                <table class="table table-sm table-bordered mb-0 kq-sat-hach-table">
                    <thead class="thead-light sticky-top">
                        <tr>
                            <th>#</th>
                            <th>Họ tên</th>
                            <th>Số ĐD</th>
                            <th>Hạng</th>
                            <th>Mã ĐK</th>
                            <th>Lý thuyết</th>
                            <th>Sa hình</th>
                            <th>Đường</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            @php
                                $trClass = match ($row['status'] ?? '') {
                                    'matched', 'will_add_thu_tien' => ($row['will_apply'] ?? false) ? 'row-will-update' : 'row-skip',
                                    default => 'row-error',
                                };
                                $fmtMon = static function (array $mon): string {
                                    if (($mon['kq'] ?? null) === null) {
                                        return '—';
                                    }
                                    $s = ($mon['kq_raw'] ?? '').' (l'.($mon['lan'] ?? 1).')';
                                    if (($mon['action'] ?? '') === 'cap_nhat') {
                                        return '<span class="mon-cap-nhat">'.$s.'</span><br><small>'.$mon['ghi_chu'].'</small>';
                                    }

                                    return $s.'<br><small class="text-muted">'.$mon['ghi_chu'].'</small>';
                                };
                            @endphp
                            <tr class="{{ $trClass }}">
                                <td>{{ $row['stt'] ?: $row['row'] }}</td>
                                <td>{{ $row['ho_ten'] }}</td>
                                <td><code>{{ $row['so_dinh_danh'] }}</code></td>
                                <td>
                                    {{ $row['hang_file'] }}
                                    @if (! empty($row['hang_file_label']))
                                        <br><small class="text-muted">{{ $row['hang_file_label'] }}</small>
                                    @endif
                                    @if (! empty($row['hang_pmglx']) || ! empty($row['hang_dao_tao']))
                                        <br><small class="text-muted">PM: {{ $row['hang_pmglx'] ?? '—' }} / {{ $row['hang_dao_tao'] ?? '—' }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if (! empty($row['ma_dk']))
                                        <a href="{{ route('daotao.pdt.bc.thu-tien-hoc-vien', ['tu_khoa' => $row['ma_dk']]) }}">{{ $row['ma_dk'] }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{!! $fmtMon($row['mon']['ly_thuyet'] ?? []) !!}</td>
                                <td>{!! $fmtMon($row['mon']['sa_hinh'] ?? []) !!}</td>
                                <td>{!! $fmtMon($row['mon']['duong'] ?? []) !!}</td>
                                <td class="small">
                                    @if (($row['status'] ?? '') === 'matched')
                                        @if ($row['will_apply'] ?? false)
                                            <span class="text-warning">Sẽ cập nhật KQ thi</span>
                                        @else
                                            <span class="text-muted">Khớp · không đổi</span>
                                        @endif
                                    @elseif (($row['status'] ?? '') === 'will_add_thu_tien')
                                        @if ($row['will_apply'] ?? false)
                                            <span class="text-warning">Khớp PMGPLX · thêm thu tiền + KQ</span>
                                        @else
                                            <span class="text-info">Khớp PMGPLX · thêm thu tiền</span>
                                        @endif
                                        @if (! empty($row['message']))
                                            <br><small>{{ $row['message'] }}</small>
                                        @endif
                                    @else
                                        <span class="text-danger">{{ $row['message'] ?? 'Lỗi' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap align-items-center">
        <form method="POST" action="{{ route('daotao.pdt.cong-cu-nhap.nhap-ket-qua-sat-hach.confirm') }}" class="mr-2 mb-2"
              onsubmit="return confirm('Cập nhật kết quả thi vào Quản lý thu tiền học viên cho các dòng được đánh dấu?');">
            @csrf
            <button type="submit" class="btn btn-navy btn-lg" @if((int)($meta['will_update'] ?? 0) === 0) disabled @endif>
                Xác nhận cập nhật ({{ number_format((int) ($meta['will_update'] ?? 0)) }} HV)
            </button>
        </form>
        <a href="{{ route('daotao.pdt.cong-cu-nhap.nhap-ket-qua-sat-hach.cancel') }}" class="btn btn-outline-secondary btn-lg mb-2">Hủy</a>
    </div>
@endsection
