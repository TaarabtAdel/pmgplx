@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Xem trước — Nhập SDT / Sale')

@section('content')
    @php
        /** @var \App\Support\DaoTao\HocVienSdtSaleTuFileUpdater $updater */
        $meta = $preview['meta'] ?? [];
        $sheetMeta = $activeSheet['meta'] ?? [];
    @endphp

    <style>
        .hv-sdt-sale-preview { font-size: 0.8125rem; }
        .row-cap-nhat { background: #fff8e6; }
        .row-khong-doi { background: #f8f9fa; }
        .row-loi { background: #fde8e8; }
    </style>

    <div class="card card-panel mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <span>Xem trước — Cập nhật SDT / Sale (MANHLINH)</span>
            <a href="{{ route('daotao.pdt.cong-cu-nhap.nhap-file-sdt-sale-hoc-vien.cancel') }}" class="btn btn-sm btn-outline-secondary">← Chọn file khác</a>
        </div>
        <div class="card-body small">
            <div><strong>File:</strong> {{ $preview['file_name'] ?? '' }}</div>
            <div>
                <strong>Tổng:</strong>
                {{ (int) ($meta['sheet_count'] ?? 0) }} sheet,
                {{ number_format((int) ($meta['row_count'] ?? 0)) }} dòng có dữ liệu
                · Sẽ cập nhật: <strong class="text-success">{{ number_format((int) ($meta['will_update'] ?? 0)) }}</strong>
                · Không đổi: {{ number_format((int) ($meta['unchanged'] ?? 0)) }}
                · Không khớp CCCD: {{ number_format((int) ($meta['not_found'] ?? 0)) }}
                @if ((int) ($meta['ambiguous'] ?? 0) > 0)
                    · CCCD nhiều HV: {{ number_format((int) $meta['ambiguous']) }}
                @endif
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs mb-3">
        @foreach ($preview['sheets'] as $i => $sheet)
            <li class="nav-item">
                <a class="nav-link {{ $sheetIndex === $i ? 'active font-weight-bold' : '' }}"
                   href="{{ route('daotao.pdt.cong-cu-nhap.nhap-file-sdt-sale-hoc-vien.preview', ['sheet' => $i]) }}">
                    {{ $sheet['sheet_name'] ?? ('Sheet '.($i + 1)) }}
                    <span class="badge badge-secondary ml-1">{{ number_format((int) ($sheet['meta']['row_count'] ?? 0)) }}</span>
                </a>
            </li>
        @endforeach
    </ul>

    <div class="card card-panel mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <span>Sheet: <strong>{{ $activeSheet['sheet_name'] ?? '' }}</strong></span>
            <span class="text-muted small">
                Xem {{ count($activeSheet['preview'] ?? []) }}/{{ number_format((int) ($sheetMeta['row_count'] ?? 0)) }} dòng đầu
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0 hv-sdt-sale-preview">
                    <thead class="thead-light">
                        <tr>
                            <th>Dòng Excel</th>
                            <th>CCCD</th>
                            <th>SDT (file)</th>
                            <th>Sale (file)</th>
                            <th>Mã ĐK</th>
                            <th>Họ tên</th>
                            <th>SDT hiện tại</th>
                            <th>Sale hiện tại</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($activeSheet['preview'] ?? [] as $row)
                            @php
                                $trClass = match ($row['trang_thai'] ?? '') {
                                    \App\Support\DaoTao\HocVienSdtSaleTuFileUpdater::TRANG_CAP_NHAT => 'row-cap-nhat',
                                    \App\Support\DaoTao\HocVienSdtSaleTuFileUpdater::TRANG_KHONG_DOI => 'row-khong-doi',
                                    default => 'row-loi',
                                };
                            @endphp
                            <tr class="{{ $trClass }}">
                                <td>{{ $row['excel_row'] ?? '' }}</td>
                                <td>{{ $row['cccd'] ?? '' }}</td>
                                <td>{{ $row['sdt'] ?? '—' }}</td>
                                <td>{{ $row['sale'] ?? '—' }}</td>
                                <td>{{ $row['ma_dk'] ?: '—' }}</td>
                                <td>{{ $row['ho_ten'] ?: '—' }}</td>
                                <td>{{ $row['sdt_cu'] ?? '—' }}</td>
                                <td>{{ $row['sale_cu'] ?? '—' }}</td>
                                <td>{{ $row['trang_thai_nhan'] ?? '' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">Không có dòng trên sheet này.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <form id="formXacNhanSdtSale" method="POST" action="{{ route('daotao.pdt.cong-cu-nhap.nhap-file-sdt-sale-hoc-vien.confirm') }}">
                @csrf
                <input type="hidden" name="sheet_total" value="{{ count($preview['sheets'] ?? []) }}">
                <button type="submit" class="btn btn-success btn-lg"
                        data-will-update="{{ (int) ($meta['will_update'] ?? 0) }}"
                        data-sheet-count="{{ count($preview['sheets'] ?? []) }}">
                    Xác nhận cập nhật ({{ number_format((int) ($meta['will_update'] ?? 0)) }} học viên)
                </button>
            </form>
            <div id="hvSdtSaleChunkProgress" class="alert alert-info mt-3 mb-0 d-none"></div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function ($) {
    var $form = $('#formXacNhanSdtSale');
    var sheetTotal = parseInt($form.find('[name=sheet_total]').val(), 10) || 0;
    var $progress = $('#hvSdtSaleChunkProgress');
    var csrf = $form.find('input[name=_token]').val();

    $form.on('submit', function (e) {
        e.preventDefault();
        var will = parseInt($form.find('button[type=submit]').data('will-update'), 10) || 0;
        if (!confirm('Cập nhật SDT/Sale cho tối đa ' + will + ' học viên vào HocVienThongTinPhu (MANHLINH)?\n\nXử lý lần lượt từng sheet (' + sheetTotal + ').')) {
            return;
        }
        var $btn = $form.find('button[type=submit]');
        $btn.prop('disabled', true);
        $progress.removeClass('d-none alert-danger').addClass('alert-info');

        function processSheet(idx) {
            var isLast = idx >= sheetTotal - 1;
            $progress.text('Đang cập nhật sheet ' + (idx + 1) + '/' + sheetTotal + '…');
            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: {
                    _token: csrf,
                    sheet_index: idx,
                    sheet_total: sheetTotal,
                    chunk_finalize: isLast ? 1 : 0
                },
                dataType: 'json'
            }).done(function (res) {
                if (!res || !res.ok) {
                    $btn.prop('disabled', false);
                    $progress.removeClass('alert-info').addClass('alert-danger').text((res && res.message) ? res.message : 'Lỗi cập nhật.');
                    return;
                }
                if (res.redirect) {
                    $progress.text('Hoàn tất — chuyển trang…');
                    window.location.href = res.redirect;
                    return;
                }
                processSheet(idx + 1);
            }).fail(function (xhr) {
                $btn.prop('disabled', false);
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Lỗi cập nhật.';
                $progress.removeClass('alert-info').addClass('alert-danger').text(msg);
            });
        }

        processSheet(0);
    });
})(jQuery);
</script>
@endpush
