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
        <div class="card-header d-flex align-items-center flex-wrap">
            <span class="mr-auto">Danh sách báo cáo chi tiết</span>
            <a href="{{ route('qldtoto.bao-cao-chi-tiet.create') }}" class="btn btn-sm btn-navy">＋ Tạo mới báo cáo chi tiết</a>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success py-2">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger py-2">{{ session('error') }}</div>
            @endif

            <p class="small text-muted mb-3">
                Mỗi bản ghi ghép theo <strong>ngày kỳ thi + tên khóa</strong>. Bấm tên khóa hoặc <em>Chi tiết</em> để xem, nhập lại file và xuất Excel.
            </p>

            @if ($danhSachKy->isEmpty())
                <p class="text-muted mb-0">Chưa có báo cáo nào. Bấm <strong>Tạo mới báo cáo chi tiết</strong> để bắt đầu.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width: 3rem;">#</th>
                                <th>Ngày kỳ thi</th>
                                <th>Tên khóa</th>
                                <th>Thi lại (kỳ gốc)</th>
                                <th>Tên khóa (file DS)</th>
                                <th>Cập nhật</th>
                                <th class="text-right">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($danhSachKy as $ky)
                                <tr>
                                    <td>{{ $ky->id }}</td>
                                    <td>{{ $ky->NgayKyThi->format('d/m/Y') }}</td>
                                    <td>
                                        <a href="{{ route('qldtoto.bao-cao-chi-tiet.show', ['id' => $ky->id]) }}">{{ $ky->TenKhoaThi }}</a>
                                    </td>
                                    <td class="small">
                                        @if ($ky->LaThiLai)
                                            @if ($ky->baoCaoGoc)
                                                <span class="badge badge-info">Thi lại</span>
                                                <span class="text-nowrap">Ngày {{ $ky->baoCaoGoc->NgayKyThi->format('d/m/Y') }}</span>
                                                <a href="{{ route('qldtoto.bao-cao-chi-tiet.show', ['id' => $ky->baoCaoGoc->id]) }}"
                                                   class="d-block text-muted" title="Mở báo cáo kỳ gốc">{{ $ky->baoCaoGoc->TenKhoaThi }}</a>
                                            @else
                                                <span class="badge badge-warning text-dark">Thi lại</span>
                                                <span class="text-muted">Chưa gắn kỳ gốc</span>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="small text-muted">{{ $ky->TenKhoaTuFile }}</td>
                                    <td class="small text-muted">{{ $ky->updated_at?->format('d/m/Y H:i') }}</td>
                                    <td class="text-right text-nowrap">
                                        <a href="{{ route('qldtoto.bao-cao-chi-tiet.show', ['id' => $ky->id]) }}" class="btn btn-outline-primary btn-sm">Chi tiết</a>
                                        <a href="{{ route('qldtoto.bao-cao-chi-tiet.export', ['id' => $ky->id]) }}" class="btn btn-outline-secondary btn-sm">Excel</a>
                                        <form method="POST" action="{{ route('qldtoto.bao-cao-chi-tiet.destroy', ['id' => $ky->id]) }}" class="d-inline"
                                              onsubmit="return confirm('Xoá hết dữ liệu đã lưu của «{{ $ky->TenKhoaThi }}» (ngày {{ $ky->NgayKyThi->format('d/m/Y') }})? Thao tác không hoàn tác.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm">Xoá</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection