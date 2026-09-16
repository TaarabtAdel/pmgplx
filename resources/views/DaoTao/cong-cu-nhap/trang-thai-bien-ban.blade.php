@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Trạng thái sinh biên bản')

@section('content')
    @php
        $status = $job['status'] ?? '';
        $statusLabel = match ($status) {
            'pending' => 'Đang chờ xử lý',
            'processing' => 'Đang sinh file',
            'done' => 'Hoàn tất',
            'failed' => 'Thất bại',
            default => $status,
        };
    @endphp

    <div class="card card-panel">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Sinh biên bản tổng hợp</span>
            <a href="{{ route('daotao.pdt.cong-cu-nhap.nhap-xml-bien-ban.cancel', $job['id']) }}" class="btn btn-sm btn-outline-secondary">← Chọn file khác</a>
        </div>
        <div class="card-body">
            <div><strong>File XML:</strong> {{ $job['file_name'] ?? '' }}</div>
            <div class="mt-2">
                <strong>Trạng thái:</strong>
                @if ($status === 'done')
                    <span class="badge badge-success">{{ $statusLabel }}</span>
                @elseif ($status === 'failed')
                    <span class="badge badge-danger">{{ $statusLabel }}</span>
                @else
                    <span class="badge badge-info">{{ $statusLabel }}</span>
                @endif
            </div>
            @if ((int) ($job['thi_sinh_count'] ?? 0) > 0)
                <div class="mt-1"><strong>Số thí sinh:</strong> {{ number_format((int) $job['thi_sinh_count']) }}</div>
            @elseif ((int) ($job['expected_count'] ?? 0) > 0)
                <div class="mt-1"><strong>Số bản ghi (header):</strong> {{ number_format((int) $job['expected_count']) }}</div>
            @endif
            @if (! empty($job['error_message']))
                <div class="alert alert-danger mt-3 mb-0">{{ $job['error_message'] }}</div>
            @endif

            @if ($status === 'done')
                <a href="{{ route('daotao.pdt.cong-cu-nhap.nhap-xml-bien-ban.download', $job['id']) }}" class="btn btn-navy btn-lg mt-3">
                    Tải file DOCX
                </a>
            @elseif ($poll)
                <p class="text-muted mt-3 mb-0">Trang sẽ tự tải lại mỗi 3 giây cho đến khi xong.</p>
            @endif
        </div>
    </div>
@endsection

@if ($poll)
    @push('scripts')
        <script>
            setTimeout(function () { window.location.reload(); }, 3000);
        </script>
    @endpush
@endif
