@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Lịch dự kiến thực hành')

@section('content')
    @include('QldtOto.lich-du-kien.thuc-hanh.partials.breadcrumb')

    <div class="card card-panel">
        <div class="card-header d-flex align-items-center">
            <span class="mr-auto">Lịch dự kiến thực hành</span>
            <a href="{{ route('qldtoto.lich-du-kien.thuc-hanh.create') }}" class="btn btn-sm btn-navy">＋ Tạo lịch dự kiến</a>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('qldtoto.lich-du-kien.thuc-hanh.index') }}" class="form-row align-items-end mb-3">
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
                            <th>Hạng</th>
                            <th>Ngày BĐ thực hành</th>
                            <th>Xe tự động</th>
                            <th>Cặp GV</th>
                            <th>Cabin của cặp trước</th>
                            <th>Ngày nghỉ</th>
                            <th>Người tạo</th>
                            <th>Cập nhật</th>
                            <th style="width: 8rem;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $i => $lich)
                            @php
                                $dsNghi = $lich->ngayNghis->sortBy('Ngay')->map(fn ($n) => $n->Ngay->format('d/m/Y'));
                            @endphp
                            <tr>
                                <td>{{ $items->firstItem() + $i }}</td>
                                <td>
                                    <a href="{{ route('qldtoto.lich-du-kien.thuc-hanh.edit', $lich) }}">{{ $lich->TenKhoa }}</a>
                                </td>
                                <td>{{ $hangOptions[$lich->Hang] ?? $lich->Hang }}</td>
                                <td>{{ $lich->NgayBatDauThucHanh?->format('d/m/Y') }}</td>
                                <td>{{ $lich->XeTuDong ?: '—' }}</td>
                                <td>{{ $lich->cap_gvs_count ?: '—' }}</td>
                                <td>{{ $lich->CabinCapTruocNgay?->format('d/m/Y') ?? '—' }}</td>
                                <td title="{{ $dsNghi->implode(', ') }}">
                                    @if ($dsNghi->isEmpty())
                                        <span class="text-muted">—</span>
                                    @else
                                        {{ $dsNghi->count() }} ngày
                                        <span class="small text-muted">({{ $dsNghi->take(3)->implode(', ') }}{{ $dsNghi->count() > 3 ? ', …' : '' }})</span>
                                    @endif
                                </td>
                                <td>{{ $lich->nguoiTao?->Ten ?? '—' }}</td>
                                <td>{{ $lich->NgayCapNhat?->format('d/m/Y H:i') }}</td>
                                <td class="text-nowrap">
                                    <a href="{{ route('qldtoto.lich-du-kien.thuc-hanh.edit', $lich) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                                    <form method="POST" action="{{ route('qldtoto.lich-du-kien.thuc-hanh.destroy', $lich) }}" class="d-inline"
                                          onsubmit="return confirm('Xoá lịch dự kiến này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Xoá</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted py-3">
                                    Chưa có lịch dự kiến nào. Bấm <strong>＋ Tạo lịch dự kiến</strong> để bắt đầu.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($items->hasPages())
                <div class="mt-3">{{ $items->links('pagination::bootstrap-4') }}</div>
            @endif
        </div>
    </div>
@endsection
