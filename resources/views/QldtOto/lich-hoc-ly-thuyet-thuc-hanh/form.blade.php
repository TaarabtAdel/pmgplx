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
                <strong>Bước 1 — lịch chung cả khoá</strong> (một file Excel). Xuất riêng từng giáo viên làm sau.
                <strong>Ngày khai giảng</strong> lấy <strong>Ngày KG</strong> trên <a href="{{ $linkDanhMucKhoaHoc }}" target="_blank">Danh mục khoá học</a>.
                Các mốc thực hành gợi ý từ <a href="{{ $linkLichGv }}" target="_blank">Lịch giáo viên</a>;
                mốc thiếu hoặc khoá cũ (lịch GV chưa chi tiết) bổ sung từ <a href="{{ $linkLichXeTap }}" target="_blank">Lịch xe tập</a> (<code>GhiChu</code>: Hình, Dat, Ca bin, Ôn…).
                Mẫu: <strong>C</strong>, <strong>B sàn</strong> (BK56), <strong>B tự động</strong> (B01K39).
            </p>

            <form method="GET" action="{{ route('qldtoto.lich-hoc-ly-thuyet-thuc-hanh.index') }}" class="form-row align-items-end mb-3">
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
                <p class="text-muted mb-0">Chọn khoá để xem mốc ngày và xuất lịch.</p>
            @else
                @if (($goiY['so_buoi_lich'] ?? '0') === '0' && ($goiY['so_buoi_lich_xe'] ?? '0') === '0')
                    <div class="alert alert-warning py-2 small">
                        Chưa có lịch giáo viên hay lịch xe tập — nhập trên PMGPLX hoặc điền ngày thủ công bên dưới.
                    </div>
                @elseif (($goiY['so_buoi_lich'] ?? '0') === '0' && ($goiY['so_buoi_lich_xe'] ?? '0') !== '0')
                    <div class="alert alert-info py-2 small">
                        Lịch GV trống hoặc thưa — đang gợi ý chủ yếu từ <a href="{{ $linkLichXeTap }}" target="_blank">lịch xe tập</a> ({{ $goiY['so_buoi_lich_xe'] }} buổi).
                    </div>
                @endif
                <form method="POST" action="{{ route('qldtoto.lich-hoc-ly-thuyet-thuc-hanh.export') }}">
                    @csrf
                    <input type="hidden" name="ma_kh" value="{{ $maKh }}">

                    <div class="form-row mb-3">
                        <div class="form-group col-md-6">
                            <label for="ten_khoa_doc">Tên khoá trên file (dòng tiêu đề)</label>
                            <input type="text" class="form-control form-control-sm" id="ten_khoa_doc" name="ten_khoa_doc"
                                   value="{{ old('ten_khoa_doc', $tenKhoaDoc) }}">
                        </div>
                    </div>

                    <h6 class="small font-weight-bold mb-2">Điều chỉnh mốc ngày (trước khi xuất)</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-bordered mb-0" style="font-size: 0.8125rem;">
                            <thead class="thead-light">
                                <tr>
                                    <th>Mốc trên file (công thức / ô Excel)</th>
                                    <th style="width: 11rem">Ngày (sửa nếu cần)</th>
                                    <th>Gợi ý · nguồn</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (\App\Support\QldtOto\LichHocLyThuyetThucHanhMocMoTa::KEYS as $key)
                                    <tr>
                                        <td class="small">{{ $nhanMocTrenFile[$key] ?? $key }}</td>
                                        <td>
                                            <input type="date" class="form-control form-control-sm" name="{{ $key }}"
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

                    @php
                        $duocXuat = in_array($hang, [
                            \App\Support\QldtOto\LichHocLyThuyetThucHanhLichNgay::HANG_C,
                            \App\Support\QldtOto\LichHocLyThuyetThucHanhLichNgay::HANG_B_SAN,
                            \App\Support\QldtOto\LichHocLyThuyetThucHanhLichNgay::HANG_B_TU_DONG,
                        ], true);
                    @endphp
                    <button type="submit" class="btn btn-success" @disabled(! $duocXuat)>
                        ⬇ Xuất Excel lịch chung ({{ $nhanHang[$hang] ?? $hang }})
                    </button>
                    @if (str_starts_with((string) ($goiY['goi_y_nguon'] ?? ''), 'b_san_offset'))
                        <span class="small text-muted ml-2">Gợi ý B sàn: BD thực hành + quy tắc BK56; bổ sung từ lịch xe khi thiếu.</span>
                    @elseif (str_starts_with((string) ($goiY['goi_y_nguon'] ?? ''), 'b01_offset'))
                        <span class="small text-muted ml-2">Gợi ý B01: BD thực hành (lịch GV môn 6 hoặc ngày xe sớm nhất) + quy tắc B01K39; mốc có trên lịch xe thì ưu tiên theo <code>GhiChu</code>.</span>
                    @elseif (str_contains((string) ($goiY['goi_y_nguon'] ?? ''), 'lich_xe_tap'))
                        <span class="small text-muted ml-2">Đã bổ sung từ lịch xe tập ({{ $goiY['so_buoi_lich_xe'] ?? 0 }} buổi).</span>
                    @endif
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
