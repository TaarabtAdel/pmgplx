@extends('PMGPLX.layouts.quan-ly')

@section('title', $item['title'])

@section('content')
    @include('QldtOto.lam-lich-chinh-thuc.partials.breadcrumb')

    <div class="card card-panel">
        <div class="card-header d-flex align-items-center flex-wrap gap-2">
            <span class="mr-auto">{{ $item['title'] }}</span>
            <a href="{{ route('qldtoto.lich-du-kien.thuc-hanh.index') }}" class="btn btn-sm btn-outline-secondary">Lịch dự kiến TH</a>
            <a href="{{ route('qldtoto.lam-lich-chinh-thuc.create') }}" class="btn btn-sm btn-navy">＋ Chọn từ lịch dự kiến</a>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success py-2">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger py-2">{{ session('error') }}</div>
            @endif

            <p class="small text-muted mb-3">
                Chọn một lịch từ
                <a href="{{ route('qldtoto.lich-du-kien.thuc-hanh.index') }}">Làm lịch dự kiến → Thực hành</a>
                (đã sinh buổi), xem lại, kéo thứ tự bài, xuất Excel, rồi <strong>Lưu vào phần mềm</strong> (PMGPLX).
            </p>

            <form method="GET" action="{{ route('qldtoto.lam-lich-chinh-thuc.index') }}" class="form-row align-items-end mb-3">
                <div class="form-group col-md-4 mb-0">
                    <label>Tên khoá</label>
                    <input type="text" class="form-control form-control-sm" name="tu_khoa" value="{{ $filters['tu_khoa'] }}" placeholder="Nhập từ khoá">
                </div>
                <div class="form-group col-md-2 mb-0">
                    <label>Hạng</label>
                    <select class="form-control form-control-sm" name="hang">
                        <option value="">—Tất cả—</option>
                        @foreach ($hangOptions as $ma => $nhan)
                            <option value="{{ $ma }}" @selected($filters['hang'] === $ma)>{{ $nhan }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2 mb-0">
                    <button type="submit" class="btn btn-sm btn-primary btn-block">Tìm kiếm</button>
                </div>
                <div class="col ml-auto text-right">
                    Tổng số bản ghi: <strong>{{ number_format($items->total()) }}</strong>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped table-hover table-data mb-0">
                    <thead>
                        <tr>
                            <th style="width: 3rem;">#</th>
                            <th>Tên khoá</th>
                            <th>Mã KH (PMGPLX)</th>
                            <th>Hạng</th>
                            <th>Ngày BĐ TH</th>
                            <th>Số buổi</th>
                            <th>Lưu PM</th>
                            <th>Người tạo</th>
                            <th>Cập nhật</th>
                            <th style="width: 9rem;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $i => $lich)
                            <tr>
                                <td>{{ $items->firstItem() + $i }}</td>
                                <td>
                                    <a href="{{ route('qldtoto.lam-lich-chinh-thuc.show', $lich) }}">{{ $lich->TenKhoa }}</a>
                                </td>
                                <td>{{ $lich->MaKH ?: '—' }}</td>
                                <td>{{ $hangOptions[$lich->Hang] ?? $lich->Hang }}</td>
                                <td>{{ $lich->NgayBatDauThucHanh?->format('d/m/Y') }}</td>
                                <td>{{ $lich->buois_count }}</td>
                                <td>
                                    @if ($lich->NgayLuuPm)
                                        <span class="text-success" title="{{ $lich->SoBuoiLuuPm }} buổi">
                                            {{ $lich->NgayLuuPm->format('d/m/Y H:i') }}
                                        </span>
                                    @else
                                        <span class="text-muted">Chưa</span>
                                    @endif
                                </td>
                                <td>{{ $lich->nguoiTao?->Ten ?? '—' }}</td>
                                <td>{{ $lich->NgayCapNhat?->format('d/m/Y H:i') }}</td>
                                <td class="text-nowrap">
                                    <a href="{{ route('qldtoto.lam-lich-chinh-thuc.show', $lich) }}" class="btn btn-sm btn-outline-primary">Xem</a>
                                    <form method="POST" action="{{ route('qldtoto.lam-lich-chinh-thuc.destroy', $lich) }}" class="d-inline"
                                          onsubmit="return confirm('Xoá lịch chính thức «{{ $lich->TenKhoa }}»?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Xoá</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-muted text-center py-4">
                                    Chưa có lịch chính thức.
                                    <a href="{{ route('qldtoto.lam-lich-chinh-thuc.create') }}">Chọn từ lịch dự kiến</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $items->links() }}</div>
        </div>
    </div>
@endsection
