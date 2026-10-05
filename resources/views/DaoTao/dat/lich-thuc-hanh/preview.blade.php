@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Xem trước lịch TH')

@push('styles')
<style>
    .lich-excel-wrap { max-height: 80vh; overflow: auto; border: 1px solid #dee2e6; }
    .lich-excel-table { font-size: 11px; border-collapse: separate; border-spacing: 0; min-width: max-content; }
    .lich-excel-table th, .lich-excel-table td { border: 1px solid #dee2e6; padding: 2px 4px; vertical-align: middle; white-space: nowrap; color: #212529; }
    .lich-excel-table thead th { position: sticky; top: 0; z-index: 2; background: #f5f5f5; }
    .lich-excel-table .gv-group-head { background: #e3f2fd; text-align: center; font-weight: 600; }
    .lich-excel-table .col-subhead { background: #fafafa; font-weight: 600; font-size: 10px; }
    .lich-excel-table .col-ngay { position: sticky; left: 0; z-index: 1; background: #fff; font-weight: 600; }
    .lich-excel-table thead .col-ngay { z-index: 3; background: #f5f5f5; }
</style>
@endpush

@section('content')
    @php
        use App\Support\DaoTao\LichThucHanh\LichCellHienThi;
        use App\Support\DaoTao\LichThucHanh\LichExcelExporter;
        use App\Support\DaoTao\LichThucHanh\LichNgay;

        $gvs = $lich['giao_viens'] ?? [];
        $dates = $lich['dates'] ?? [];
        $cells = $lich['cells'] ?? [];
    @endphp

    <div class="card card-panel mb-3">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <span>Xem trước lịch — {{ $duAn->MaKhoaHoc }}</span>
            <div class="d-flex flex-wrap mt-1 mt-md-0">
                <a href="{{ route('daotao.pdt.dat.lich-thuc-hanh.export', $duAn->Id) }}"
                   class="btn btn-sm btn-success mr-2 mb-1 mb-md-0">Tải Excel</a>
                <a href="{{ route('daotao.pdt.dat.lich-thuc-hanh.edit', $duAn->Id) }}" class="btn btn-sm btn-outline-primary mb-1 mb-md-0">← Cấu hình</a>
            </div>
        </div>
        <div class="card-body p-2">
            @if (session('success'))
                <div class="alert alert-success small py-2 mb-2">{{ session('success') }}</div>
            @endif

            @if (($kiemTra['warnings'] ?? []) !== [])
                <div class="alert alert-warning small py-2 mb-2">
                    @foreach ($kiemTra['warnings'] as $w)
                        <div>{{ $w }}</div>
                    @endforeach
                </div>
            @endif

            @if ($gvs === [] || $dates === [])
                <div class="alert alert-info small mb-0">Chưa có dữ liệu lưới lịch.</div>
            @else
                <div class="lich-excel-wrap">
                    <table class="lich-excel-table mb-0">
                        <thead>
                            <tr>
                                <th class="col-ngay" rowspan="2">STT</th>
                                @foreach ($gvs as $gv)
                                    <th colspan="7" class="gv-group-head">
                                        {{ $gv['ho_ten'] ?? '' }}<br>
                                        <span class="font-weight-normal">{{ $gv['bien_so'] ?? '' }}</span>
                                    </th>
                                @endforeach
                            </tr>
                            <tr>
                                @foreach ($gvs as $gv)
                                    <th class="col-subhead">THỨ</th>
                                    <th class="col-subhead">THỜI GIAN</th>
                                    <th class="col-subhead">SỐ GIỜ</th>
                                    <th class="col-subhead">NỘI DUNG</th>
                                    <th class="col-subhead">{{ ($gv['ma_gv'] ?? '').'-'.($gv['ma_khoa'] ?? '') }}</th>
                                    <th class="col-subhead">BẮT ĐẦU</th>
                                    <th class="col-subhead">KẾT THÚC</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dates as $iso)
                                <tr>
                                    <td class="col-ngay text-center">{{ $loop->iteration }}</td>
                                    @foreach ($gvs as $gv)
                                        @php
                                            $c = $cells[$iso.'|'.$gv['ma_gv']] ?? [];
                                            $bg = LichExcelExporter::cssBackgroundForCell($c, $iso, $lich);
                                        @endphp
                                        <td style="background: {{ $bg }}">{{ $c['thu'] ?? '' }}</td>
                                        <td style="background: {{ $bg }}">{{ LichNgay::hienThiNgay($iso) }}</td>
                                        <td style="background: {{ $bg }}">{{ $c['so_gio'] ?? '' }}</td>
                                        <td style="background: {{ $bg }}">{{ $c['bai'] ?? '' }}</td>
                                        <td style="background: {{ $bg }}">{{ LichCellHienThi::noiDungPhu($c) }}</td>
                                        <td style="background: {{ $bg }}">{{ $c['bat_dau'] ?? '' }}</td>
                                        <td style="background: {{ $bg }}">{{ $c['ket_thuc'] ?? '' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
