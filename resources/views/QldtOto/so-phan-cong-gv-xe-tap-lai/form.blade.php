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
                Giáo viên chọn từ
                <a href="{{ route('pmgplx.dm.giao-vien.index') }}" target="_blank">Danh mục giáo viên PMGPLX</a>
                (cột Ghi chú = xe gắn GV — tự chọn biển số khi «Xe tự động»).
                Biển số chọn từ
                <a href="{{ route('pmgplx.dm.xe.index') }}" target="_blank">Danh mục xe PMGPLX</a>.
                Xem trước cảnh báo trùng lịch như
                <a href="{{ route('daotao.pdt.phan-cong-dao-tao.danh-sach') }}">Phân công đào tạo</a>.
            </p>

            <form method="POST" action="{{ route('qldtoto.so-phan-cong-gv-xe-tap-lai.preview.store') }}" id="form-phan-cong">
                @csrf
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="ma_kh">Khoá (PMGPLX)</label>
                        <select class="form-control form-control-sm chon-s2" id="ma_kh" name="ma_kh" required data-placeholder="— Chọn khoá —">
                            <option value="">— Chọn khoá —</option>
                            @foreach ($khoaOptions as $kh)
                                <option value="{{ $kh->MaKH }}" @selected(old('ma_kh', $form['ma_kh']) === $kh->MaKH)>
                                    {{ $kh->TenKH }} ({{ $kh->MaKH }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="ten_khoa">Tên khoá (Phân công đào tạo)</label>
                        <input type="text" class="form-control form-control-sm" id="ten_khoa" name="ten_khoa"
                               value="{{ old('ten_khoa', $form['ten_khoa']) }}" required>
                    </div>
                    <div class="form-group col-md-2">
                        <label for="ten_khoa_doc">Khoá trên file Word</label>
                        <input type="text" class="form-control form-control-sm" id="ten_khoa_doc" name="ten_khoa_doc"
                               value="{{ old('ten_khoa_doc', $form['ten_khoa_doc']) }}" required placeholder="vd. K56">
                    </div>
                    <div class="form-group col-md-2">
                        <label for="nam">Năm</label>
                        <input type="number" class="form-control form-control-sm" id="nam" name="nam"
                               value="{{ old('nam', $form['nam']) }}" required min="2000" max="2100">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-2" id="bang-phan-cong">
                        <thead class="thead-light">
                            <tr>
                                <th style="width:3rem;">#</th>
                                <th style="width:11.5rem;min-width:11.5rem;">Giáo viên</th>
                                <th style="width:9rem;">Từ ngày</th>
                                <th style="width:9rem;">Đến ngày</th>
                                <th style="width:9rem;">Biển số xe</th>
                                <th style="width:10rem;">Nội dung</th>
                                <th style="width:3rem;"></th>
                            </tr>
                        </thead>
                        <tbody id="rows-body">
                            @php $oldRows = old('rows', $form['rows']); @endphp
                            @foreach ($oldRows as $i => $row)
                                <tr class="row-phan-cong">
                                    <td class="text-muted stt">{{ $i + 1 }}</td>
                                    <td>
                                        @include('QldtOto.so-phan-cong-gv-xe-tap-lai.partials.giao-vien-select', [
                                            'name' => 'rows['.$i.'][ma_gv]',
                                            'selectedMa' => $row['ma_gv'] ?? '',
                                            'selectedHoTen' => $row['ho_ten'] ?? '',
                                        ])
                                    </td>
                                    <td><input type="date" class="form-control form-control-sm" name="rows[{{ $i }}][tu_ngay]" value="{{ $row['tu_ngay'] ?? '' }}"></td>
                                    <td><input type="date" class="form-control form-control-sm" name="rows[{{ $i }}][den_ngay]" value="{{ $row['den_ngay'] ?? '' }}"></td>
                                    <td>
                                        @include('QldtOto.so-phan-cong-gv-xe-tap-lai.partials.xe-select', [
                                            'name' => 'rows['.$i.'][bien_so]',
                                            'selectedBienSo' => $row['bien_so'] ?? '',
                                        ])
                                    </td>
                                    <td>
                                        <select class="form-control form-control-sm sel-noi-dung chon-s2" name="rows[{{ $i }}][noi_dung]" required>
                                            @foreach ($noiDungOptions as $opt)
                                                <option value="{{ $opt }}" @selected(($row['noi_dung'] ?? '') === $opt)>{{ $opt }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger btn-xoa-dong" title="Xóa dòng">✕</button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <button type="button" class="btn btn-sm btn-outline-primary mb-3" id="btn-them-dong">＋ Thêm dòng</button>

                @error('rows')<div class="text-danger small mb-2">{{ $message }}</div>@enderror

                <div>
                    <button type="submit" class="btn btn-sm btn-navy">Xem trước &amp; xuất file</button>
                </div>
            </form>
        </div>
    </div>

    <template id="tpl-gv-select">
        @include('QldtOto.so-phan-cong-gv-xe-tap-lai.partials.giao-vien-select', [
            'name' => 'rows[__IDX__][ma_gv]',
            'selectedMa' => '',
            'selectedHoTen' => '',
        ])
    </template>
    <template id="tpl-xe-select">
        @include('QldtOto.so-phan-cong-gv-xe-tap-lai.partials.xe-select', [
            'name' => 'rows[__IDX__][bien_so]',
            'selectedBienSo' => '',
        ])
    </template>
@endsection

@push('scripts')
<script>
(function ($) {
    const body = document.getElementById('rows-body');
    const btnThem = document.getElementById('btn-them-dong');
    const maKh = document.getElementById('ma_kh');
    const xeTuDong = @json(\App\Support\QldtOto\PhanCongGvXeTapLaiFormSupport::NOI_DUNG_XE_TU_DONG);
    const noiDungHtml = @json(collect($noiDungOptions)->map(fn ($o) => '<option value="'.e($o).'">'.e($o).'</option>')->implode(''));
    const gvSelectTpl = document.getElementById('tpl-gv-select');
    const xeSelectTpl = document.getElementById('tpl-xe-select');

    const select2Lang = {
        noResults: function () { return 'Không tìm thấy'; },
        removeAllItems: function () { return 'Bỏ chọn'; },
    };

    function khoiTaoSelect2(scope) {
        const root = scope || document.getElementById('form-phan-cong');
        if (!root) return;
        root.querySelectorAll('select.chon-s2').forEach(function (sel) {
            if ($(sel).hasClass('select2-hidden-accessible')) {
                return;
            }
            const opts = {
                theme: 'bootstrap4',
                width: '100%',
                allowClear: !sel.classList.contains('sel-noi-dung'),
                placeholder: sel.getAttribute('data-placeholder') || '',
                language: select2Lang,
            };
            $(sel).select2(opts);
            $(sel).on('select2:clearing', function () {
                $(this).data('vuaBoChon', true);
            }).on('select2:opening', function (e) {
                if ($(this).data('vuaBoChon')) {
                    $(this).removeData('vuaBoChon');
                    e.preventDefault();
                }
            });
        });
    }

    function huySelect2(tr) {
        $(tr).find('select.chon-s2').each(function () {
            if ($(this).hasClass('select2-hidden-accessible')) {
                $(this).select2('destroy');
            }
        });
    }

    function reindex() {
        body.querySelectorAll('.row-phan-cong').forEach(function (tr, idx) {
            tr.querySelector('.stt').textContent = idx + 1;
            tr.querySelectorAll('[name^="rows["]').forEach(function (el) {
                el.name = el.name.replace(/rows\[\d+]/, 'rows[' + idx + ']');
            });
        });
    }

    function normalizeBienKey(v) {
        return (v || '').toUpperCase().replace(/[\s.\-]/g, '');
    }

    function pickBienSoSelect(selectEl, raw) {
        if (!selectEl || !raw) return;
        const key = normalizeBienKey(raw);
        let val = '';
        for (let i = 0; i < selectEl.options.length; i++) {
            const o = selectEl.options[i];
            if (!o.value) continue;
            if (o.value === raw || normalizeBienKey(o.value) === key) {
                val = o.value;
                break;
            }
        }
        if (val !== '') {
            $(selectEl).val(val).trigger('change');
        }
    }

    function applyXeTuDong(tr) {
        const nd = tr.querySelector('.sel-noi-dung');
        if (!nd || nd.value !== xeTuDong) return;
        const sel = tr.querySelector('.sel-gv');
        const bi = tr.querySelector('.sel-bien-so');
        if (!sel || !bi) return;
        const opt = sel.options[sel.selectedIndex];
        if (opt && opt.dataset.ghiChu) {
            pickBienSoSelect(bi, opt.dataset.ghiChu);
        }
    }

    $(body).on('change', '.sel-gv, .sel-noi-dung', function () {
        applyXeTuDong(this.closest('tr'));
    });

    btnThem?.addEventListener('click', function () {
        const idx = body.querySelectorAll('.row-phan-cong').length;
        const tr = document.createElement('tr');
        tr.className = 'row-phan-cong';
        let gvCell = '';
        if (gvSelectTpl) {
            gvCell = gvSelectTpl.innerHTML.replace(/rows\[__IDX__\]/g, 'rows[' + idx + ']');
        }
        let xeCell = '';
        if (xeSelectTpl) {
            xeCell = xeSelectTpl.innerHTML.replace(/rows\[__IDX__\]/g, 'rows[' + idx + ']');
        }
        tr.innerHTML =
            '<td class="text-muted stt">' + (idx + 1) + '</td>' +
            '<td>' + gvCell + '</td>' +
            '<td><input type="date" class="form-control form-control-sm" name="rows[' + idx + '][tu_ngay]"></td>' +
            '<td><input type="date" class="form-control form-control-sm" name="rows[' + idx + '][den_ngay]"></td>' +
            '<td>' + xeCell + '</td>' +
            '<td><select class="form-control form-control-sm sel-noi-dung chon-s2" name="rows[' + idx + '][noi_dung]" required>' + noiDungHtml + '</select></td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger btn-xoa-dong">✕</button></td>';
        body.appendChild(tr);
        khoiTaoSelect2(tr);
    });

    body?.addEventListener('click', function (e) {
        if (!e.target.classList.contains('btn-xoa-dong')) return;
        const rows = body.querySelectorAll('.row-phan-cong');
        if (rows.length <= 1) return;
        const tr = e.target.closest('tr');
        if (tr) {
            huySelect2(tr);
            tr.remove();
        }
        reindex();
    });

    async function fillKhoa() {
        const ma = maKh?.value;
        if (!ma) return;
        const url = @json(route('qldtoto.so-phan-cong-gv-xe-tap-lai.khoa', ['maKh' => '__MA__'])).replace('__MA__', encodeURIComponent(ma));
        try {
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            const tk = document.getElementById('ten_khoa');
            const td = document.getElementById('ten_khoa_doc');
            if (tk && data.ten_khoa) tk.value = data.ten_khoa;
            if (td && data.ten_khoa_doc) td.value = data.ten_khoa_doc;
        } catch (err) { /* bỏ qua */ }
    }
    $(function () {
        khoiTaoSelect2();
        $('#ma_kh').on('change', fillKhoa);
    });
})(jQuery);
</script>
@endpush
