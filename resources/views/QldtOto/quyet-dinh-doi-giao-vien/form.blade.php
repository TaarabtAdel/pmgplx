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
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>{{ $item['title'] }}</span>
            <a href="{{ route('qldtoto.quyet-dinh-doi-giao-vien.reset') }}" class="btn btn-sm btn-outline-secondary"
               onclick="return confirm('Xoá toàn bộ dữ liệu đang nhập?');">Làm mới</a>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">
                Mẫu <code>quyet-dinh-doi-giao-vien.docx</code>: mỗi dòng là
                «Giáo viên <em>cũ</em> sử dụng xe … từ ngày … đến hết …. Giáo viên <em>thay</em> thay giáo viên <em>cũ</em> tiếp tục sử dụng xe … từ ngày … đến hết …».
                Chọn khoá để gợi ý giáo viên / xe từ phân công đào tạo.
            </p>

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

            <form method="POST" action="{{ route('qldtoto.quyet-dinh-doi-giao-vien.preview.store') }}" id="form-doi-gv">
                @csrf
                <div class="form-row mb-3">
                    <div class="form-group col-md-3 mb-0">
                        <label for="so_qd">Số quyết định</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" id="so_qd" name="so_qd" inputmode="numeric"
                                   value="{{ $form['so_qd'] }}" placeholder="vd. 753" maxlength="10" required>
                            <div class="input-group-append"><span class="input-group-text">/QĐ-TTML</span></div>
                        </div>
                    </div>
                    <div class="form-group col-md-3 mb-0">
                        <label for="ngay_quyet_dinh">Ngày quyết định</label>
                        <input type="date" class="form-control form-control-sm" id="ngay_quyet_dinh" name="ngay_quyet_dinh"
                               value="{{ $form['ngay_quyet_dinh'] }}" required>
                    </div>
                </div>

                <div class="form-row align-items-end mb-3">
                    <div class="form-group col-md-6 mb-0">
                        <label for="chon_khoa">Thêm khoá (PMGPLX)</label>
                        <select class="form-control form-control-sm" id="chon_khoa" data-placeholder="— Chọn khoá —">
                            <option value=""></option>
                            @foreach ($khoaOptions as $kh)
                                <option value="{{ $kh->MaKH }}">{{ $kh->TenKH }} ({{ $kh->MaKH }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-2 mb-0">
                        <button type="button" class="btn btn-sm btn-primary btn-block" id="btn-them-khoa">＋ Thêm khoá</button>
                    </div>
                </div>

                <div id="khoa-list"></div>
                <p class="text-muted small" id="khoa-empty">Chưa có khoá nào — chọn khoá rồi bấm «Thêm khoá».</p>

                <button type="submit" class="btn btn-sm btn-navy">Xem trước</button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function ($) {
    const gvOptions = @json($giaoVienOptions->map(fn ($o) => ['ma' => $o['ma'], 'ho_ten' => $o['ho_ten'], 'label' => $o['label']])->values());
    const xeOptions = @json($xeTapOptions->values());
    const urlKhoa = @json(route('qldtoto.quyet-dinh-doi-giao-vien.khoa', ['maKh' => '__MA__']));
    const FIELDS = ['gv_cu', 'xe', 'tu_cu', 'den_cu', 'gv_moi', 'tu_moi', 'den_moi'];

    let state = @json($form['khoa_list']);
    const listEl = document.getElementById('khoa-list');
    const emptyEl = document.getElementById('khoa-empty');

    const lang = { noResults: function () { return 'Không tìm thấy'; } };

    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function norm(s) {
        return String(s ?? '').normalize('NFC').toLowerCase().replace(/\s+/g, ' ').trim();
    }

    function timMaGv(ma, hoTen) {
        if (ma && gvOptions.some(function (o) { return o.ma === ma; })) return ma;
        const n = norm(hoTen);
        const hit = n ? gvOptions.find(function (o) { return norm(o.ho_ten) === n; }) : null;
        return hit ? hit.ma : '';
    }

    function timBienSo(bien) {
        if (!bien) return '';
        const key = String(bien).toUpperCase().replace(/[\s.\-]/g, '');
        const hit = xeOptions.find(function (o) { return String(o.bien_so).toUpperCase().replace(/[\s.\-]/g, '') === key; });
        return hit ? hit.bien_so : bien;
    }

    function congNgay(ymd, n) {
        if (!ymd) return '';
        const d = new Date(ymd + 'T00:00:00Z');
        if (isNaN(d)) return '';
        d.setUTCDate(d.getUTCDate() + n);
        return d.toISOString().slice(0, 10);
    }

    function selectHtml(name, field, options, valueKey, selected, placeholder) {
        let html = '<select class="form-control form-control-sm chon-s2" data-f="' + field + '" name="' + name + '" data-placeholder="' + esc(placeholder) + '"><option value=""></option>';
        let found = false;
        options.forEach(function (o) {
            const v = String(o[valueKey]);
            const sel = v === String(selected ?? '');
            if (sel) found = true;
            html += '<option value="' + esc(v) + '"' + (sel ? ' selected' : '') + '>' + esc(o.label) + '</option>';
        });
        if (selected && !found) {
            html += '<option value="' + esc(selected) + '" selected>' + esc(selected) + '</option>';
        }
        return html + '</select>';
    }

    function dongHtml(k, r, row) {
        const n = 'khoa_list[' + k + '][rows][' + r + ']';
        const date = function (f) {
            return '<input type="date" class="form-control form-control-sm" data-f="' + f + '" name="' + n + '[' + f + ']" value="' + esc(row[f]) + '">';
        };
        return '<tr class="dong-row">' +
            '<td class="text-muted">' + (r + 1) + '</td>' +
            '<td>' + selectHtml(n + '[gv_cu]', 'gv_cu', gvOptions, 'ma', row.gv_cu, '— GV cũ —') + '</td>' +
            '<td>' + selectHtml(n + '[xe]', 'xe', xeOptions, 'bien_so', row.xe, '— Xe —') + '</td>' +
            '<td>' + date('tu_cu') + '</td>' +
            '<td>' + date('den_cu') + '</td>' +
            '<td>' + selectHtml(n + '[gv_moi]', 'gv_moi', gvOptions, 'ma', row.gv_moi, '— GV thay —') + '</td>' +
            '<td>' + date('tu_moi') + '</td>' +
            '<td>' + date('den_moi') + '</td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger py-0 btn-xoa-dong" title="Xóa dòng">✕</button></td>' +
            '</tr>';
    }

    function khoaHtml(k, khoa) {
        const n = 'khoa_list[' + k + ']';
        const rows = (khoa.rows || []).map(function (row, r) { return dongHtml(k, r, row); }).join('');
        return '<div class="card border mb-3 khoa-block" data-k="' + k + '">' +
            '<div class="card-header py-2 d-flex align-items-center">' +
            '<strong class="mr-2">Khoá</strong>' +
            '<input type="text" class="form-control form-control-sm" data-k-f="ten_ngan" name="' + n + '[ten_ngan]" value="' + esc(khoa.ten_ngan) + '" style="max-width: 9rem;" required>' +
            '<input type="hidden" data-k-f="ma_kh" name="' + n + '[ma_kh]" value="' + esc(khoa.ma_kh) + '">' +
            '<input type="hidden" data-k-f="ngay_bg" name="' + n + '[ngay_bg]" value="' + esc(khoa.ngay_bg) + '">' +
            '<span class="small text-muted ml-2">PMGPLX: <code>' + esc(khoa.ma_kh) + '</code></span>' +
            '<button type="button" class="btn btn-sm btn-outline-danger ml-auto btn-xoa-khoa">✕ Xóa khoá</button>' +
            '</div>' +
            '<div class="card-body p-2">' +
            '<div class="table-responsive"><table class="table table-sm table-bordered mb-2" style="min-width: 1150px; table-layout: fixed;">' +
            '<colgroup><col style="width:2.2rem"><col style="width:17%"><col style="width:12%"><col style="width:9rem"><col style="width:9rem"><col style="width:17%"><col style="width:9rem"><col style="width:9rem"><col style="width:2.6rem"></colgroup>' +
            '<thead class="thead-light"><tr>' +
            '<th>#</th><th>Giáo viên cũ</th><th>Xe</th><th>Từ ngày</th><th>Đến hết</th>' +
            '<th>Giáo viên thay</th><th>Từ ngày</th><th>Đến hết</th><th></th>' +
            '</tr></thead><tbody>' + rows + '</tbody></table></div>' +
            '<button type="button" class="btn btn-sm btn-outline-secondary btn-them-dong">＋ Thêm dòng</button>' +
            '</div></div>';
    }

    function docState() {
        state = Array.from(listEl.querySelectorAll('.khoa-block')).map(function (card) {
            const khoa = {};
            card.querySelectorAll('[data-k-f]').forEach(function (el) { khoa[el.dataset.kF] = el.value; });
            khoa.rows = Array.from(card.querySelectorAll('.dong-row')).map(function (tr) {
                const row = {};
                FIELDS.forEach(function (f) {
                    const el = tr.querySelector('[data-f="' + f + '"]');
                    row[f] = el ? el.value : '';
                });
                return row;
            });
            return khoa;
        });
    }

    function render() {
        $(listEl).find('select.chon-s2').each(function () {
            if ($(this).hasClass('select2-hidden-accessible')) $(this).select2('destroy');
        });
        listEl.innerHTML = state.map(function (khoa, k) { return khoaHtml(k, khoa); }).join('');
        emptyEl.style.display = state.length ? 'none' : '';
        $(listEl).find('select.chon-s2').each(function () {
            $(this).select2({
                theme: 'bootstrap4',
                width: '100%',
                allowClear: true,
                placeholder: this.getAttribute('data-placeholder') || '',
                language: lang,
            });
        });
    }

    function dongMoi(khoa) {
        return { gv_cu: '', xe: '', tu_cu: '', den_cu: '', gv_moi: '', tu_moi: '', den_moi: khoa.ngay_bg || '' };
    }

    $('#chon_khoa').select2({
        theme: 'bootstrap4',
        width: '100%',
        allowClear: true,
        placeholder: '— Chọn khoá —',
        dropdownParent: $('#chon_khoa').closest('.form-group'),
        language: lang,
    });

    document.getElementById('btn-them-khoa').addEventListener('click', async function () {
        const ma = $('#chon_khoa').val();
        if (!ma) return;
        docState();
        if (state.some(function (k) { return k.ma_kh === ma; })) {
            alert('Khoá này đã có trong danh sách.');
            return;
        }
        const btn = this;
        btn.disabled = true;
        try {
            const res = await fetch(urlKhoa.replace('__MA__', encodeURIComponent(ma)), { headers: { Accept: 'application/json' } });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();
            const khoa = { ma_kh: data.ma_kh, ten_ngan: data.ten_ngan, ngay_bg: data.ngay_bg || '', rows: [] };
            (data.rows || []).forEach(function (g) {
                khoa.rows.push({
                    gv_cu: timMaGv(g.gv_cu, g.gv_cu_ten),
                    xe: timBienSo(g.xe),
                    tu_cu: g.tu_cu || '',
                    den_cu: g.den_cu || '',
                    gv_moi: '',
                    tu_moi: '',
                    den_moi: khoa.ngay_bg,
                });
            });
            if (!khoa.rows.length) khoa.rows.push(dongMoi(khoa));
            state.push(khoa);
            render();
            $('#chon_khoa').val(null).trigger('change');
        } catch (err) {
            alert('Không tải được dữ liệu khoá: ' + err.message);
        } finally {
            btn.disabled = false;
        }
    });

    listEl.addEventListener('click', function (e) {
        const btn = e.target.closest('button');
        if (!btn) return;
        const card = btn.closest('.khoa-block');
        if (!card) return;
        docState();
        const k = Number(card.dataset.k);
        if (btn.classList.contains('btn-xoa-khoa')) {
            if (!confirm('Xóa khoá ' + (state[k].ten_ngan || '') + ' khỏi quyết định?')) return;
            state.splice(k, 1);
        } else if (btn.classList.contains('btn-them-dong')) {
            state[k].rows.push(dongMoi(state[k]));
        } else if (btn.classList.contains('btn-xoa-dong')) {
            const r = Array.from(card.querySelectorAll('.dong-row')).indexOf(btn.closest('.dong-row'));
            state[k].rows.splice(r, 1);
            if (!state[k].rows.length) state[k].rows.push(dongMoi(state[k]));
        } else {
            return;
        }
        render();
    });

    listEl.addEventListener('change', function (e) {
        const el = e.target;
        if (el.dataset.f !== 'den_cu' || !el.value) return;
        const tuMoi = el.closest('.dong-row').querySelector('[data-f="tu_moi"]');
        if (tuMoi && !tuMoi.value) tuMoi.value = congNgay(el.value, 1);
    });

    document.getElementById('so_qd').addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '');
    });

    render();
})(jQuery);
</script>
@endpush
