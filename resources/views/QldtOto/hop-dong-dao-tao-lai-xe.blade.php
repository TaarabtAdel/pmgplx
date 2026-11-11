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
                Xuất <strong>mẫu in</strong> theo Thông tư 17: chỉ điền <em>giờ học, học phí, mô tả hạng</em> vào hợp đồng.
                Phần <strong>Bên B</strong> (họ tên, CCCD, địa chỉ, ngày ký…) giữ dấu chấm — in ra để học viên tự điền khi ký.
            </p>

            <form method="GET" action="{{ route('qldtoto.hop-dong-dao-tao.index') }}" class="form-row align-items-end mb-3" id="form-loc">
                <div class="form-group col-md-4 mb-0">
                    <label for="loai_loc">Loại hợp đồng</label>
                    <select class="form-control form-control-sm" id="loai_loc" name="loai">
                        @foreach ($loaiOptions as $key => $opt)
                            <option value="{{ $key }}" @selected($form['loai'] === $key)>{{ $opt['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-4 mb-0">
                    <label for="ma_kh_loc">Khoá (tùy chọn — gợi ý số khóa)</label>
                    <select class="form-control form-control-sm" id="ma_kh_loc" name="ma_kh">
                        <option value=""></option>
                        @foreach ($khoaOptions as $kh)
                            <option value="{{ $kh->MaKH }}" @selected($maKh === $kh->MaKH)>{{ $kh->TenKH }} ({{ $kh->MaKH }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2 mb-0">
                    <button type="submit" class="btn btn-sm btn-primary btn-block">Tải mẫu</button>
                </div>
            </form>

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

            <form method="POST" action="{{ route('qldtoto.hop-dong-dao-tao.export') }}">
                @csrf
                <input type="hidden" name="loai" id="loai_hidden" value="{{ $v('loai') }}">

                <div class="form-group">
                    <label for="mo_ta_hang">Dòng «Là người học lái xe ô tô: …» (có thể giữ «Khóa ….» để học viên điền)</label>
                    <input type="text" class="form-control form-control-sm" id="mo_ta_hang" name="mo_ta_hang" value="{{ $v('mo_ta_hang') }}" maxlength="200" required>
                </div>

                <h6 class="font-weight-bold">Thời gian đào tạo (Điều 1)</h6>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="gio_tong">Tổng giờ / khóa</label>
                        <input type="text" class="form-control form-control-sm js-so" id="gio_tong" name="gio_tong" value="{{ $v('gio_tong') }}" inputmode="numeric" maxlength="4" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="gio_lt">Lý thuyết</label>
                        <input type="text" class="form-control form-control-sm js-so" id="gio_lt" name="gio_lt" value="{{ $v('gio_lt') }}" inputmode="numeric" maxlength="4" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="gio_th">Thực hành</label>
                        <input type="text" class="form-control form-control-sm js-so" id="gio_th" name="gio_th" value="{{ $v('gio_th') }}" inputmode="numeric" maxlength="4" required>
                    </div>
                </div>

                <h6 class="font-weight-bold">Học phí (Điều 2)</h6>
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="hoc_phi">Mức học phí (số)</label>
                        <input type="number" class="form-control form-control-sm" id="hoc_phi" name="hoc_phi" min="0" step="1000" value="{{ $v('hoc_phi') }}" required>
                    </div>
                    <div class="form-group col-md-9">
                        <label for="hoc_phi_chu">Bằng chữ (học phí)</label>
                        <input type="text" class="form-control form-control-sm" id="hoc_phi_chu" name="hoc_phi_chu" value="{{ $v('hoc_phi_chu') }}" maxlength="120" required>
                    </div>
                </div>
                <div class="form-row">
                    @foreach ([1, 2, 3] as $lan)
                        <div class="form-group col-md-6">
                            <label for="lan_{{ $lan }}">Lần {{ $lan }} (số)</label>
                            <input type="number" class="form-control form-control-sm" id="lan_{{ $lan }}" name="lan_{{ $lan }}" min="0" step="1000" value="{{ $v('lan_'.$lan) }}" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="lan_{{ $lan }}_chu">Lần {{ $lan }} bằng chữ</label>
                            <input type="text" class="form-control form-control-sm" id="lan_{{ $lan }}_chu" name="lan_{{ $lan }}_chu" value="{{ $v('lan_'.$lan.'_chu') }}" maxlength="120" required>
                        </div>
                    @endforeach
                </div>
                <div class="form-group">
                    <label for="hoc_phi_mot_lan_chu">Ghi chú nộp một lần — bằng chữ</label>
                    <input type="text" class="form-control form-control-sm" id="hoc_phi_mot_lan_chu" name="hoc_phi_mot_lan_chu" value="{{ $v('hoc_phi_mot_lan_chu') }}" maxlength="120" required>
                </div>

                <button type="submit" class="btn btn-sm btn-navy">Xuất file Word để in</button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function ($) {
    const macDinh = @json($loaiMacDinhJson);
    $('#loai_loc, #ma_kh_loc').select2({
        theme: 'bootstrap4', width: '100%', allowClear: true,
        dropdownParent: $('#form-loc').closest('.card-body'),
    });
    $('#ma_kh_loc').on('select2:select select2:clear', function () { $('#form-loc').trigger('submit'); });

  document.querySelectorAll('.js-so').forEach(function (el) {
        el.addEventListener('input', function () { this.value = this.value.replace(/\D/g, ''); });
    });

    $('#loai_loc').on('change', function () {
        const loai = this.value;
        $('#loai_hidden').val(loai);
        const d = macDinh[loai];
        if (!d) return;
        Object.keys(d).forEach(function (k) {
            const el = document.getElementById(k) || document.querySelector('[name="'+k+'"]');
            if (el) el.value = d[k];
        });
    });
})(jQuery);
</script>
@endpush
