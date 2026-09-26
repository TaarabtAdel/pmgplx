@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Nhập XML xuất biên bản tổng hợp')

@section('content')
    <div class="card card-panel mb-3">
        <div class="card-header">Nhập XML kết quả sát hạch</div>
        <div class="card-body">
            <div class="alert alert-info">
                Upload file XML <code>&lt;SAT_HACH&gt;</code>. Hệ thống lưu từng thí sinh vào bảng <code>SatHachBienBan</code> (DB MANHLINH).
                Sau đó xuất <strong>từng biên bản DOCX</strong> — mỗi lần một thí sinh, nhẹ hơn file gộp hàng trăm trang.
            </div>

            <form method="POST" action="{{ route('daotao.pdt.cong-cu-nhap.nhap-xml-bien-ban.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-row align-items-end">
                    <div class="form-group col-md-6 mb-2">
                        <label for="file">Chọn file XML</label>
                        <input type="file" name="file" id="file" class="form-control-file" accept=".xml,text/xml" required>
                    </div>
                    <div class="form-group col-md-3 mb-2">
                        <button type="submit" class="btn btn-navy">Nhập vào DB</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-panel">
        <div class="card-header">Danh sách thí sinh đã nhập</div>
        <div class="card-body">
            <form method="GET" action="{{ route('daotao.pdt.cong-cu-nhap.nhap-xml-bien-ban') }}" class="mb-3">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-4 mb-2">
                        <label class="small text-muted mb-1" for="ma_ky_sh">Kỳ sát hạch</label>
                        <select name="ma_ky_sh" id="ma_ky_sh" class="form-control form-control-sm" data-placeholder="— Tất cả —">
                            <option value=""></option>
                            @foreach ($kyOptions as $ky)
                                <option value="{{ $ky }}" @selected(($filters['ma_ky_sh'] ?? '') === $ky)>{{ $ky }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4 mb-2">
                        <label class="small text-muted mb-1" for="tu_khoa">Tìm (tên / SBD / mã ĐK / CCCD)</label>
                        <input type="text" name="tu_khoa" id="tu_khoa" class="form-control form-control-sm"
                               value="{{ $filters['tu_khoa'] ?? '' }}">
                    </div>
                    <div class="form-group col-md-4 mb-2">
                        <button type="submit" class="btn btn-sm btn-navy mr-1">Lọc</button>
                        <a href="{{ route('daotao.pdt.cong-cu-nhap.nhap-xml-bien-ban') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                        <button type="button" id="btn-xuat-tong" class="btn btn-sm btn-outline-success ml-1"
                                @disabled(($filters['ma_ky_sh'] ?? '') === '')>
                            Xuất tổng Word
                        </button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>STT</th>
                            <th>Kỳ SH</th>
                            <th>SBD</th>
                            <th>Họ tên</th>
                            <th>Mã ĐK</th>
                            <th>Hạng</th>
                            <th>Ngày sinh</th>
                            <th>Ảnh</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $row)
                            <tr>
                                <td>{{ $row->SoTT ?: '—' }}</td>
                                <td><code>{{ $row->MaKySH ?: '—' }}</code></td>
                                <td>{{ $row->SoBaoDanh ?: '—' }}</td>
                                <td>{{ $row->HoVaTen ?: '—' }}</td>
                                <td><code>{{ $row->MaDK }}</code></td>
                                <td>{{ $row->HangGPLX ?: '—' }}</td>
                                <td>{{ $row->NgaySinh ?: '—' }}</td>
                                <td>
                                    @if ((int) $row->CoAnh === 1)
                                        <span class="badge badge-success">Có</span>
                                    @else
                                        <span class="badge badge-secondary">Không</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    <a href="{{ route('daotao.pdt.cong-cu-nhap.nhap-xml-bien-ban.export', $row->Id) }}"
                                       class="btn btn-sm btn-outline-success">
                                        Xuất DOCX
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">Chưa có dữ liệu. Hãy nhập file XML.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3 mb-0">
                {{ $items->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalXuatTong" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title">Xuất tổng Word theo kỳ</h5>
                </div>
                <div class="modal-body">
                    <p class="mb-1 small text-muted" id="xuat-tong-ky"></p>
                    <p class="mb-2" id="xuat-tong-status">Đang chuẩn bị…</p>
                    <div class="progress" style="height: 18px;">
                        <div class="progress-bar bg-success" id="xuat-tong-bar" role="progressbar" style="width: 0%">0%</div>
                    </div>
                    <p class="small text-muted mt-2 mb-0" id="xuat-tong-folder" style="display:none;"></p>
                    <p class="small text-danger mt-2 mb-0" id="xuat-tong-error" style="display:none;"></p>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="xuat-tong-close" data-dismiss="modal" disabled>Đóng</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $(function () {
        var $ky = $('#ma_ky_sh');
        var $btn = $('#btn-xuat-tong');
        var running = false;

        $ky.select2({
            theme: 'bootstrap4',
            allowClear: true,
            width: '100%',
            placeholder: '— Tất cả —'
        });

        function syncBtn() {
            $btn.prop('disabled', running || !$.trim($ky.val() || ''));
        }

        $ky.on('change', syncBtn);
        syncBtn();

        $btn.on('click', function () {
            var maKy = $.trim($ky.val() || '');
            if (!maKy || running) {
                return;
            }
            xuatTong(maKy);
        });

        function setProgress(done, total, text) {
            var pct = total > 0 ? Math.round(done * 100 / total) : 0;
            $('#xuat-tong-status').text(text);
            $('#xuat-tong-bar').css('width', pct + '%').text(pct + '%');
        }

        function fail(message) {
            running = false;
            syncBtn();
            $('#xuat-tong-error').text(message || 'Xuất tổng thất bại.').show();
            $('#xuat-tong-close').prop('disabled', false);
        }

        function xuatTong(maKy) {
            running = true;
            syncBtn();
            $('#xuat-tong-error').hide().text('');
            $('#xuat-tong-folder').hide().text('');
            $('#xuat-tong-close').prop('disabled', true);
            $('#xuat-tong-ky').text('Kỳ sát hạch: ' + maKy);
            setProgress(0, 1, 'Đang lấy danh sách thí sinh…');
            $('#modalXuatTong').modal({ backdrop: 'static', keyboard: false });

            $.ajax({
                url: @json(route('daotao.pdt.cong-cu-nhap.nhap-xml-bien-ban.export-tong.start')),
                method: 'POST',
                data: { ma_ky_sh: maKy, _token: $('meta[name="csrf-token"]').attr('content') }
            }).done(function (start) {
                var items = start.items || [];
                var total = start.total || items.length;
                var jobId = start.job_id;
                if (!jobId || !total) {
                    fail('Không có thí sinh để xuất.');
                    return;
                }
                setProgress(0, total, 'Đang xuất 0/' + total + '…');
                addNext(jobId, items, 0, total);
            }).fail(function (xhr) {
                fail((xhr.responseJSON && xhr.responseJSON.message) || 'Không bắt đầu được phiên xuất.');
            });
        }

        function addNext(jobId, items, index, total) {
            if (index >= total) {
                fail('Không tạo được file Word tổng.');
                return;
            }
            var item = items[index] || {};
            var label = item.ten || item.sbd || ('#' + (item.id || ''));
            setProgress(index, total, 'Đang xuất ' + (index + 1) + '/' + total + ': ' + label);
            $.ajax({
                url: @json(route('daotao.pdt.cong-cu-nhap.nhap-xml-bien-ban.export-tong.add')),
                method: 'POST',
                timeout: 0,
                data: {
                    job_id: jobId,
                    id: item.id,
                    _token: $('meta[name="csrf-token"]').attr('content')
                }
            }).done(function (res) {
                var done = res.done || (index + 1);
                if (res.phase === 'pdf') {
                    setProgress(res.pdf_done || 0, res.pdf_total || total, res.status || 'Đang chuyển PDF…');
                    pdfStepNext(jobId, res.pdf_total || total);
                    return;
                }
                if (res.download_url) {
                    var kind = res.download_kind || 'pages';
                    if (res.pages_dir) {
                        $('#xuat-tong-folder').text(
                            'Thư mục trên server: ' + res.pages_dir
                        ).show();
                    }
                    var msg = 'Đã xuất xong. ';
                    if (kind === 'pdf') {
                        msg += 'Đang tải PDF tổng (in)…';
                    } else if (kind === 'merge') {
                        msg += 'Đang tải file Word tổng…';
                    } else {
                        msg += 'Đang tải ZIP từng biên bản…';
                    }
                    setProgress(total, total, msg);
                    window.location = res.download_url;
                    running = false;
                    syncBtn();
                    $('#xuat-tong-close').prop('disabled', false);
                    if (kind === 'merge') {
                        setTimeout(function () { $('#modalXuatTong').modal('hide'); }, 800);
                    }
                    return;
                }
                setProgress(done, total, 'Đã xuất ' + done + '/' + total);
                addNext(jobId, items, index + 1, total);
            }).fail(function (xhr) {
                fail((xhr.responseJSON && xhr.responseJSON.message) || 'Lỗi khi xuất Word tổng.');
            });
        }

        function pdfStepNext(jobId, pdfTotal) {
            $.ajax({
                url: @json(route('daotao.pdt.cong-cu-nhap.nhap-xml-bien-ban.export-tong.pdf-step')),
                method: 'POST',
                timeout: 0,
                data: {
                    job_id: jobId,
                    _token: $('meta[name="csrf-token"]').attr('content')
                }
            }).done(function (res) {
                if (res.download_url) {
                    var kind = res.download_kind || 'pdf';
                    if (res.pages_dir) {
                        $('#xuat-tong-folder').text('Thư mục trên server: ' + res.pages_dir).show();
                    }
                    setProgress(pdfTotal, pdfTotal, 'Đã xuất xong. Đang tải PDF tổng (in)…');
                    window.location = res.download_url;
                    running = false;
                    syncBtn();
                    $('#xuat-tong-close').prop('disabled', false);
                    return;
                }
                if (res.phase === 'pdf') {
                    var label = res.name ? (' — ' + res.name) : '';
                    setProgress(res.pdf_done || 0, res.pdf_total || pdfTotal,
                        (res.status || 'Đang chuyển PDF…') + label);
                    pdfStepNext(jobId, res.pdf_total || pdfTotal);
                    return;
                }
                fail('Không hoàn tất được bước chuyển PDF.');
            }).fail(function (xhr) {
                fail((xhr.responseJSON && xhr.responseJSON.message) || 'Lỗi khi chuyển PDF (LibreOffice).');
            });
        }
    });
</script>
@endpush
