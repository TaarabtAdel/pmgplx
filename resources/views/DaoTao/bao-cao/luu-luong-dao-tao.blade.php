@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Báo cáo Lưu lượng đào tạo')

@push('styles')
<style>
    .luu-luong-intro {
        color: #444;
        margin-bottom: 1rem;
        line-height: 1.55;
    }
    .luu-luong-intro h5 {
        color: #1f4e79;
        font-weight: 700;
        margin-bottom: 0.35rem;
    }
    .luu-luong-summary {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.25rem;
        background: #f7f9fb;
        border: 1px solid #c5cdd6;
        border-radius: 0.2rem;
        margin-bottom: 1rem;
    }
    .luu-luong-summary .summary-label {
        color: #555;
        font-size: 0.9rem;
        margin-bottom: 0.25rem;
    }
    .luu-luong-summary .summary-meta {
        color: #666;
        font-size: 0.85rem;
        margin-top: 0.35rem;
    }
    .luu-luong-summary .summary-count {
        font-size: 2rem;
        font-weight: 700;
        color: #1f4e79;
        line-height: 1.2;
    }
    .luu-luong-summary .summary-count .han-muc {
        font-size: 1.15rem;
        font-weight: 600;
        color: #6c757d;
    }
    .luu-luong-summary .status-wrap {
        text-align: right;
    }
    .luu-luong-summary .status-wrap .status-label {
        font-size: 0.85rem;
        color: #666;
        margin-bottom: 0.35rem;
    }
    .badge-trong-han-muc {
        background: #28a745;
        color: #fff;
        font-size: 0.9rem;
        font-weight: 600;
        padding: 0.45rem 0.85rem;
        border-radius: 1rem;
    }
    .badge-vuot-han-muc {
        background: #dc3545;
        color: #fff;
        font-size: 0.9rem;
        font-weight: 600;
        padding: 0.45rem 0.85rem;
        border-radius: 1rem;
    }
    .ky-hieu-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem 1.25rem;
        margin-bottom: 1rem;
        font-size: 0.875rem;
    }
    .ky-hieu-legend-item {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }
    .ky-hieu-badge {
        display: inline-block;
        min-width: 1.6rem;
        padding: 0.15rem 0.4rem;
        font-weight: 700;
        text-align: center;
        border-radius: 0.15rem;
        border: 1px solid rgba(0, 0, 0, 0.08);
        line-height: 1.3;
    }
    .ky-hieu-h {
        background: #cce5ff;
        color: #004085;
    }
    .ky-hieu-t,
    .ky-hieu-d {
        background: #fff3cd;
        color: #856404;
    }
    .ky-hieu-kiem {
        background: #f8d7da;
        color: #721c24;
    }
</style>
@endpush

@section('content')
    <div class="luu-luong-intro">
        <h5>Tra cứu lưu lượng theo thời điểm</h5>
        <p class="mb-0">
            Dữ liệu từ bảng <strong>TienDoDaoTao</strong> (DB MANHLINH — nhập file tiến độ đào tạo).
            Tại ngày kiểm tra, lấy các lớp có tuần đào tạo (<code>TuNgay</code> → <code>DenNgay</code>) bao trùm ngày đó,
            cộng <strong>Số lượng học viên</strong> theo hạng GPLX (<strong>B</strong>, <strong>B01</strong>, <strong>C</strong> — suy ra từ mã khóa-lớp).
        </p>
    </div>

    <div class="card card-panel">
        <div class="card-header">Tra cứu lưu lượng theo thời điểm</div>
        <div class="card-body">
            <form method="GET" action="{{ route('daotao.pdt.bc.luu-luong-dao-tao') }}">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-3">
                        <label for="ngay_kiem_tra">Ngày kiểm tra</label>
                        <input type="date"
                               class="form-control form-control-sm"
                               id="ngay_kiem_tra"
                               name="ngay_kiem_tra"
                               value="{{ $filters['ngay_kiem_tra'] }}"
                               required>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="ky_hieu">Ký hiệu</label>
                        <select class="form-control form-control-sm" id="ky_hieu" name="ky_hieu">
                            <option value="" @selected($filters['ky_hieu'] === '')>Tất cả</option>
                            @foreach ($kyHieuOptions as $option)
                                <option value="{{ $option['value'] }}" @selected($filters['ky_hieu'] === $option['value'])>{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label for="hang_gplx">Hạng GPLX</label>
                        <select class="form-control form-control-sm" id="hang_gplx" name="hang_gplx">
                            <option value="" @selected($filters['hang_gplx'] === '')>Tất cả</option>
                            @foreach ($hangOptions as $hang)
                                <option value="{{ $hang }}" @selected($filters['hang_gplx'] === $hang)>{{ $hang }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary btn-block">Tra cứu</button>
                        <a href="{{ route('daotao.pdt.bc.luu-luong-dao-tao', ['lam_moi' => 1]) }}"
                           class="btn btn-sm btn-outline-secondary btn-block mt-1"
                           title="Làm mới">↻</a>
                    </div>
                </div>
            </form>

            @if ($searched && $summary)
                <div class="luu-luong-summary mt-3">
                    <div>
                        <div class="summary-label">
                            Học viên đang học ngày {{ $summary['ngay_hien_thi'] }}
                            @if (! empty($summary['ky_hieu_loc']))
                                · ký hiệu <strong>{{ $summary['ky_hieu_loc'] }}</strong>
                            @endif
                        </div>
                        <div class="summary-count">
                            {{ number_format($summary['tong_so']) }}
                            <span class="han-muc">/ {{ number_format($summary['han_muc']) }}</span>
                        </div>
                        <div class="summary-meta">
                            {{ number_format($summary['so_lop']) }} lớp đang trong tuần đào tạo
                            · {{ $summary['han_muc_mo_ta'] }}
                        </div>
                    </div>
                    <div class="status-wrap">
                        <div class="status-label">Trạng thái</div>
                        @if ($summary['trong_han_muc'])
                            <span class="badge-trong-han-muc">Trong hạn mức</span>
                        @else
                            <span class="badge-vuot-han-muc">Vượt hạn mức</span>
                        @endif
                    </div>
                </div>

                <h6 class="section-title mt-4 mb-2">Tổng hợp theo hạng GPLX</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-striped table-hover table-data mb-0">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Hạng GPLX</th>
                                <th>Số học viên</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $index => $row)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $row['hang'] }}</td>
                                    <td>{{ number_format($row['so_luong']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4">
                                        Không có lớp nào trong tuần đào tạo tại ngày này
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if (count($items) > 0)
                            <tfoot>
                                <tr class="font-weight-bold">
                                    <td colspan="2" class="text-right">Tổng cộng</td>
                                    <td>{{ number_format($summary['tong_so']) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

                <h6 class="section-title mt-4 mb-2">Các lớp đang trong tuần đào tạo</h6>
                <div class="ky-hieu-legend">
                    @foreach (\App\Models\DaoTao\TienDoDaoTao::kyHieuLegend() as $item)
                        <span class="ky-hieu-legend-item">
                            <span class="ky-hieu-badge {{ $item['css_class'] }}">{{ $item['token'] }}</span>
                            {{ $item['label'] }}
                        </span>
                    @endforeach
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-striped table-hover table-data mb-0">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Mã khóa-lớp</th>
                                <th>Năm</th>
                                <th>Số HV</th>
                                <th>GV dạy</th>
                                <th>Hạng</th>
                                <th>Tuần (TG)</th>
                                <th>Ký hiệu</th>
                                <th>Giải thích</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($classes as $index => $lop)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        @php
                                            $khoaDaoTaoId = \App\Models\DaoTao\KhoaDaoTao::query()
                                                ->where('TenKhoa', \App\Models\DaoTao\KhoaDaoTao::normalizeTenKhoa($lop['ma_khoa_lop']))
                                                ->value('Id');
                                        @endphp
                                        @if ($khoaDaoTaoId)
                                            <a href="{{ route('daotao.pdt.phan-cong-dao-tao.danh-sach', array_merge(
                                                ['khoa_dao_tao_id' => $khoaDaoTaoId, 'from' => 'luu-luong-dao-tao'],
                                                array_filter([
                                                    'ngay_kiem_tra' => $filters['ngay_kiem_tra'],
                                                    'ky_hieu' => $filters['ky_hieu'],
                                                    'hang_gplx' => $filters['hang_gplx'],
                                                ], fn ($value) => $value !== '' && $value !== null)
                                            )) }}"
                                               title="Xem phân công đào tạo">
                                                <strong>{{ $lop['ma_khoa_lop'] }}</strong>
                                            </a>
                                        @else
                                            <strong>{{ $lop['ma_khoa_lop'] }}</strong>
                                        @endif
                                    </td>
                                    <td>{{ $lop['nam_hoc'] ?? '—' }}</td>
                                    <td>{{ number_format($lop['so_hoc_vien']) }}</td>
                                    <td>{{ $lop['giao_vien_day'] ?: '—' }}</td>
                                    <td>{{ $lop['hang'] }}</td>
                                    <td>{{ $lop['tuan_tu'] }} – {{ $lop['tuan_den'] }}</td>
                                    <td>
                                        @if (! empty($lop['ky_hieu_parts']))
                                            @include('DaoTao._ky-hieu-badges', ['parts' => $lop['ky_hieu_parts']])
                                        @else
                                            {{ $lop['ky_hieu'] ?: '—' }}
                                        @endif
                                    </td>
                                    <td class="small">{{ $lop['giai_thich'] ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        Không có lớp nào trong tuần đào tạo tại ngày này
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if (count($classes) > 0)
                            <tfoot>
                                <tr class="font-weight-bold">
                                    <td colspan="3" class="text-right">Tổng số học viên</td>
                                    <td>{{ number_format($summary['tong_so']) }}</td>
                                    <td colspan="5"></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
