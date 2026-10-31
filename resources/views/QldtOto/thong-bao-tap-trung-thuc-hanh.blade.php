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
                Mẫu <code>thong-bao-tap-trung-thuc-hanh.docx</code>. Chọn khoá để điền sẵn hạng / khoá, nhập thời gian tập trung rồi bấm <strong>Xuất Word</strong>.
            </p>

            <form method="GET" action="{{ route('qldtoto.thong-bao-tap-trung-thuc-hanh.index') }}" class="form-row align-items-end mb-3">
                <div class="form-group col-md-6 mb-0">
                    <label for="ma_kh_loc">Khoá (PMGPLX)</label>
                    <select class="form-control form-control-sm" id="ma_kh_loc" name="ma_kh">
                        <option value=""></option>
                        @foreach ($khoaOptions as $kh)
                            <option value="{{ $kh->MaKH }}" @selected($maKh === $kh->MaKH)>{{ $kh->TenKH }} ({{ $kh->MaKH }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2 mb-0">
                    <button type="submit" class="btn btn-sm btn-primary btn-block">Chọn khoá</button>
                </div>
            </form>

            @if ($form === null)
                <p class="text-muted mb-0">Chọn khoá để lập thông báo.</p>
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

                @php $v = fn (string $key) => old($key, $form[$key] ?? ''); @endphp

                <form method="POST" action="{{ route('qldtoto.thong-bao-tap-trung-thuc-hanh.export') }}" id="form-thong-bao">
                    @csrf
                    <input type="hidden" name="ma_kh" value="{{ $form['ma_kh'] }}">

                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="so_tb">Số thông báo</label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" id="so_tb" name="so_tb" inputmode="numeric"
                                       value="{{ $v('so_tb') }}" placeholder="vd. 365" maxlength="10" required>
                                <div class="input-group-append"><span class="input-group-text">/TB-TTML</span></div>
                            </div>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="ngay_thong_bao">Ngày thông báo</label>
                            <input type="date" class="form-control form-control-sm" id="ngay_thong_bao" name="ngay_thong_bao" value="{{ $v('ngay_thong_bao') }}" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="hang">Hạng</label>
                            <input type="text" class="form-control form-control-sm" id="hang" name="hang" value="{{ $v('hang') }}" maxlength="40" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="khoa_so">Khóa (số)</label>
                            <input type="text" class="form-control form-control-sm" id="khoa_so" name="khoa_so" value="{{ $v('khoa_so') }}" maxlength="20" required>
                        </div>
                    </div>

                    <h6 class="font-weight-bold mb-2">Thời gian tập trung học thực hành</h6>
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="buoi">Buổi</label>
                            <select class="form-control form-control-sm" id="buoi" name="buoi">
                                @foreach ($buoiOptions as $b)
                                    <option value="{{ $b }}" @selected($v('buoi') === $b)>{{ $b }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="gio">Giờ</label>
                            <input type="text" class="form-control form-control-sm" id="gio" name="gio" value="{{ $v('gio') }}" maxlength="20" placeholder="vd. 06h, 13h30" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="ngay_tap_trung">Ngày</label>
                            <input type="date" class="form-control form-control-sm" id="ngay_tap_trung" name="ngay_tap_trung" value="{{ $v('ngay_tap_trung') }}" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Sẽ in</label>
                            <div class="form-control form-control-sm bg-light text-truncate" id="xem-thoi-gian">—</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="dia_diem">Địa điểm (sau chữ «tại»)</label>
                        <input type="text" class="form-control form-control-sm" id="dia_diem" name="dia_diem" value="{{ $v('dia_diem') }}" maxlength="300" required>
                    </div>
                    <div class="form-group">
                        <label for="noi_dung">Nội dung</label>
                        <textarea class="form-control form-control-sm" id="noi_dung" name="noi_dung" rows="2" maxlength="500" required>{{ $v('noi_dung') }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-sm btn-navy">Xuất Word</button>
                </form>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function ($) {
    $(function () {
        const $khoa = $('#ma_kh_loc');
        $khoa.select2({
            theme: 'bootstrap4', width: '100%', allowClear: true, placeholder: '— Chọn khoá —',
            dropdownParent: $khoa.closest('.form-group'),
            language: { noResults: function () { return 'Không tìm thấy khoá học'; } },
        });
        $khoa.on('select2:select', function () { this.form.submit(); });

        const form = document.getElementById('form-thong-bao');
        if (!form) return;

        function xemThoiGian() {
            const buoi = document.getElementById('buoi').value;
            const gio = document.getElementById('gio').value.trim();
            const ngay = document.getElementById('ngay_tap_trung').value;
            const out = document.getElementById('xem-thoi-gian');
            if (!ngay) { out.textContent = '—'; return; }
            const d = new Date(ngay + 'T00:00:00');
            const thu = d.getDay() === 0 ? 'Chủ nhật' : 'Thứ ' + (d.getDay() + 1);
            const dmy = ngay.split('-').reverse().join('/');
            out.textContent = (buoi + ' ' + gio).trim() + ' ngày ' + dmy + ' (' + thu + ')';
        }
        ['buoi', 'gio', 'ngay_tap_trung'].forEach(function (id) {
            document.getElementById(id).addEventListener('input', xemThoiGian);
            document.getElementById(id).addEventListener('change', xemThoiGian);
        });
        document.getElementById('so_tb').addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '');
        });
        xemThoiGian();
    });
})(jQuery);
</script>
@endpush
