@extends('PMGPLX.layouts.quan-ly')

@section('title', $record->TenKhoaThi.' — '.$item['title'])

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-white border py-2 small mb-3">
            <li class="breadcrumb-item"><a href="{{ route('qldtoto.index') }}">QLĐT Ô TÔ</a></li>
            @foreach (array_slice($duongDan, 1) as $buoc)
                <li class="breadcrumb-item">{{ $buoc }}</li>
            @endforeach
            <li class="breadcrumb-item"><a href="{{ route('qldtoto.bao-cao-chi-tiet.index') }}">{{ $item['title'] }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $record->TenKhoaThi }}</li>
        </ol>
    </nav>

    @if (session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger py-2">{{ session('error') }}</div>
    @endif

    <div class="d-flex flex-wrap align-items-center mb-3">
        <a href="{{ route('qldtoto.bao-cao-chi-tiet.index') }}" class="btn btn-sm btn-outline-secondary mr-2 mb-2">← Danh sách</a>
        <a href="{{ route('qldtoto.bao-cao-chi-tiet.export', ['id' => $record->id]) }}" class="btn btn-sm btn-navy mb-2">Xuất Excel báo cáo chi tiết</a>
    </div>

    <div class="card card-panel mb-3">
        <div class="card-header">Thông tin kỳ thi</div>
        <div class="card-body py-2 small">
            <strong>Ngày kỳ thi:</strong> {{ $record->NgayKyThi->format('d/m/Y') }}
            &nbsp;|&nbsp; <strong>Tên khóa:</strong> {{ $record->TenKhoaThi }}
            @if ($record->TenKhoaTuFile)
                &nbsp;|&nbsp; <span class="text-muted">DS file: {{ $record->TenKhoaTuFile }}</span>
            @endif
            @if ($record->DiaDiemSatHach)
                &nbsp;|&nbsp; <strong>Địa điểm:</strong> {{ $record->DiaDiemSatHach }}
            @endif
        </div>
    </div>

    <div class="card card-panel mb-3">
        <div class="card-header">Nhập / cập nhật file</div>
        <div class="card-body">
            <p class="small text-muted mb-3">
                Chọn lại file để ghép lại dữ liệu. Trùng <strong>ngày kỳ thi + tên khóa</strong> sẽ cập nhật bản ghi hiện tại.
            </p>
            @include('QldtOto.bao-cao-chi-tiet.partials.form-nhap-file', [
                'ngayKyThi' => $record->NgayKyThi->format('Y-m-d'),
                'tenKhoaThi' => $record->TenKhoaThi,
                'diaDiem' => $record->DiaDiemSatHach ?? '',
                'submitLabel' => 'Ghép lại & lưu',
                'recordId' => $record->id,
                'batBuocDanhSach' => $thiSinh === [],
            ])
        </div>
    </div>

    @if ($canhBao !== [])
        <div class="alert alert-warning py-2">
            <strong>Cảnh báo:</strong>
            <ul class="mb-0 pl-3">@foreach ($canhBao as $msg)<li>{{ $msg }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="card card-panel">
        <div class="card-header">Kết quả ghép ({{ count($thiSinh) }} thí sinh)</div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-sm table-bordered mb-0 text-center" style="font-size: 0.8rem;">
                <thead class="thead-light">
                    <tr>
                        <th>STT</th>
                        <th class="text-left">Họ tên</th>
                        <th>Ngày sinh</th>
                        <th>CCCD</th>
                        <th>Hạng</th>
                        <th>LT</th>
                        <th>MP</th>
                        <th>H</th>
                        <th>CC</th>
                        <th>ĐT</th>
                        <th>Tổng</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($thiSinh as $ts)
                        <tr>
                            <td>{{ $ts['stt'] ?? '' }}</td>
                            <td class="text-left">{{ $ts['hoTen'] ?? '' }}</td>
                            <td>{{ $ts['ngaySinh'] ?? '' }}</td>
                            <td>{{ $ts['soDinhDanh'] ?? '' }}</td>
                            <td>{{ $ts['hang'] ?? '' }}</td>
                            <td>{{ $ts['lt'] ?? '' }}</td>
                            <td>{{ $ts['mp'] ?? '' }}</td>
                            <td>{{ $ts['h'] ?? '' }}</td>
                            <td>{{ $ts['cc'] ?? '' }}</td>
                            <td>{{ $ts['duong'] ?? '' }}</td>
                            <td><strong>{{ $ts['tongHop'] ?? '' }}</strong></td>
                            <td class="text-left small">{{ $ts['ghiChu'] ?? '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection