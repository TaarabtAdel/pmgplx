@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Xem trước ghép — '.$item['title'])

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-white border py-2 small mb-3">
            <li class="breadcrumb-item"><a href="{{ route('qldtoto.index') }}">QLĐT Ô TÔ</a></li>
            @foreach (array_slice($duongDan, 1) as $buoc)
                <li class="breadcrumb-item">{{ $buoc }}</li>
            @endforeach
            <li class="breadcrumb-item"><a href="{{ route('qldtoto.bao-cao-chi-tiet.index') }}">{{ $item['title'] }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Xem trước ghép</li>
        </ol>
    </nav>

    @if (session('error'))
        <div class="alert alert-danger py-2">{{ session('error') }}</div>
    @endif

    <div class="alert alert-warning py-2 mb-3">
        <strong>Chưa lưu vào hệ thống.</strong>
        Dò 5 dòng mẫu bên dưới; nếu đúng bấm <strong>Xác nhận lưu</strong>, nếu sai bấm <strong>Huỷ — chọn file lại</strong>.
    </div>

    <div class="card card-panel mb-3">
        <div class="card-header">Thông tin kỳ thi (sẽ lưu)</div>
        <div class="card-body py-2 small">
            <strong>Ngày kỳ thi:</strong> {{ $ngayKyThi }}
            &nbsp;|&nbsp; <strong>Tên khóa:</strong> {{ $tenKhoaThi }}
            @if ($tenKhoaTuFile !== '')
                &nbsp;|&nbsp; <span class="text-muted">DS file: {{ $tenKhoaTuFile }}</span>
            @endif
            @if ($diaDiem !== '')
                &nbsp;|&nbsp; <strong>Địa điểm:</strong> {{ $diaDiem }}
            @endif
            @if ($laThiLai)
                &nbsp;|&nbsp; <span class="badge badge-info">Thi lại</span>
                @if ($kyGocLabel !== '')
                    <span class="text-muted">Gốc: {{ $kyGocLabel }}</span>
                @endif
            @endif
            &nbsp;|&nbsp; <strong>{{ $soThiSinh }}</strong> thí sinh sau ghép
        </div>
    </div>

    @if ($canhBao !== [])
        <div class="alert alert-warning py-2">
            <strong>Cảnh báo (vẫn có thể lưu nếu bạn chấp nhận):</strong>
            <ul class="mb-0 pl-3">@foreach ($canhBao as $msg)<li>{{ $msg }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card card-panel mb-3 border-info">
        <div class="card-header bg-light py-2">
            <strong>5 dòng mẫu để dò</strong>
            <span class="small text-muted d-block d-md-inline ml-md-2">
                @if ($uuTienTuFile)
                    Ưu tiên thí sinh có trong file điểm vừa tải
                @else
                    5 dòng đầu danh sách
                @endif
            </span>
        </div>
        <div class="card-body py-2">
            @include('QldtOto.bao-cao-chi-tiet.partials.bang-xem-truoc-5-dong', ['xemTruocGhep' => $xemTruocGhep])
        </div>
    </div>

    <div class="d-flex flex-wrap align-items-center">
        <form method="POST" action="{{ route('qldtoto.bao-cao-chi-tiet.confirm') }}" class="mr-2 mb-2">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <button type="submit" class="btn btn-navy" onclick="return confirm('Lưu báo cáo chi tiết với dữ liệu vừa ghép?');">
                Xác nhận lưu
            </button>
        </form>
        <form method="POST" action="{{ route('qldtoto.bao-cao-chi-tiet.preview.cancel') }}" class="mb-2">
            @csrf
            <button type="submit" class="btn btn-outline-secondary">Huỷ — chọn file lại</button>
        </form>
    </div>
@endsection