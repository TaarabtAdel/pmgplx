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
        <a href="{{ route('qldtoto.bao-cao-chi-tiet.export', ['id' => $record->id]) }}" class="btn btn-sm btn-navy mr-2 mb-2">Xuất Excel báo cáo chi tiết</a>
        <a href="{{ route('qldtoto.bao-cao-chi-tiet.export-ds', ['loai' => 'vang', 'id' => $record->id]) }}" class="btn btn-sm btn-outline-secondary mr-2 mb-2">Xuất Excel DS vắng</a>
        <a href="{{ route('qldtoto.bao-cao-chi-tiet.export-ds', ['loai' => 'dat', 'id' => $record->id]) }}" class="btn btn-sm btn-outline-secondary mr-2 mb-2">Xuất Excel DS đạt</a>
        <a href="{{ route('qldtoto.bao-cao-chi-tiet.export-ds', ['loai' => 'ko-dat', 'id' => $record->id]) }}" class="btn btn-sm btn-outline-secondary mr-2 mb-2">Xuất Excel DS không đạt</a>
        <form method="POST" action="{{ route('qldtoto.bao-cao-chi-tiet.destroy', ['id' => $record->id]) }}" class="d-inline mb-2"
              onsubmit="return confirm('Xoá hết dữ liệu đã lưu của «{{ $record->TenKhoaThi }}» (ngày {{ $record->NgayKyThi->format('d/m/Y') }})? Danh sách thí sinh, kết quả các môn và cảnh báo đều bị xoá. Không hoàn tác.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger">Xoá</button>
        </form>
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
            @if ($record->LaThiLai && $record->baoCaoGoc)
                &nbsp;|&nbsp; <span class="badge badge-info">Thi lại</span>
                <span class="text-muted">Gốc: {{ $record->baoCaoGoc->NgayKyThi->format('d/m/Y') }} — {{ $record->baoCaoGoc->TenKhoaThi }}</span>
            @endif
        </div>
    </div>

    @include('QldtOto.bao-cao-chi-tiet.partials.bang-quy-uoc-logic', [
        'laThiLai' => $record->LaThiLai,
    ])

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
                'submitLabel' => 'Ghép lại & xem trước',
                'recordId' => $record->id,
                'batBuocDanhSach' => $thiSinh === [],
                'laThiLai' => $record->LaThiLai,
                'baoCaoGocId' => $record->BaoCaoGocId,
                'danhSachKyGoc' => $danhSachKyGoc,
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
        <div class="card-header d-flex flex-wrap align-items-center">
            <span class="mr-auto">Kết quả ghép ({{ count($thiSinh) }} thí sinh)</span>
            @if ($record->LaThiLai && $cccdDatDotGoc !== [])
                <span class="small text-muted"><span class="d-inline-block rounded px-2 py-0 mr-1" style="background:#e9ecef;">&nbsp;</span> Đã đạt đợt 1 (kỳ gốc)</span>
            @endif
        </div>
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
                        @php
                            $cccdHang = preg_replace('/\D/', '', (string) ($ts['soDinhDanh'] ?? ''));
                            $daDatDot1 = $cccdHang !== '' && ! empty($cccdDatDotGoc[$cccdHang]);
                        @endphp
                        <tr @class(['ktm-da-dat-dot-goc' => $daDatDot1])>
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

    <div class="card card-panel mt-3">
        <div class="card-header">Lịch sử upload file</div>
        <div class="card-body p-0">
            @if ($lichSuUpload === [])
                <p class="small text-muted mb-0 p-3">Chưa có lần upload nào được ghi nhận. Mỗi lần «Ghép lại &amp; lưu» có chọn file sẽ thêm dòng tương ứng.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 3rem;">STT</th>
                                <th>Loại</th>
                                <th>Tên file</th>
                                <th>Thời gian</th>
                                <th class="text-right">Số HV được cập nhật</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lichSuUpload as $i => $dong)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $dong['loai'] }}</td>
                                    <td class="small">{{ $dong['ten_file'] }}</td>
                                    <td class="text-nowrap small">{{ $dong['thoi_gian'] }}</td>
                                    <td class="text-right">{{ $dong['so_hv'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection

@if ($record->LaThiLai)
    @push('styles')
        <style>
            tr.ktm-da-dat-dot-goc td { background-color: #e9ecef !important; }
        </style>
    @endpush
@endif