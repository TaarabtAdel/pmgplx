@extends('PMGPLX.layouts.quan-ly')

@section('title', $item['title'])

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-white border py-2 small mb-3">
            <li class="breadcrumb-item"><a href="{{ route('qldtoto.index') }}">QLĐT Ô TÔ</a></li>
            @foreach (array_slice($duongDan, 1) as $buoc)
                <li class="breadcrumb-item">{{ $buoc }}</li>
            @endforeach
            <li class="breadcrumb-item active" aria-current="page">{{ $item['title'] }}</li>
        </ol>
    </nav>

    <div class="card card-panel">
        <div class="card-header">{{ $item['title'] }}</div>
        <div class="card-body">
            @if (! empty($item['mo_ta']))
                <div class="alert alert-info py-2">
                    <strong>Ghi chú:</strong> {{ $item['mo_ta'] }}
                </div>
            @endif
            @if (($slug ?? '') === '3-danh-sach-thi-sinh-du-sat-hach')
                <p class="mb-3">
                    Danh sách thí sinh dự sát hạch lấy từ <strong>PMGPLX bản cũ</strong>.
                    Vào danh mục học viên bản cũ, lọc theo khóa (và điều kiện khác nếu cần), rồi dùng
                    <strong>Xuất Excel</strong> để tải file.
                </p>
                <a href="{{ route('pmgplxold.dm.hoc-vien.index') }}" class="btn btn-sm btn-success">
                    ⬇ Mở danh mục học viên (bản cũ) — xuất Excel
                </a>
            @else
                <p class="text-muted mb-0">Màn hình đang được xây dựng.</p>
            @endif
        </div>
    </div>
@endsection
