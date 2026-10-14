@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Xem trước dữ liệu lưu DB')

@section('content')
    @include('PMGPLX.lich.partials.nhap-lich-import-routes')
    @php
        $meta = $payload['meta'] ?? [];
        $gvSave = $payload['lich_giao_vien'] ?? [];
        $gvSkip = $payload['lich_giao_vien_bo_qua'] ?? [];
        $gvDelete = $payload['lich_giao_vien_xoa'] ?? [];
        $xeSave = $payload['lich_xe_tap'] ?? [];
        $xeSkip = $payload['lich_xe_tap_bo_qua'] ?? [];
        $xeDelete = $payload['lich_xe_tap_xoa'] ?? [];
        $offDaySummary = $meta['off_day_summary'] ?? [];
    @endphp

    <div class="card card-panel">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Xem trước dữ liệu sẽ lưu vào DB</span>
            <a href="{{ $nhapLichRoutes['preview_xe'] }}" class="btn btn-sm btn-outline-secondary">← Quay lại lịch xe</a>
        </div>
        <div class="card-body">
            @php
                $replaceMaKh = $meta['replace_ma_kh'] ?? [];
                $replaceWipeGv = (int) ($meta['replace_wipe_gv'] ?? 0);
                $replaceWipeXe = (int) ($meta['replace_wipe_xe'] ?? 0);
            @endphp

            @if (! empty($meta['slot_replace_import']))
                <div class="alert alert-primary">
                    <strong>Nhập lịch thay thế:</strong>
                    cập nhật GV / xe / ghi chú khi trùng <strong>cùng khóa + khung TG bắt đầu/kết thúc</strong>;
                    thêm buổi mới nếu chưa có; <strong>không xóa</strong> các buổi khác của khóa.
                    Dự kiến: GV cập nhật <strong>{{ (int) ($meta['gv_update'] ?? 0) }}</strong>,
                    thêm mới <strong>{{ (int) ($meta['gv_save'] ?? 0) }}</strong>;
                    xe cập nhật <strong>{{ (int) ($meta['xe_update'] ?? 0) }}</strong>,
                    thêm mới <strong>{{ (int) ($meta['xe_save'] ?? 0) }}</strong>.
                </div>
            @elseif ($replaceMaKh !== [])
                <div class="alert alert-danger">
                    <strong>Thay thế lịch theo khóa:</strong>
                    trước khi ghi, hệ thống sẽ xóa <strong>toàn bộ</strong> lịch TH / xe tập lái cũ
                    (<code>IsKhoaHoc*=0</code>) của khóa:
                    @foreach ($replaceMaKh as $maKh)
                        <code class="mr-1">{{ $maKh }}</code>
                    @endforeach
                    — hiện có khoảng <strong>{{ number_format($replaceWipeGv) }}</strong> buổi GV,
                    <strong>{{ number_format($replaceWipeXe) }}</strong> buổi xe sẽ bị xóa,
                    sau đó ghi <strong>{{ count($gvSave) }}</strong> buổi GV và <strong>{{ count($xeSave) }}</strong> buổi xe từ file.
                </div>
            @endif

            <div class="mb-3">
                <div><strong>File:</strong> {{ $preview['file_name'] ?? '' }}</div>
                <div>
                    <strong>Lịch giáo viên:</strong>
                    sẽ lưu mới <strong class="text-success">{{ (int) ($meta['gv_save'] ?? 0) }}</strong>@if ((int) ($meta['gv_update'] ?? 0) > 0), cập nhật <strong class="text-primary">{{ (int) ($meta['gv_update'] ?? 0) }}</strong>@endif,
                    bỏ qua <strong class="text-danger">{{ (int) ($meta['gv_skip'] ?? 0) }}</strong>
                </div>
                <div>
                    <strong>Lịch xe tập lái:</strong>
                    sẽ lưu mới <strong class="text-success">{{ (int) ($meta['xe_save'] ?? 0) }}</strong>@if ((int) ($meta['xe_update'] ?? 0) > 0), cập nhật <strong class="text-primary">{{ (int) ($meta['xe_update'] ?? 0) }}</strong>@endif,
                    bỏ qua <strong class="text-danger">{{ (int) ($meta['xe_skip'] ?? 0) }}</strong>
                </div>
                @if ((int) ($meta['gv_delete'] ?? 0) > 0 || (int) ($meta['xe_delete'] ?? 0) > 0)
                    <div class="text-muted small">
                        Ngày nghỉ trong file (không ghi buổi mới): GV {{ (int) ($meta['gv_delete'] ?? 0) }}, xe {{ (int) ($meta['xe_delete'] ?? 0) }} buổi cũ trùng ngày nghỉ
                        (đã nằm trong phần xóa toàn khóa ở trên).
                    </div>
                @endif
            </div>

            @if ($offDaySummary !== [])
                <div class="alert alert-danger">
                    <strong>Ngày nghỉ — lịch sẽ gỡ khỏi DB:</strong>
                    <ul class="mb-0 mt-2 pl-3">
                        @foreach ($offDaySummary as $item)
                            <li>
                                {{ ($item['ten_gv'] ?? '') !== '' ? $item['ten_gv'] : ($item['MaGV'] ?? '') }}
                                — khóa {{ $item['MaKH'] ?? '' }}
                                — ngày {{ \Carbon\Carbon::parse($item['ngay'])->format('d/m/Y') }}
                                (GV {{ (int) ($item['gv_count'] ?? 0) }}, xe {{ (int) ($item['xe_count'] ?? 0) }})
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="alert alert-info">
                Đây là mảng record sau tiền xử lý, đúng cấu trúc sẽ insert vào
                <code>KhoaHoc_GiaoVien</code> (LoaiGV=TH, <code>MaMonHoc</code>=NULL, <code>TenMonHoc</code>=mã số môn)
                và <code>KhoaHoc_XeTap</code>.
                Nhấn <strong>Xác nhận lưu DB</strong> để ghi vào database.
            </div>

            <h5 class="section-title">1) Lịch giáo viên — sẽ lưu ({{ count($gvSave) }})</h5>
            <div class="table-responsive mb-3">
                <pre class="mb-0 p-2 border bg-light" style="max-height: 28rem; overflow: auto; white-space: pre-wrap; word-break: break-word;">{{ json_encode($gvSave, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>

            @if (count($gvSkip) > 0)
                <h5 class="section-title text-danger">Lịch giáo viên — bỏ qua ({{ count($gvSkip) }})</h5>
                <div class="table-responsive mb-3">
                    <pre class="mb-0 p-2 border bg-light" style="max-height: 16rem; overflow: auto; white-space: pre-wrap; word-break: break-word;">{{ json_encode($gvSkip, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            @endif

            @if (count($gvDelete) > 0)
                <h5 class="section-title text-danger">Lịch giáo viên — sẽ xóa ({{ count($gvDelete) }})</h5>
                <div class="table-responsive mb-3">
                    <pre class="mb-0 p-2 border bg-light" style="max-height: 16rem; overflow: auto; white-space: pre-wrap; word-break: break-word;">{{ json_encode($gvDelete, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            @endif

            <h5 class="section-title">2) Lịch xe tập lái — sẽ lưu ({{ count($xeSave) }})</h5>
            <div class="table-responsive mb-3">
                <pre class="mb-0 p-2 border bg-light" style="max-height: 28rem; overflow: auto; white-space: pre-wrap; word-break: break-word;">{{ json_encode($xeSave, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>

            @if (count($xeSkip) > 0)
                <h5 class="section-title text-danger">Lịch xe tập lái — bỏ qua ({{ count($xeSkip) }})</h5>
                <div class="table-responsive mb-3">
                    <pre class="mb-0 p-2 border bg-light" style="max-height: 16rem; overflow: auto; white-space: pre-wrap; word-break: break-word;">{{ json_encode($xeSkip, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            @endif

            @if (count($xeDelete) > 0)
                <h5 class="section-title text-danger">Lịch xe tập lái — sẽ xóa ({{ count($xeDelete) }})</h5>
                <div class="table-responsive mb-3">
                    <pre class="mb-0 p-2 border bg-light" style="max-height: 16rem; overflow: auto; white-space: pre-wrap; word-break: break-word;">{{ json_encode($xeDelete, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            @endif

            <form method="POST" action="{{ $nhapLichRoutes['confirm'] }}" class="mt-2">
                @csrf
                <button type="submit" class="btn btn-success btn-lg">Xác nhận lưu DB</button>
                <a href="{{ $nhapLichRoutes['preview_xe'] }}" class="btn btn-outline-secondary btn-lg ml-2">← Quay lại lịch xe</a>
                <a href="{{ $nhapLichRoutes['preview_gv'] }}" class="btn btn-outline-secondary btn-lg ml-2">← Quay lại lịch GV</a>
                <a href="{{ $nhapLichRoutes['cancel'] }}" class="btn btn-outline-danger btn-lg ml-2">Hủy</a>
            </form>
        </div>
    </div>
@endsection
