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

    @if (session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger py-2">{{ session('error') }}</div>
    @endif

    <div class="card card-panel">
        <div class="card-header">Xuất báo cáo kỳ kiểm tra kết thúc môn (KTM)</div>
        <div class="card-body">
            <p class="small text-muted mb-3">
                Upload file Excel kết quả kiểm tra (mẫu có các sheet <strong>DS SH</strong>, <strong>Sheet1</strong>…).
                Hệ thống tính số liệu và xuất 3 file theo mẫu: <strong>BBTH</strong>, <strong>BC CHI TIẾT</strong>, <strong>BC CHUNG</strong>.
                Mẫu file đặt tại <code>docs/xuat-bao-cao/</code>; mô tả đầy đủ trong <code>docs/xuat-bao-cao/README.md</code>.
            </p>

            <form method="POST" action="{{ route('qldtoto.xuat-bao-cao-ktm.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label for="file">File nhập kết quả KTM</label>
                    <input type="file" name="file" id="file" class="form-control-file" accept=".xls,.xlsx" required>
                </div>
                <button type="submit" class="btn btn-navy">Xem trước &amp; cấu hình xuất</button>
            </form>
        </div>
    </div>
@endsection