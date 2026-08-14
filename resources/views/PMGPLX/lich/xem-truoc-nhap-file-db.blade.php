@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Xem trước dữ liệu lưu DB')

@section('content')
    @php
        $meta = $payload['meta'] ?? [];
        $gvSave = $payload['lich_giao_vien'] ?? [];
        $gvSkip = $payload['lich_giao_vien_bo_qua'] ?? [];
        $xeSave = $payload['lich_xe_tap'] ?? [];
        $xeSkip = $payload['lich_xe_tap_bo_qua'] ?? [];
    @endphp

    <div class="card card-panel">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Xem trước dữ liệu sẽ lưu vào DB</span>
            <a href="{{ route('pmgplx.lich.nhap-file.preview-xe') }}" class="btn btn-sm btn-outline-secondary">← Quay lại lịch xe</a>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <div><strong>File:</strong> {{ $preview['file_name'] ?? '' }}</div>
                <div>
                    <strong>Lịch giáo viên:</strong>
                    sẽ lưu <strong class="text-success">{{ (int) ($meta['gv_save'] ?? 0) }}</strong>,
                    bỏ qua <strong class="text-danger">{{ (int) ($meta['gv_skip'] ?? 0) }}</strong>
                </div>
                <div>
                    <strong>Lịch xe tập lái:</strong>
                    sẽ lưu <strong class="text-success">{{ (int) ($meta['xe_save'] ?? 0) }}</strong>,
                    bỏ qua <strong class="text-danger">{{ (int) ($meta['xe_skip'] ?? 0) }}</strong>
                </div>
            </div>

            <div class="alert alert-info">
                Đây là mảng record sau tiền xử lý, đúng cấu trúc sẽ insert vào
                <code>KhoaHoc_GiaoVien</code> (LoaiGV=TH) và <code>KhoaHoc_XeTap</code>.
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

            <form method="POST" action="{{ route('pmgplx.lich.nhap-file.confirm') }}" class="mt-2">
                @csrf
                <button type="submit" class="btn btn-success btn-lg">Xác nhận lưu DB</button>
                <a href="{{ route('pmgplx.lich.nhap-file.preview-xe') }}" class="btn btn-outline-secondary btn-lg ml-2">← Quay lại lịch xe</a>
                <a href="{{ route('pmgplx.lich.nhap-file.preview-gv') }}" class="btn btn-outline-secondary btn-lg ml-2">← Quay lại lịch GV</a>
                <a href="{{ route('pmgplx.lich.nhap-file.cancel') }}" class="btn btn-outline-danger btn-lg ml-2">Hủy</a>
            </form>
        </div>
    </div>
@endsection
