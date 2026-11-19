@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Quản lý khoá học (bản cũ)')

@section('content')
    @if (session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger py-2">{{ session('error') }}</div>
    @endif

    <div class="card card-panel mb-3">
        <div class="card-header d-flex flex-wrap align-items-center gap-2">
            <span>Quản lý khoá học <span class="badge badge-secondary">bản cũ</span></span>
            <a href="{{ route('pmgplx.dm.khoa-hoc.index') }}" class="btn btn-sm btn-outline-secondary ml-auto">← Khoá học (PM mới)</a>
            <a href="{{ route('pmgplx.dm.hoc-vien.dong-bo.form') }}" class="btn btn-sm btn-outline-primary">Đồng bộ HV → bản cũ</a>
        </div>
        <div class="card-body">
            <div class="alert alert-info py-2 small mb-3">
                DB <code>sqlsrv_old</code>. <strong>Thêm mới:</strong> không tự tăng mã — bắt buộc <strong>nhập Mã KH</strong>.
                <code>MaCSDT</code> / <code>MaSoGTVT</code> lấy mẫu từ khoá gần nhất trên bản cũ.
            </div>

            <form method="POST" action="{{ route('pmgplx.dm.khoa-hoc-ban-cu.store') }}" class="border rounded p-3 mb-4 bg-light">
                @csrf
                <div class="form-row align-items-end">
                    <div class="form-group col-md-2 mb-md-0">
                        <label for="ma_kh">Mã khoá <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm @error('ma_kh') is-invalid @enderror"
                               id="ma_kh" name="ma_kh" value="{{ old('ma_kh') }}" maxlength="50" required placeholder="Nhập tay">
                        @error('ma_kh')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group col-md-3 mb-md-0">
                        <label for="ten_kh">Tên khoá <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm @error('ten_kh') is-invalid @enderror"
                               id="ten_kh" name="ten_kh" value="{{ old('ten_kh') }}" maxlength="255" required>
                        @error('ten_kh')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group col-md-1 mb-md-0">
                        <label for="hang_gplx">Hạng GPLX</label>
                        <input type="text" class="form-control form-control-sm" id="hang_gplx" name="hang_gplx"
                               value="{{ old('hang_gplx') }}" maxlength="20" placeholder="B1">
                    </div>
                    <div class="form-group col-md-1 mb-md-0">
                        <label for="hang_dt">Hạng ĐT</label>
                        <input type="text" class="form-control form-control-sm" id="hang_dt" name="hang_dt"
                               value="{{ old('hang_dt') }}" maxlength="20">
                    </div>
                    <div class="form-group col-md-2 mb-md-0">
                        <label for="ngay_kg">Ngày KG</label>
                        <input type="date" class="form-control form-control-sm" id="ngay_kg" name="ngay_kg" value="{{ old('ngay_kg') }}">
                    </div>
                    <div class="form-group col-md-2 mb-md-0">
                        <label for="ngay_bg">Ngày BG</label>
                        <input type="date" class="form-control form-control-sm" id="ngay_bg" name="ngay_bg" value="{{ old('ngay_bg') }}">
                    </div>
                    <div class="form-group col-md-1 mb-md-0">
                        <div class="custom-control custom-checkbox mt-4">
                            <input type="checkbox" class="custom-control-input" id="trang_thai" name="trang_thai" value="1"
                                   @checked($errors->any() ? old('trang_thai') : true)>
                            <label class="custom-control-label" for="trang_thai">Hiệu lực</label>
                        </div>
                    </div>
                    <div class="form-group col-md-auto mb-md-0">
                        <button type="submit" class="btn btn-sm btn-success">＋ Thêm mới</button>
                    </div>
                </div>
            </form>

            <form method="GET" action="{{ route('pmgplx.dm.khoa-hoc-ban-cu.index') }}" class="form-row align-items-end mb-3">
                <div class="form-group col-md-4 mb-0">
                    <label>Mã|Tên khoá|Số QĐ</label>
                    <input type="text" class="form-control form-control-sm" name="tu_khoa" value="{{ $filters['tu_khoa'] }}" placeholder="Tìm kiếm">
                </div>
                <div class="form-group col-md-2 mb-0">
                    <label>Hạng đào tạo</label>
                    <select class="form-control form-control-sm" name="hang_dt">
                        <option value="">—Tất cả—</option>
                        @foreach ($hangOptions as $hang)
                            <option value="{{ $hang }}" @selected($filters['hang_dt'] === $hang)>{{ $hang }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2 mb-0">
                    <label>Trạng thái</label>
                    <select class="form-control form-control-sm" name="trang_thai">
                        <option value="">—Tất cả—</option>
                        <option value="1" @selected($filters['trang_thai'] === '1')>Hiệu lực</option>
                        <option value="0" @selected($filters['trang_thai'] === '0')>Không hiệu lực</option>
                    </select>
                </div>
                <div class="form-group col-md-2 mb-0">
                    <button type="submit" class="btn btn-sm btn-primary btn-block">Tìm kiếm</button>
                    <a href="{{ route('pmgplx.dm.khoa-hoc-ban-cu.index') }}" class="btn btn-sm btn-outline-secondary btn-block mt-1">↻</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-panel">
        <div class="card-header">Danh sách khoá học (bản cũ)</div>
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center mb-3">
                <span class="mr-3">Tổng: <strong>{{ number_format($items->total()) }}</strong></span>
                <form method="GET" action="{{ route('pmgplx.dm.khoa-hoc-ban-cu.index') }}" class="form-inline ml-auto">
                    @foreach (request()->except(['per_page', 'page']) as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                    <label class="mr-2 mb-0">Số bản ghi/trang</label>
                    <select name="per_page" class="form-control form-control-sm" onchange="this.form.submit()">
                        @foreach ([20, 50, 100, 200] as $n)
                            <option value="{{ $n }}" @selected((int) $filters['per_page'] === $n)>{{ $n }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped table-hover table-data mb-0">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Mã khoá</th>
                            <th>Tên khoá</th>
                            <th>Hạng GPLX | Hạng ĐT</th>
                            <th>Ngày KG - Ngày BG</th>
                            <th class="text-right">HV (bản cũ)</th>
                            <th class="text-right">Tổng HV</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $index => $row)
                            <tr>
                                <td>{{ $items->firstItem() + $index }}</td>
                                <td class="text-nowrap">
                                    @include('PMGPLX.danh-muc._trang-thai-icon', ['active' => (bool) $row->TrangThai])
                                    {{ $row->MaKH }}
                                </td>
                                <td>{{ $row->TenKH }}</td>
                                <td class="text-nowrap">{{ trim((string) $row->HangGPLX) ?: '—' }} | {{ trim((string) $row->HangDT) ?: '—' }}</td>
                                <td class="text-nowrap">{{ $row->NgayKG?->format('d/m/Y') ?? '—' }} - {{ $row->NgayBG?->format('d/m/Y') ?? '—' }}</td>
                                <td class="text-right">
                                    <a href="{{ route('pmgplxold.dm.hoc-vien.index', ['ma_kh' => $row->MaKH]) }}" target="_blank">
                                        {{ number_format((int) $row->so_hoc_vien) }}
                                    </a>
                                </td>
                                <td class="text-right">{{ $row->TongSoHV }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Không có dữ liệu (hoặc chưa kết nối DB bản cũ).</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $items->links() }}</div>
        </div>
    </div>
@endsection