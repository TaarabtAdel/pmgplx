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
                Dữ liệu gợi ý từ
                <a href="{{ $linkDanhSach }}" target="_blank">Phân công đào tạo → Danh sách</a>
                (MANHLINH). Mỗi xe có thể gán nhiều giáo viên. Mẫu Word sẽ bổ sung sau — hiện chỉ <strong>xem trước</strong>.
            </p>

            <form method="GET" action="{{ route('qldtoto.quyet-dinh-dieu-dong-xe.index') }}" class="form-row align-items-end mb-3" id="form-chon-khoa">
                <div class="form-group col-md-6 mb-0">
                    <label for="ma_kh_loc">Khoá (PMGPLX)</label>
                    <select class="form-control form-control-sm chon-s2 sel-khoa-pmgplx" id="ma_kh_loc" name="ma_kh" data-placeholder="— Chọn khoá —">
                        <option value=""></option>
                        @foreach ($khoaOptions as $kh)
                            <option value="{{ $kh->MaKH }}" @selected($form['ma_kh'] === $kh->MaKH)>
                                {{ $kh->TenKH }} ({{ $kh->MaKH }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2 mb-0">
                    <button type="submit" class="btn btn-sm btn-primary btn-block">Chọn khoá</button>
                </div>
            </form>

            @if ($form['ma_kh'] === '')
                <p class="text-muted mb-0">Chọn khoá để tải xe và giáo viên từ phân công đào tạo (hoặc nhập thủ công nếu chưa có phân công).</p>
            @else
                @if ($canhBaoPhanCong === 'chua_phan_cong')
                    <div class="alert alert-warning">
                        <strong>Chưa có phân công giáo viên / xe cho khoá này.</strong>
                        Bạn vẫn có thể nhập xe và giáo viên bên dưới. Để điền tự động từ sổ phân công:
                        <ol class="mb-0 mt-2 pl-3">
                            <li class="mb-1">
                                <a href="{{ route('daotao.pdt.cong-cu-nhap.nhap-file-so-phan-cong-giao-vien') }}">Nhập file sổ phân công giáo viên</a>
                            </li>
                            <li>
                                <a href="{{ route('qldtoto.so-phan-cong-gv-xe-tap-lai.index', ['ma_kh' => $form['ma_kh']]) }}">Sổ phân công GV &amp; xe (QLĐT Ô TÔ)</a>
                                · <a href="{{ $linkDanhSach }}">Phân công đào tạo</a>
                            </li>
                        </ol>
                    </div>
                @elseif ($canhBaoPhanCong === 'co_phan_cong_chua_xe')
                    <div class="alert alert-warning">
                        <strong>Đã có {{ number_format($soPhanCong) }} dòng phân công</strong> nhưng chưa gán xe tập lái — chưa tải được nhóm theo xe.
                        Nhập thủ công bên dưới hoặc cập nhật biển số trên
                        <a href="{{ $linkDanhSach }}">danh sách phân công</a>.
                    </div>
                @endif

                <form method="POST" action="{{ route('qldtoto.quyet-dinh-dieu-dong-xe.preview.store') }}" id="form-dieu-dong">
                    @csrf
                    <input type="hidden" name="ma_kh" value="{{ $form['ma_kh'] }}">
                    <div class="form-row mb-3">
                        <div class="form-group col-md-5 mb-0">
                            <label for="ten_khoa">Tên khoá (Phân công đào tạo)</label>
                            <input type="text" class="form-control form-control-sm" id="ten_khoa" name="ten_khoa"
                                   value="{{ old('ten_khoa', $form['ten_khoa']) }}" required>
                        </div>
                        <div class="form-group col-md-7 mb-0 d-flex align-items-end">
                            <span class="small text-muted">
                                PMGPLX: <code>{{ $form['ma_kh'] }}</code>
                                @if ($khoaDaoTaoId)
                                    · KhoaDaoTao #{{ $khoaDaoTaoId }}
                                @endif
                            </span>
                        </div>
                    </div>
                    <div class="form-row mb-3">
                        <div class="form-group col-md-3 mb-0">
                            <label for="ngay_quyet_dinh">Ngày quyết định</label>
                            <input type="date" class="form-control form-control-sm" id="ngay_quyet_dinh" name="ngay_quyet_dinh"
                                   value="{{ old('ngay_quyet_dinh', $form['ngay_quyet_dinh']) }}" required>
                            <small class="text-muted">«Quảng Trị, ngày … tháng … năm …»</small>
                            @error('ngay_quyet_dinh')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group col-md-5 mb-0">
                            <label for="hang_khoa">Hạng / khóa</label>
                            <input type="text" class="form-control form-control-sm" id="hang_khoa" name="hang_khoa"
                                   value="{{ old('hang_khoa', $form['hang_khoa']) }}" maxlength="150" required
                                   placeholder="vd. Hạng B (Số sàn) khóa 56">
                            @error('hang_khoa')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div id="xe-blocks">
                        @php $blocks = old('xe_blocks', $form['xe_blocks']); @endphp
                        @foreach ($blocks as $xi => $block)
                            @include('QldtOto.quyet-dinh-dieu-dong-xe.partials.xe-block', [
                                'xi' => $xi,
                                'block' => $block,
                                'noiDungOptions' => $noiDungOptions,
                            ])
                        @endforeach
                    </div>

                    <button type="button" class="btn btn-sm btn-outline-primary mb-3" id="btn-them-xe">＋ Thêm xe</button>
                    @error('xe_blocks')<div class="text-danger small mb-2">{{ $message }}</div>@enderror

                    <div>
                        <button type="submit" class="btn btn-sm btn-navy">Xem trước</button>
                    </div>
                </form>
            @endif
        </div>
    </div>

    @if ($form['ma_kh'] !== '')
        <template id="tpl-xe-block">
            @include('QldtOto.quyet-dinh-dieu-dong-xe.partials.xe-block', [
                'xi' => '__XI__',
                'block' => ['bien_so' => '', 'so_quyet_dinh' => '', 'loai_xe' => '', 'nhan_hieu' => '', 'mau_son' => '', 'giao_vien' => [['ma_gv' => '', 'ho_ten' => '', 'tu_ngay' => '', 'den_ngay' => '', 'noi_dung' => 'GVTH']]],
                'noiDungOptions' => $noiDungOptions,
            ])
        </template>
        <template id="tpl-gv-row">
            @include('QldtOto.quyet-dinh-dieu-dong-xe.partials.gv-row', [
                'xi' => '__XI__',
                'gi' => '__GI__',
                'gv' => ['ma_gv' => '', 'ho_ten' => '', 'tu_ngay' => '', 'den_ngay' => '', 'noi_dung' => 'GVTH'],
                'noiDungOptions' => $noiDungOptions,
            ])
        </template>
        <template id="tpl-gv-select">
            @include('QldtOto.so-phan-cong-gv-xe-tap-lai.partials.giao-vien-select', [
                'name' => 'xe_blocks[__XI__][giao_vien][__GI__][ma_gv]',
                'selectedMa' => '',
                'selectedHoTen' => '',
            ])
        </template>
        <template id="tpl-xe-select">
            @include('QldtOto.so-phan-cong-gv-xe-tap-lai.partials.xe-select', [
                'name' => 'xe_blocks[__XI__][bien_so]',
                'selectedBienSo' => '',
            ])
        </template>
    @endif
@endsection

@push('scripts')
<script>
(function ($) {
    const select2Lang = {
        noResults: function () { return 'Không tìm thấy khoá học'; },
        removeAllItems: function () { return 'Bỏ chọn'; },
    };

    function initKhoaSelect2() {
        const $sel = $('#ma_kh_loc');
        if (! $sel.length || $sel.hasClass('select2-hidden-accessible')) {
            return;
        }
        $sel.select2({
            theme: 'bootstrap4',
            width: '100%',
            allowClear: true,
            placeholder: $sel.attr('data-placeholder') || '— Chọn khoá —',
            dropdownParent: $sel.closest('.form-group'),
            language: select2Lang,
        });
    }

    $(initKhoaSelect2);

    @if ($form['ma_kh'] === '')
    return;
    @endif

    const xeBlocks = document.getElementById('xe-blocks');
    const btnThemXe = document.getElementById('btn-them-xe');
    const xeThongTin = @json($xeThongTin ?? []);

    function soQdDau() {
        const first = xeBlocks.querySelector('.xe-block .inp-so-qd');
        const n = parseInt((first?.value || '').replace(/\D/g, ''), 10);
        return Number.isNaN(n) ? null : n;
    }

    function tangSoQdCacXeSau() {
        const n = soQdDau();
        if (n === null) return;
        xeBlocks.querySelectorAll('.xe-block').forEach(function (card, xi) {
            if (xi === 0) return;
            const inp = card.querySelector('.inp-so-qd');
            if (inp) inp.value = String(n + xi);
        });
    }

    function dienThongTinXe(card, bienSo) {
        const info = xeThongTin[bienSo] || { loai_xe: '', nhan_hieu: '', mau_son: '' };
        card.querySelector('.inp-loai-xe').value = info.loai_xe || '';
        card.querySelector('.inp-nhan-hieu').value = info.nhan_hieu || '';
        card.querySelector('.inp-mau-son').value = info.mau_son || '';
    }

    xeBlocks?.addEventListener('input', function (e) {
        const inp = e.target.closest('.inp-so-qd');
        if (!inp) return;
        inp.value = inp.value.replace(/\D/g, '');
        if (inp === xeBlocks.querySelector('.xe-block .inp-so-qd')) {
            tangSoQdCacXeSau();
        }
    });

    $(xeBlocks).on('change', 'select.sel-bien-so', function () {
        const card = this.closest('.xe-block');
        if (card) dienThongTinXe(card, this.value);
    });
    function khoiTaoSelect2(scope) {
        (scope || document).querySelectorAll('select.chon-s2').forEach(function (sel) {
            if ($(sel).hasClass('select2-hidden-accessible')) return;
            $(sel).select2({
                theme: 'bootstrap4',
                width: '100%',
                allowClear: !sel.classList.contains('sel-noi-dung'),
                placeholder: sel.getAttribute('data-placeholder') || '',
                language: select2Lang,
            });
        });
    }

    function huySelect2(el) {
        $(el).find('select.chon-s2').each(function () {
            if ($(this).hasClass('select2-hidden-accessible')) $(this).select2('destroy');
        });
    }

    function reindexXe() {
        xeBlocks.querySelectorAll('.xe-block').forEach(function (card, xi) {
            card.dataset.xeIndex = xi;
            card.querySelector('.xe-stt').textContent = xi + 1;
            card.querySelectorAll('[name^="xe_blocks["]').forEach(function (el) {
                el.name = el.name.replace(/xe_blocks\[\d+]/, 'xe_blocks[' + xi + ']');
            });
            card.querySelectorAll('.gv-row').forEach(function (tr, gi) {
                tr.querySelectorAll('[name*="[giao_vien]["]').forEach(function (el) {
                    el.name = el.name.replace(/\[giao_vien\]\[\d+]/, '[giao_vien][' + gi + ']');
                });
            });
        });
    }

    btnThemXe?.addEventListener('click', function () {
        const tpl = document.getElementById('tpl-xe-block');
        const xi = xeBlocks.querySelectorAll('.xe-block').length;
        const html = tpl.innerHTML.replace(/__XI__/g, String(xi));
        const wrap = document.createElement('div');
        wrap.innerHTML = html.trim();
        xeBlocks.appendChild(wrap.firstElementChild);
        reindexXe();
        khoiTaoSelect2(xeBlocks.lastElementChild);
        const n = soQdDau();
        const inp = xeBlocks.lastElementChild.querySelector('.inp-so-qd');
        if (n !== null && inp) inp.value = String(n + xi);
    });

    xeBlocks?.addEventListener('click', function (e) {
        const btn = e.target.closest('button');
        if (!btn) return;
        if (btn.classList.contains('btn-xoa-xe')) {
            const card = btn.closest('.xe-block');
            if (xeBlocks.querySelectorAll('.xe-block').length <= 1) {
                alert('Giữ ít nhất một xe.');
                return;
            }
            huySelect2(card);
            card.remove();
            reindexXe();
        }
        if (btn.classList.contains('btn-them-gv')) {
            const card = btn.closest('.xe-block');
            const xi = card.dataset.xeIndex;
            const tbody = card.querySelector('.gv-body');
            const gi = tbody.querySelectorAll('.gv-row').length;
            const tpl = document.getElementById('tpl-gv-row');
            const html = tpl.innerHTML.replace(/__XI__/g, xi).replace(/__GI__/g, String(gi));
            const tr = document.createElement('tr');
            tr.className = 'gv-row';
            tr.innerHTML = html.trim();
            tbody.appendChild(tr);
            reindexXe();
            khoiTaoSelect2(tr);
        }
        if (btn.classList.contains('btn-xoa-gv')) {
            const tr = btn.closest('.gv-row');
            const tbody = tr.closest('.gv-body');
            if (tbody.querySelectorAll('.gv-row').length <= 1) {
                alert('Mỗi xe cần ít nhất một giáo viên.');
                return;
            }
            huySelect2(tr);
            tr.remove();
            reindexXe();
        }
    });

    khoiTaoSelect2(document.getElementById('form-dieu-dong'));
})(jQuery);
</script>
@endpush
