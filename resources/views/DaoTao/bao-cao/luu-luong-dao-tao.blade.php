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
</style>
@endpush

@section('content')
    <div class="luu-luong-intro">
        <h5>Tra cứu lưu lượng theo thời điểm</h5>
        <p class="mb-0">
            Tại thời điểm kiểm tra, lấy các khóa học có ngày khai giảng–ngày tốt nghiệp bao trùm thời điểm đó,
            sau đó đếm số học viên theo hạng GPLX (liên kết qua <code>MaKhoaHoc</code> trong hồ sơ học viên).
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
                    <div class="form-group col-md-2">
                        <label for="gio">Giờ</label>
                        <input type="time"
                               class="form-control form-control-sm"
                               id="gio"
                               name="gio"
                               value="{{ $filters['gio'] }}"
                               required>
                    </div>
                    <div class="form-group col-md-3">
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
                            Học viên đang học tại {{ $summary['gio_hien_thi'] }} - {{ $summary['ngay_hien_thi'] }}
                        </div>
                        <div class="summary-count">
                            {{ number_format($summary['tong_so']) }}
                            <span class="han-muc">/ {{ number_format($summary['han_muc']) }}</span>
                        </div>
                        <div class="summary-meta">
                            {{ number_format($summary['so_khoa_hoc']) }} khóa học phù hợp (NgayKG → NgayBG)
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
                                        Không có học viên trong khóa học phù hợp tại thời điểm này
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

                <h6 class="section-title mt-4 mb-2">Các khóa học đang học phù hợp</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-striped table-hover table-data mb-0">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Mã khóa học</th>
                                <th>Tên khóa</th>
                                <th>Ngày bắt đầu</th>
                                <th>Ngày kết thúc</th>
                                <th>Số học viên đang học</th>
                                <th>Hạng học</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($khoaHocs as $index => $kh)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $kh['ma_kh'] }}</td>
                                    <td>{{ $kh['ten_kh'] }}</td>
                                    <td>{{ $kh['ngay_bat_dau'] }}</td>
                                    <td>{{ $kh['ngay_ket_thuc'] }}</td>
                                    <td>{{ number_format($kh['so_hoc_vien']) }}</td>
                                    <td>{{ $kh['hang_hoc'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        Không có khóa học phù hợp tại thời điểm này
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if (count($khoaHocs) > 0)
                            <tfoot>
                                <tr class="font-weight-bold">
                                    <td colspan="5" class="text-right">Tổng số học viên</td>
                                    <td>{{ number_format($summary['tong_so']) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
