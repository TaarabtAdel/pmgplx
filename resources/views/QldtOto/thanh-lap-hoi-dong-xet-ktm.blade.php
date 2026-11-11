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
                Mẫu <code>quyet-dinh-thanh-lap-hoi-dong-xet-hoan-thanh-khoa.docx</code> và
                <code>danh-sach-hoi-dong-xet-hoan-thanh-khoa.xlsx</code> (danh sách thành viên hội đồng kèm theo).
            </p>

            <form method="GET" action="{{ route('qldtoto.thanh-lap-hoi-dong-xet-ktm.index') }}" class="form-row align-items-end mb-3">
                <div class="form-group col-md-6 mb-0">
                    <label for="ma_kh_loc">Khoá (gợi ý — tùy chỉnh mô tả lớp nhiều khóa trên form)</label>
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
                <div class="form-group col-md-4 mb-0">
                    <a href="{{ route('qldtoto.thanh-lap-hoi-dong-xet-ktm.index', ['bat_dau' => 1]) }}" class="btn btn-sm btn-outline-secondary btn-block">Nhập nhiều khóa (không chọn khoá)</a>
                </div>
            </form>

            @if ($form === null)
                <p class="text-muted mb-0">Chọn khoá hoặc bấm «Nhập nhiều khóa» để lập quyết định + danh sách hội đồng.</p>
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

                <form method="POST" id="form-hoi-dong">
                    @csrf
                    <input type="hidden" name="ma_kh" value="{{ $form['ma_kh'] }}">

                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="so_qd">Số quyết định</label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" id="so_qd" name="so_qd" inputmode="numeric"
                                       value="{{ $v('so_qd') }}" placeholder="vd. 516" maxlength="10" required>
                                <div class="input-group-append"><span class="input-group-text">/QĐ-TTML</span></div>
                            </div>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="ngay_quyet_dinh">Ngày quyết định</label>
                            <input type="date" class="form-control form-control-sm" id="ngay_quyet_dinh" name="ngay_quyet_dinh" value="{{ $v('ngay_quyet_dinh') }}" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="giam_doc">Giám đốc ký</label>
                            <input type="text" class="form-control form-control-sm" id="giam_doc" name="giam_doc" value="{{ $v('giam_doc') }}" maxlength="100" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="mo_ta_quyet_dinh">Dòng mô tả dưới tiêu đề «QUYẾT ĐỊNH»</label>
                        <textarea class="form-control form-control-sm" id="mo_ta_quyet_dinh" name="mo_ta_quyet_dinh" rows="2" maxlength="500" required>{{ $v('mo_ta_quyet_dinh') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label for="mo_ta_dieu_1">Điều 1 — phần sau «…lớp đào tạo lái xe ô tô»</label>
                        <textarea class="form-control form-control-sm" id="mo_ta_dieu_1" name="mo_ta_dieu_1" rows="2" maxlength="500" required>{{ $v('mo_ta_dieu_1') }}</textarea>
                    </div>

                    <div class="d-flex align-items-center mb-2 mt-3">
                        <h6 class="font-weight-bold mb-0 mr-auto">Danh sách thành viên hội đồng (Excel kèm theo)</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-them-tv">+ Thêm thành viên</button>
                    </div>
                    <div id="ds-thanh-vien"></div>

                    <div class="mt-3 d-flex flex-wrap">
                        <button type="submit" class="btn btn-sm btn-outline-primary mr-2 mb-2"
                                formaction="{{ route('qldtoto.thanh-lap-hoi-dong-xet-ktm.export-quyet-dinh') }}">Xuất Quyết định</button>
                        <button type="submit" class="btn btn-sm btn-outline-primary mr-2 mb-2"
                                formaction="{{ route('qldtoto.thanh-lap-hoi-dong-xet-ktm.export-danh-sach') }}">Xuất Danh sách Excel</button>
                        <button type="submit" class="btn btn-sm btn-navy mb-2"
                                formaction="{{ route('qldtoto.thanh-lap-hoi-dong-xet-ktm.export-ca-hai') }}">Xuất cả hai (ZIP)</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function ($) {
    $(function () {
        $('#ma_kh_loc').select2({
            theme: 'bootstrap4', width: '100%', allowClear: true, placeholder: '— Chọn khoá —',
            dropdownParent: $('#ma_kh_loc').closest('.form-group'),
            language: { noResults: function () { return 'Không tìm thấy'; } },
        }).on('select2:select', function () { this.form.submit(); });

        const form = document.getElementById('form-hoi-dong');
        if (!form) return;

        let tv = @json(array_values(old('thanh_vien', $form['thanh_vien'] ?? [])));
        if (!tv.length) tv = [{ ho_ten: '', chuc_vu: '', chuc_danh: '' }];

        const ds = document.getElementById('ds-thanh-vien');
        const esc = function (s) { return $('<div>').text(s).html(); };

        function render() {
            ds.innerHTML = tv.map(function (m, i) {
                return '<div class="form-row border rounded p-2 mb-2 bg-light align-items-end" data-i="' + i + '">'
                    + '<div class="col-md-1 mb-2 mb-md-0 text-center font-weight-bold">' + (i + 1) + '</div>'
                    + '<div class="form-group col-md-4 mb-md-0">'
                    + '<label class="small">Họ và tên</label>'
                    + '<input type="text" class="form-control form-control-sm js-ht" name="thanh_vien[' + i + '][ho_ten]" maxlength="120" required value="' + esc(m.ho_ten || '') + '">'
                    + '</div>'
                    + '<div class="form-group col-md-3 mb-md-0">'
                    + '<label class="small">Chức vụ</label>'
                    + '<input type="text" class="form-control form-control-sm js-cv" name="thanh_vien[' + i + '][chuc_vu]" maxlength="120" required value="' + esc(m.chuc_vu || '') + '">'
                    + '</div>'
                    + '<div class="form-group col-md-3 mb-md-0">'
                    + '<label class="small">Chức danh trong HĐ</label>'
                    + '<input type="text" class="form-control form-control-sm js-cd" name="thanh_vien[' + i + '][chuc_danh]" maxlength="120" required value="' + esc(m.chuc_danh || '') + '">'
                    + '</div>'
                    + '<div class="form-group col-md-1 mb-md-0">'
                    + '<button type="button" class="btn btn-sm btn-outline-danger btn-block js-bo-tv"' + (tv.length === 1 ? ' disabled' : '') + '>&times;</button>'
                    + '</div></div>';
            }).join('');
        }

        ds.addEventListener('input', function (e) {
            const row = e.target.closest('[data-i]');
            if (!row) return;
            const i = +row.dataset.i;
            if (e.target.classList.contains('js-ht')) tv[i].ho_ten = e.target.value;
            if (e.target.classList.contains('js-cv')) tv[i].chuc_vu = e.target.value;
            if (e.target.classList.contains('js-cd')) tv[i].chuc_danh = e.target.value;
        });
        ds.addEventListener('click', function (e) {
            const btn = e.target.closest('.js-bo-tv');
            if (!btn) return;
            const i = +btn.closest('[data-i]').dataset.i;
            if (tv.length === 1) return;
            tv.splice(i, 1);
            render();
        });
        document.getElementById('btn-them-tv').addEventListener('click', function () {
            tv.push({ ho_ten: '', chuc_vu: '', chuc_danh: 'Ủy viên' });
            render();
        });

        document.getElementById('so_qd').addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '');
        });

        render();
    });
})(jQuery);
</script>
@endpush
