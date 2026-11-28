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
            <div class="alert alert-info small py-2">
                Mẫu <code>phu-luc-5-dat-cabin.goc.docx</code> (phụ lục 5).
                Số học viên lấy từ
                <a href="{{ route('daotao.pdt.dat.tong-hop-hoc-vien') }}" target="_blank" rel="noopener">Tổng hợp học viên DAT</a>
                — phiên <strong>đạt</strong>, lọc <strong>Đạt CT = Đạt</strong>, theo khoá và khoảng ngày phiên.
            </div>

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('qldtoto.4-dat-cabin.export') }}" id="form-phu-luc-5">
                @csrf
                <div class="form-row">
                    <div class="form-group col-md-5">
                        <label for="ma_khoa_hoc">Mã khóa học (DAT)</label>
                        <select class="form-control form-control-sm" id="ma_khoa_hoc" name="ma_khoa_hoc" required>
                            <option value="">— Chọn khóa —</option>
                            @foreach ($khoaHocOptions as $kh)
                                <option value="{{ $kh->MaKhoaHoc }}" @selected(old('ma_khoa_hoc', $ma_khoa_hoc) === $kh->MaKhoaHoc)>
                                    {{ $kh->TenKhoaHoc ?: $kh->MaKhoaHoc }} ({{ $kh->MaKhoaHoc }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="tu_ngay">Từ ngày (phiên)</label>
                        <input type="date" class="form-control form-control-sm" id="tu_ngay" name="tu_ngay"
                               value="{{ old('tu_ngay', $tu_ngay) }}" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="den_ngay">Đến ngày (phiên)</label>
                        <input type="date" class="form-control form-control-sm" id="den_ngay" name="den_ngay"
                               value="{{ old('den_ngay', $den_ngay) }}" required>
                    </div>
                </div>

                <div class="mb-3">
                    <button type="button" class="btn btn-outline-primary btn-sm" id="btn-loc-pl5">Lọc</button>
                    <a href="{{ route('qldtoto.4-dat-cabin.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>

                @if ($dem !== null)
                    <div class="border rounded p-3 bg-light small mb-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
                            <strong>Xem trước số liệu (Đạt CT)</strong>
                            <a href="{{ $linkTongHop }}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">
                                Mở tổng hợp học viên
                            </a>
                        </div>
                        <div class="row text-center">
                            <div class="col-3"><span class="text-muted d-block">B số tự động</span><strong>{{ $dem['B'] }}</strong></div>
                            <div class="col-3"><span class="text-muted d-block">B số cơ khí</span><strong>{{ $dem['B.01'] }}</strong></div>
                            <div class="col-3"><span class="text-muted d-block">C1</span><strong>{{ $dem['C1'] }}</strong></div>
                            <div class="col-3"><span class="text-muted d-block">Tổng</span><strong>{{ $dem['tong'] }}</strong></div>
                        </div>
                        @if (($dem['khac'] ?? 0) > 0)
                            <p class="mb-0 mt-2 text-warning">Có {{ $dem['khac'] }} HV hạng khác (không ghi vào 3 cột B/C1 trên phụ lục).</p>
                        @endif
                    </div>
                @endif

                <button type="submit" class="btn btn-navy">Xuất Word — Phụ lục 5</button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function ($) {
    $(function () {
        var $sel = $('#ma_khoa_hoc');
        $sel.select2({
            theme: 'bootstrap4',
            width: '100%',
            allowClear: true,
            placeholder: '— Chọn khóa —',
            dropdownParent: $sel.closest('.form-group'),
            language: { noResults: function () { return 'Không tìm thấy'; } },
        });

        $('#btn-loc-pl5').on('click', function () {
            var ma = $sel.val();
            if (!ma) {
                $sel.focus();
                return;
            }
            var params = new URLSearchParams({
                ma_khoa_hoc: ma,
                tu_ngay: $('#tu_ngay').val() || '',
                den_ngay: $('#den_ngay').val() || ''
            });
            window.location = @json(route('qldtoto.4-dat-cabin.index')) + '?' + params.toString();
        });
    });
})(jQuery);
</script>
@endpush