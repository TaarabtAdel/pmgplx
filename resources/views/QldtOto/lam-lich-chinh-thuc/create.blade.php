@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Chọn lịch dự kiến → chính thức')

@section('content')
    @include('QldtOto.lam-lich-chinh-thuc.partials.breadcrumb', ['trangCon' => 'Chọn từ dự kiến'])

    <div class="card card-panel">
        <div class="card-header">Chọn lịch dự kiến thực hành</div>
        <div class="card-body">
            @if (session('error'))
                <div class="alert alert-danger py-2">{{ session('error') }}</div>
            @endif
            @foreach ($errors->all() as $err)
                <div class="alert alert-danger py-2">{{ $err }}</div>
            @endforeach

            <p class="small text-muted mb-3">
                Chỉ hiện lịch dự kiến <strong>đã sinh buổi</strong> tại
                <a href="{{ route('qldtoto.lich-du-kien.thuc-hanh.index') }}">/qldt-oto/lich-du-kien/thuc-hanh</a>.
                Sau khi chọn, hệ thống sao chép lịch sang bản chính thức để chỉnh thứ tự bài / xuất / lưu PMGPLX.
            </p>

            @if ($duKienOptions->isEmpty())
                <div class="alert alert-warning">
                    Chưa có lịch dự kiến nào đã sinh buổi.
                    <a href="{{ route('qldtoto.lich-du-kien.thuc-hanh.create') }}">Tạo lịch dự kiến</a>
                    rồi bấm <strong>Sinh lịch</strong> trước.
                </div>
            @else
                <form method="POST" action="{{ route('qldtoto.lam-lich-chinh-thuc.store') }}">
                    @csrf
                    <div class="form-row align-items-end">
                        <div class="form-group col-md-6">
                            <label for="lich_du_kien_id">Lịch dự kiến <span class="text-danger">*</span></label>
                            <select class="form-control form-control-sm chon-s2" id="lich_du_kien_id" name="lich_du_kien_id" required
                                    data-placeholder="— Chọn lịch dự kiến —">
                                <option value=""></option>
                                @foreach ($duKienOptions as $dk)
                                    <option value="{{ $dk->Id }}" @selected((string) old('lich_du_kien_id') === (string) $dk->Id)
                                            data-ten="{{ $dk->TenKhoa }}">
                                        {{ $dk->TenKhoa }}
                                        · {{ $hangOptions[$dk->Hang] ?? $dk->Hang }}
                                        · BĐ {{ $dk->NgayBatDauThucHanh?->format('d/m/Y') }}
                                        · {{ $dk->buois_count }} buổi
                                        @if ($dk->NgaySinhLich)
                                            · sinh {{ $dk->NgaySinhLich->format('d/m/Y H:i') }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="ma_kh">Mã khoá PMGPLX (để lưu vào phần mềm)</label>
                            <select class="form-control form-control-sm sel-khoa-pmgplx" id="ma_kh" name="ma_kh"
                                    data-placeholder="— Gợi ý theo tên khoá hoặc chọn —">
                                <option value=""></option>
                                @foreach ($khoaOptions as $kh)
                                    <option value="{{ $kh->MaKH }}" @selected(old('ma_kh') === $kh->MaKH)>
                                        {{ $kh->TenKH }} ({{ $kh->MaKH }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <button type="submit" class="btn btn-sm btn-navy btn-block">Tạo &amp; xem lại</button>
                        </div>
                    </div>
                </form>
            @endif

            <a href="{{ route('qldtoto.lam-lich-chinh-thuc.index') }}" class="btn btn-sm btn-outline-secondary mt-2">← Danh sách</a>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function ($) {
    function s2(sel) {
        var $el = $(sel);
        if (!$el.length) return;
        $el.select2({
            theme: 'bootstrap4',
            width: '100%',
            allowClear: true,
            placeholder: $el.data('placeholder') || '—'
        });
    }
    s2('#lich_du_kien_id');
    s2('#ma_kh');

    $('#lich_du_kien_id').on('change', function () {
        var opt = this.options[this.selectedIndex];
        var ten = opt ? (opt.getAttribute('data-ten') || '') : '';
        var $kh = $('#ma_kh');
        if (!ten || $kh.val()) return;
        var found = null;
        $kh.find('option').each(function () {
            var v = this.value;
            var t = (this.textContent || '').toUpperCase();
            if (v && (v === ten || t.indexOf(ten.toUpperCase()) !== -1)) {
                found = v;
                return false;
            }
        });
        if (found) $kh.val(found).trigger('change');
    });
})(jQuery);
</script>
@endpush
