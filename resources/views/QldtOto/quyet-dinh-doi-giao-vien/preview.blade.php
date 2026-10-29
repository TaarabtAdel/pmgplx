@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Xem trước — Quyết định đổi giáo viên')

@section('content')
    @php
        $dsKhoa = implode(', ', array_column($preview['khoa_list'], 'ten_ngan'));
        $soDong = array_sum(array_map(fn ($k) => count($k['rows']), $preview['khoa_list']));
    @endphp

    <div class="card card-panel mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Xem trước — Quyết định đổi giáo viên</span>
            <a href="{{ route('qldtoto.quyet-dinh-doi-giao-vien.cancel') }}" class="btn btn-sm btn-outline-secondary">← Sửa lại</a>
        </div>
        <div class="card-body">
            <div><strong>Số:</strong> {{ $preview['so_qd'] }}{{ \App\Support\QldtOto\QuyetDinhDieuDongXeSupport::HAU_TO_SO_QD }}</div>
            <div><strong>Ngày quyết định:</strong> {{ \Carbon\Carbon::parse($preview['ngay_quyet_dinh'])->format('d/m/Y') }}</div>
            <div><strong>Khoá:</strong> {{ $dsKhoa }}</div>
            <div class="mt-2 text-muted small">
                {{ count($preview['khoa_list']) }} khoá, {{ $soDong }} dòng đổi giáo viên.
            </div>
            @if (session('error'))
                <div class="alert alert-danger mt-2 mb-0 py-2 small">{{ session('error') }}</div>
            @endif
            <div class="mt-3">
                <a href="{{ route('qldtoto.quyet-dinh-doi-giao-vien.export') }}" class="btn btn-sm btn-navy">Xuất Word</a>
            </div>
        </div>
    </div>

    <div class="card card-panel mb-3">
        <div class="card-header py-2">Điều 1. Quyết định điều chỉnh giáo viên và xe giảng dạy khóa {{ $dsKhoa }} như sau:</div>
        <div class="card-body">
            @foreach ($preview['khoa_list'] as $khoa)
                <div class="font-weight-bold mb-1">- Khóa {{ $khoa['ten_ngan'] }}:</div>
                @foreach ($khoa['rows'] as $row)
                    <p class="mb-2 pl-3">+ {{ \App\Support\QldtOto\QuyetDinhDoiGiaoVienSupport::cauDieuChinh($khoa['ten_ngan'], $row) }}</p>
                @endforeach
            @endforeach
        </div>
    </div>

    <a href="{{ route('qldtoto.quyet-dinh-doi-giao-vien.cancel') }}" class="btn btn-sm btn-outline-secondary">← Sửa lại</a>
@endsection
