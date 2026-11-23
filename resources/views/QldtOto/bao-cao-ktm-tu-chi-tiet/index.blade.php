@extends('PMGPLX.layouts.quan-ly')

@section('title', $meta['title'].' — '.$item['title'])

@section('content')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-white border py-2 small mb-3">
            <li class="breadcrumb-item"><a href="{{ route('qldtoto.index') }}">QLĐT Ô TÔ</a></li>
            @foreach (array_slice($duongDan, 1) as $buoc)
                <li class="breadcrumb-item">{{ $buoc }}</li>
            @endforeach
            <li class="breadcrumb-item active" aria-current="page">{{ $meta['title'] }}</li>
        </ol>
    </nav>

    @if (session('error'))
        <div class="alert alert-danger py-2">{{ session('error') }}</div>
    @endif

    <div class="card card-panel">
        <div class="card-header">{{ $meta['title'] }} <span class="small text-muted">(mẫu sheet «{{ $meta['sheetLabel'] }}»)</span></div>
        <div class="card-body">
            <p class="small text-muted">
                Tích chọn một hoặc nhiều <strong>báo cáo chi tiết</strong> đã ghép &amp; lưu. Hệ thống cộng số liệu theo hạng (kỳ thường / thi lại) rồi xuất Excel đúng file chuẩn.
                <a href="{{ route('qldtoto.bao-cao-chi-tiet.index') }}">Quản lý báo cáo chi tiết</a>
            </p>

            @if ($danhSach->isEmpty())
                <p class="text-muted mb-0">Chưa có báo cáo chi tiết. Tạo và lưu tại menu <em>Báo cáo chi tiết</em> trước.</p>
            @else
                <form method="POST" action="{{ route($exportRoute) }}">
                    @csrf
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                                <tr>
                                    <th style="width:2.5rem;"></th>
                                    <th>ID</th>
                                    <th>Ngày kỳ thi</th>
                                    <th>Tên khóa</th>
                                    <th>Thi lại</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($danhSach as $ky)
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="record_ids[]" value="{{ $ky->id }}"
                                                   @checked(in_array($ky->id, $idsChon, true))>
                                        </td>
                                        <td>{{ $ky->id }}</td>
                                        <td>{{ $ky->NgayKyThi->format('d/m/Y') }}</td>
                                        <td class="small">{{ $ky->TenKhoaThi }}</td>
                                        <td class="small">
                                            @if ($ky->LaThiLai)
                                                <span class="badge badge-info">Thi lại</span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <h6 class="border-bottom pb-1 mt-2">Tham số in báo cáo</h6>
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label class="small">Ngày lập / ngày ghi trên báo cáo</label>
                            <input type="date" name="ngay_lap" class="form-control form-control-sm" value="{{ old('ngay_lap', now()->format('Y-m-d')) }}">
                        </div>
                        <div class="form-group col-md-3">
                            <label class="small">Ngày sát hạch (hiển thị)</label>
                            <input type="date" name="ngay_thi" class="form-control form-control-sm"
                                   value="{{ old('ngay_thi') }}">
                            <small class="text-muted">Để trống = ngày kỳ thi của báo cáo đã chọn (dd/mm/yyyy trên file Excel).</small>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="small">Trung tâm sát hạch</label>
                            <input type="text" name="sat_hach" class="form-control form-control-sm" maxlength="255"
                                   value="{{ old('sat_hach', $cfg['sat_hach'] ?? '') }}">
                        </div>
                    </div>

                    @if ($loaiXuat !== 'bc_chung')
                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label class="small">Ngày họp</label>
                                <input type="text" name="ngay_hop" class="form-control form-control-sm" placeholder="dd/mm/yyyy" value="{{ old('ngay_hop') }}">
                            </div>
                            <div class="form-group col-md-2">
                                <label class="small">Giờ họp</label>
                                <input type="text" name="gio_hop" class="form-control form-control-sm" value="{{ old('gio_hop', $cfg['bbth']['gio_hop'] ?? '7h30') }}">
                            </div>
                            <div class="form-group col-md-3">
                                <label class="small">Số QĐ thành lập HĐ</label>
                                <input type="text" name="so_qd" class="form-control form-control-sm" value="{{ old('so_qd', $cfg['bbth']['so_qd'] ?? '') }}">
                            </div>
                            <div class="form-group col-md-3">
                                <label class="small">Ngày QĐ</label>
                                <input type="text" name="ngay_qd" class="form-control form-control-sm" placeholder="dd/mm/yyyy" value="{{ old('ngay_qd', $cfg['bbth']['ngay_qd'] ?? '') }}">
                            </div>
                            <div class="form-group col-md-12">
                                <label class="small">Địa điểm họp</label>
                                <input type="text" name="dia_diem_hop" class="form-control form-control-sm"
                                       value="{{ old('dia_diem_hop', $cfg['bbth']['dia_diem_hop'] ?? '') }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="small">Tên khóa (đoạn mở đầu)</label>
                            <input type="text" name="ten_khoa" class="form-control form-control-sm" maxlength="500"
                                   placeholder="Để trống = ghép tên từ các báo cáo đã chọn" value="{{ old('ten_khoa') }}">
                        </div>
                        @if ($loaiXuat === 'bb_hop')
                            <div class="form-group">
                                <label class="small">Tiêu đề dòng A6 (tên khóa trên biên bản họp)</label>
                                <input type="text" name="tieu_de_hop" class="form-control form-control-sm" maxlength="500"
                                       placeholder="VD: HỌP THÔNG QUA KẾT QUẢ … KHÓA B01K39 VÀ KHÓA B01K40" value="{{ old('tieu_de_hop') }}">
                            </div>
                        @endif
                        <div class="form-group">
                            <label class="small">Đoạn mở đầu (ô A8) — để trống = sinh tự động</label>
                            <textarea name="doan_mo_dau" class="form-control form-control-sm" rows="3" maxlength="5000">{{ old('doan_mo_dau') }}</textarea>
                        </div>
                    @endif

                    <button type="submit" class="btn btn-navy btn-sm mt-2">Xuất Excel {{ $meta['sheetLabel'] }}</button>
                </form>
            @endif
        </div>
    </div>
@endsection