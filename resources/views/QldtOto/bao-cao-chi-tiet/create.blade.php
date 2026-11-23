@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Tạo mới — '.$item['title'])

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-white border py-2 small mb-3">
            <li class="breadcrumb-item"><a href="{{ route('qldtoto.index') }}">QLĐT Ô TÔ</a></li>
            @foreach (array_slice($duongDan, 1) as $buoc)
                <li class="breadcrumb-item">{{ $buoc }}</li>
            @endforeach
            <li class="breadcrumb-item"><a href="{{ route('qldtoto.bao-cao-chi-tiet.index') }}">{{ $item['title'] }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Tạo mới</li>
        </ol>
    </nav>

    @if (session('error'))
        <div class="alert alert-danger py-2">{{ session('error') }}</div>
    @endif

    <div class="mb-3">
        <a href="{{ route('qldtoto.bao-cao-chi-tiet.index') }}" class="btn btn-sm btn-outline-secondary">← Danh sách</a>
    </div>

    <div class="card card-panel">
        <div class="card-header">Tạo mới báo cáo chi tiết</div>
        <div class="card-body">
            <p class="small text-muted mb-3">
                Bắt buộc file <strong>danh sách thí sinh</strong> (7 cột). Các file điểm / đạt–trượt là tùy chọn;
                thiếu file sẽ ghi nhận vắng theo ghi chú từng thí sinh.
            </p>
            @include('QldtOto.bao-cao-chi-tiet.partials.form-nhap-file', [
                'ngayKyThi' => $ngayMacDinh,
                'tenKhoaThi' => '',
                'diaDiem' => '',
                'submitLabel' => 'Ghép dữ liệu & lưu',
            ])
        </div>
    </div>
@endsection