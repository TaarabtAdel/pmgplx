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
        <div class="card-header">{{ $item['title'] }}</div>
        <div class="card-body">
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <p class="text-muted small">
                Xuất lịch theo mẫu <strong>lich C1K06…</strong>: mỗi giáo viên một sheet thực hành + sheet lịch chung LT+TH.
                Mốc ngày dùng cùng logic với
                <a href="{{ $linkLichChung }}">Lịch dự kiến → Lý thuyết</a>
                (gợi ý từ <a href="{{ $linkLichGv }}" target="_blank">lịch giáo viên</a>,
                <a href="{{ $linkLichXeTap }}" target="_blank">lịch xe tập</a>).
                Sheet TH từng GV: hạng <strong>C</strong> (mẫu C1K06) hoặc <strong>B số tự động</strong> (mẫu B01 — cột C→H như file <em>THUC HANH B11</em>).
                Hạng B sàn bổ sung sau.
            </p>

            <form method="GET" action="{{ route('qldtoto.lich-hoc-lt-th-tung-giao-vien.index') }}" class="form-row align-items-end mb-3">
                <div class="form-group col-md-5 mb-0">
                    <label for="ma_kh">Khoá (PMGPLX)</label>
                    <select class="form-control form-control-sm sel-khoa-pmgplx" id="ma_kh" name="ma_kh" data-placeholder="— Chọn khoá —">
                        <option value=""></option>
                        @foreach ($khoaOptions as $kh)
                            <option value="{{ $kh->MaKH }}" @selected($maKh === $kh->MaKH)>
                                {{ $kh->TenKH }} ({{ $kh->MaKH }})
                            </option>
                        @endforeach
                    </select>
                </div>
                @if ($maKh !== '' && $hang !== '')
                    <div class="form-group col-md-3 mb-0">
                        <label class="d-block">Hạng đào tạo</label>
                        <p class="form-control-plaintext small py-1 mb-0 font-weight-bold">
                            {{ $nhanHang[$hang] ?? $hang }}
                        </p>
                    </div>
                @endif
                <div class="form-group col-md-2 mb-0">
                    <button type="submit" class="btn btn-sm btn-primary btn-block">Chọn</button>
                </div>
            </form>

            @if ($maKh === '')
                <p class="text-muted mb-0">Chọn khoá để xem danh sách giáo viên và xuất file.</p>
            @else
                @if ($danhSachGv === [])
                    <div class="alert alert-warning py-2 small">
                        Chưa có lịch thực hành giáo viên — nhập tại <a href="{{ $linkLichGv }}" target="_blank">PMGPLX → Lịch giáo viên</a>.
                    </div>
                @else
                    <h6 class="small font-weight-bold mb-2">Giáo viên trong khoá ({{ count($danhSachGv) }})</h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered mb-0" style="font-size: 0.8125rem;">
                            <thead class="thead-light">
                                <tr>
                                    <th>Giáo viên</th>
                                    <th>Xe tập lái</th>
                                    <th class="text-right">Buổi lịch</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($danhSachGv as $gv)
                                    <tr>
                                        <td>{{ $gv['ten_gv'] }} <span class="text-muted">({{ $gv['ma_gv'] }})</span></td>
                                        <td>{{ $gv['bien_so'] !== '' ? $gv['bien_so'] : '—' }}</td>
                                        <td class="text-right">{{ count($gv['buoi'] ?? []) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <form method="POST" action="{{ route('qldtoto.lich-hoc-lt-th-tung-giao-vien.export-workbook') }}" id="form-xuat-tung-gv">
                    @csrf
                    <input type="hidden" name="ma_kh" value="{{ $maKh }}">
                    <div class="form-row mb-3">
                        <div class="form-group col-md-6">
                            <label for="ten_khoa_doc">Tên khoá trên file (tiêu đề)</label>
                            <input type="text" class="form-control form-control-sm" id="ten_khoa_doc" name="ten_khoa_doc"
                                   value="{{ old('ten_khoa_doc', $tenKhoaDoc) }}">
                        </div>
                    </div>

                    <h6 class="small font-weight-bold mb-2">Mốc ngày (sheet lịch chung LT+TH)</h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered mb-0" style="font-size: 0.8125rem;">
                            <thead class="thead-light">
                                <tr>
                                    <th>Mốc trên file</th>
                                    <th style="width: 11rem">Ngày</th>
                                    <th>Gợi ý · nguồn</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (\App\Support\QldtOto\LichHocLyThuyetThucHanhMocMoTa::KEYS as $key)
                                    <tr>
                                        <td class="small">{{ $nhanMocTrenFile[$key] ?? $key }}</td>
                                        <td>
                                            <input type="date" class="form-control form-control-sm ngay-moc" name="{{ $key }}"
                                                   value="{{ $formNgay[$key] ?? '' }}">
                                        </td>
                                        <td class="text-muted small">{{ $goiYHienThi[$key] ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @include('QldtOto.lich-hoc-ly-thuyet-thuc-hanh.partials.preview-bang', [
                        'previewDong' => $previewDong,
                        'hang' => $hang,
                        'tenKhoaDoc' => $tenKhoaDoc,
                    ])

                    @include('QldtOto.lich-hoc-ly-thuyet-thuc-hanh.partials.bang-lich-gv-xuat', [
                        'danhSachGv' => $danhSachGv,
                        'hang' => $hang,
                    ])

                    @php
                        $duocXuat = \App\Support\QldtOto\LichHocLyThuyetThucHanhTungGiaoVienExporter::hangDuocHoTro($hang)
                            && $danhSachGv !== [];
                    @endphp
                    <button type="submit" class="btn btn-success btn-sm" @disabled(! $duocXuat)>
                        ⬇ Xuất một file Excel (tất cả GV + lịch chung)
                    </button>
                    <button type="submit" formaction="{{ route('qldtoto.lich-hoc-lt-th-tung-giao-vien.export-zip') }}"
                            class="btn btn-outline-success btn-sm ml-1" @disabled(! $duocXuat)>
                        ⬇ Xuất ZIP (mỗi GV một file)
                    </button>
                </form>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $('#ma_kh').select2({
        theme: 'bootstrap4',
        placeholder: 'Tìm khoá học...',
        allowClear: true,
        width: '100%'
    });
</script>
@endpush