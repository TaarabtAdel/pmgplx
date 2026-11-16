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
            <p class="text-muted small mb-3">
                Mẫu <strong>kế hoạch đào tạo</strong> (C / B sàn / B STĐ). Phần II lịch toàn khóa dùng ký tự
                <code>LT</code>, <code>HI</code>, <code>CB</code>, <code>TH</code>, <code>KT</code>, <code>DP</code> — không dùng shape.
                Mốc ngày giống <a href="{{ $maKh !== '' ? $linkLichLtTh : '#' }}">lịch LT+TH dự kiến</a>.
            </p>

            <form method="GET" action="{{ route('qldtoto.ke-hoach-dao-tao.index') }}" id="form-chon-khoa" class="form-row align-items-end mb-3">
                <div class="form-group col-md-8 mb-0">
                    <label for="ma_kh">Khoá (PMGPLX)</label>
                    <select class="form-control form-control-sm sel-khoa-pmgplx" id="ma_kh" name="ma_kh" data-placeholder="— Chọn khoá —">
                        <option value=""></option>
                        @foreach ($khoaOptions as $kh)
                            <option value="{{ $kh->MaKH }}" @selected($maKh === $kh->MaKH)>{{ $kh->TenKH }} ({{ $kh->MaKH }})</option>
                        @endforeach
                    </select>
                </div>
            </form>

            @if ($formMeta === null)
                <p class="text-muted mb-0">Chọn khoá để lập kế hoạch đào tạo.</p>
            @else
                @if (session('error'))
                    <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger py-2 small">
                        <ul class="mb-0 pl-3">
                            @foreach ($errors->all() as $msg)
                                <li>{{ $msg }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @php $m = $formMeta; $v = fn (string $key) => old($key, $m[$key] ?? ''); @endphp

                <form method="POST" action="{{ route('qldtoto.ke-hoach-dao-tao.export') }}">
                    @csrf
                    <input type="hidden" name="ma_kh" value="{{ $m['ma_kh'] }}">

                    <div class="form-row mb-3">
                        <div class="form-group col-md-2">
                            <label for="khoa_so">Khóa (số)</label>
                            <input type="text" class="form-control form-control-sm" id="khoa_so" name="khoa_so"
                                   value="{{ $v('khoa_so') }}" maxlength="20" required>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="hang_hien_thi">Hạng (trên file)</label>
                            <input type="text" class="form-control form-control-sm" id="hang_hien_thi" name="hang_hien_thi"
                                   value="{{ $v('hang_hien_thi') }}" maxlength="60" required>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="so_hv">Số học viên</label>
                            <input type="number" class="form-control form-control-sm" id="so_hv" name="so_hv"
                                   value="{{ $v('so_hv') }}" min="1" max="999" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label class="d-block">Hạng hệ thống</label>
                            <p class="form-control-plaintext small mb-0 font-weight-bold">{{ $nhanHang[$hang] ?? $hang }}</p>
                        </div>
                    </div>

                    <h6 class="small font-weight-bold mb-2">Mốc ngày (lịch toàn khóa)</h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered mb-0" style="font-size: 0.8125rem;">
                            <thead class="thead-light">
                                <tr>
                                    <th>Mốc</th>
                                    <th style="width: 11rem">Ngày</th>
                                    <th>Gợi ý</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (\App\Support\QldtOto\LichHocLyThuyetThucHanhMocMoTa::KEYS as $key)
                                    <tr>
                                        <td class="small">{{ $nhanMocTrenFile[$key] ?? $key }}</td>
                                        <td>
                                            <input type="date" class="form-control form-control-sm" name="{{ $key }}"
                                                   value="{{ old($key, $formNgay[$key] ?? '') }}">
                                        </td>
                                        <td class="text-muted small">{{ $goiYHienThi[$key] ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <h6 class="small font-weight-bold mb-2">III — Giáo viên / xe tập (tuỳ chỉnh)</h6>
                    <div class="form-row mb-3">
                        <div class="form-group col-md-2">
                            <label class="small">GV khóa</label>
                            <input type="number" class="form-control form-control-sm" name="so_gv_khoa"
                                   value="{{ $v('so_gv_khoa') }}" min="0">
                        </div>
                        <div class="form-group col-md-2">
                            <label class="small">GV đang SD</label>
                            <input type="number" class="form-control form-control-sm" name="so_gv_dang_sd"
                                   value="{{ $v('so_gv_dang_sd') }}" min="0">
                        </div>
                        <div class="form-group col-md-2">
                            <label class="small">Tổng GV</label>
                            <input type="number" class="form-control form-control-sm" name="so_gv_tong"
                                   value="{{ $v('so_gv_tong') }}" min="0">
                        </div>
                        <div class="form-group col-md-2">
                            <label class="small">Xe khóa</label>
                            <input type="number" class="form-control form-control-sm" name="so_xe_khoa"
                                   value="{{ $v('so_xe_khoa') }}" min="0">
                        </div>
                        <div class="form-group col-md-2">
                            <label class="small">Xe đang SD</label>
                            <input type="number" class="form-control form-control-sm" name="so_xe_dang_sd"
                                   value="{{ $v('so_xe_dang_sd') }}" min="0">
                        </div>
                        <div class="form-group col-md-2">
                            <label class="small">Tổng xe</label>
                            <input type="number" class="form-control form-control-sm" name="so_xe_tong"
                                   value="{{ $v('so_xe_tong') }}" min="0">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-sm btn-success">Xuất Word (.docx)</button>
                </form>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function ($) {
    $(function () {
        const $khoa = $('#ma_kh');
        $khoa.select2({
            theme: 'bootstrap4',
            width: '100%',
            allowClear: true,
            placeholder: $khoa.data('placeholder') || '— Chọn khoá —',
            dropdownParent: $khoa.closest('.form-group'),
            language: { noResults: function () { return 'Không tìm thấy khoá học'; } },
        });
        $khoa.on('select2:select', function () {
            document.getElementById('form-chon-khoa').submit();
        });
    });
})(jQuery);
</script>
@endpush