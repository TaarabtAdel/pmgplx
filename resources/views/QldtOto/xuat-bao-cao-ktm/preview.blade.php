@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Xem trước xuất báo cáo KTM')

@section('content')
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <h5 class="mb-0 mr-auto">Xem trước — {{ $duLieu->tenKhoa }}</h5>
        <a href="{{ route('qldtoto.xuat-bao-cao-ktm.cancel') }}" class="btn btn-sm btn-outline-secondary">← Chọn file khác</a>
    </div>

    @if (session('error'))
        <div class="alert alert-danger py-2">{{ session('error') }}</div>
    @endif

    @include('QldtOto.xuat-bao-cao-ktm.partials.doi-chieu-file', [
        'duLieu' => $duLieu,
        'fileName' => $fileName,
        'form' => $form,
    ])

    @include('QldtOto.xuat-bao-cao-ktm.partials.tham-so-bao-cao', [
        'duLieu' => $duLieu,
        'form' => $form,
    ])
@endsection