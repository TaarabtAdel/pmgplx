@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Phân quyền')

@php
    // Trải cây Tab → Menu 1 → Menu 2 thành các dòng bảng.
    $dong = [];
    $trai = function (array $nodes, int $cap, string $tab) use (&$trai, &$dong): void {
        foreach ($nodes as $n) {
            $tabCuaDong = $cap === 0 ? $n['khoa'] : $tab;
            $dong[] = ['khoa' => $n['khoa'], 'title' => $n['title'], 'cap' => $cap, 'la' => $n['children'] === [], 'tab' => $tabCuaDong];
            $trai($n['children'], $cap + 1, $tabCuaDong);
        }
    };
    $trai($cay, 0, '');
@endphp

@push('styles')
<style>
    .pq-bang { table-layout: fixed; }
    .pq-bang th.pq-vt, .pq-bang td.pq-vt { width: 7.5rem; text-align: center; vertical-align: middle; }
    .pq-bang tr.pq-cap-0 td { background: #e9eef5; font-weight: 700; }
    .pq-bang tr.pq-cap-0 td.pq-ten { cursor: pointer; user-select: none; }
    .pq-bang tr.pq-cap-1:not(.pq-la) td { background: #f6f8fb; font-weight: 600; }
    .pq-bang td.pq-ten { overflow-wrap: anywhere; }
    .pq-bang tr.pq-cap-1 td.pq-ten { padding-left: 1.75rem; }
    .pq-bang tr.pq-cap-2 td.pq-ten { padding-left: 3.5rem; }
    .pq-bang input[type=checkbox] { width: 1.05rem; height: 1.05rem; cursor: pointer; }
    .pq-bang .pq-mui { display: inline-block; width: 1rem; color: #6c757d; }
    .pq-bang thead th { position: sticky; top: 0; z-index: 2; background: #fff; }
</style>
@endpush

@section('content')
    <div class="card card-panel">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Ma trận phân quyền — Tab → Menu 1 → Menu 2</span>
            <span class="small text-muted">{{ $soLa }} chức năng</span>
        </div>
        <div class="card-body">
            <p class="small text-muted mb-2">
                Tích ô của vai trò để cho phép dùng chức năng. Tích ở Tab / Menu 1 là chọn hết các mục bên dưới.
                Nhánh menu sâu hơn Menu 2 đi theo Menu 2. <strong>Admin</strong> luôn đủ quyền.
                Người dùng nhiều vai trò được gộp quyền các vai trò. Chức năng không được phép sẽ ẩn khỏi menu và bị chặn khi mở trực tiếp.
            </p>

            <form method="POST" action="{{ route('hethong.phan-quyen.update') }}" id="form-phan-quyen">
                @csrf
                @method('PUT')

                <div class="d-flex flex-wrap align-items-center mb-2">
                    <input type="text" class="form-control form-control-sm mr-2 mb-1" id="pq-loc" placeholder="Lọc chức năng…" style="max-width: 18rem;">
                    <button type="button" class="btn btn-sm btn-outline-secondary mr-1 mb-1" id="pq-mo-het">Mở hết</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary mr-auto mb-1" id="pq-thu-het">Thu gọn tab</button>
                    <button type="submit" class="btn btn-sm btn-navy mb-1">Lưu phân quyền</button>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered pq-bang mb-2">
                        <thead class="thead-light">
                            <tr>
                                <th>Chức năng</th>
                                <th class="pq-vt">Admin</th>
                                @foreach ($vaiTroSua as $ma => $ten)
                                    <th class="pq-vt">
                                        {{ $ten }}
                                        <div class="small font-weight-normal text-muted"><span class="pq-dem" data-vt="{{ $ma }}"></span>/{{ $soLa }}</div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dong as $d)
                                <tr class="pq-cap-{{ $d['cap'] }} {{ $d['la'] ? 'pq-la' : '' }}" data-khoa="{{ $d['khoa'] }}" data-tab="{{ $d['tab'] }}">
                                    <td class="pq-ten">
                                        @if ($d['cap'] === 0)<span class="pq-mui">▾</span>@endif{{ $d['title'] }}
                                    </td>
                                    <td class="pq-vt text-success">✓</td>
                                    @foreach ($vaiTroSua as $ma => $ten)
                                        <td class="pq-vt">
                                            @if ($d['la'])
                                                <input type="checkbox" class="pq-chk pq-la-chk" name="quyen[{{ $ma }}][]" value="{{ $d['khoa'] }}"
                                                       data-vt="{{ $ma }}" data-khoa="{{ $d['khoa'] }}" @checked(isset($daCap[$ma][$d['khoa']]))
                                                       title="{{ $ten }} — {{ $d['title'] }}">
                                            @else
                                                <input type="checkbox" class="pq-chk pq-nhom-chk" data-vt="{{ $ma }}" data-khoa="{{ $d['khoa'] }}"
                                                       title="{{ $ten }} — chọn hết «{{ $d['title'] }}»">
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="text-right">
                    <button type="submit" class="btn btn-sm btn-navy">Lưu phân quyền</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const bang = document.querySelector('.pq-bang');
    const la = Array.from(bang.querySelectorAll('.pq-la-chk'));
    const nhom = Array.from(bang.querySelectorAll('.pq-nhom-chk'));

    function laCon(vt, khoa) {
        return la.filter(function (c) { return c.dataset.vt === vt && c.dataset.khoa.indexOf(khoa + '/') === 0; });
    }

    function capNhatNhom() {
        nhom.forEach(function (n) {
            const con = laCon(n.dataset.vt, n.dataset.khoa);
            const soTich = con.filter(function (c) { return c.checked; }).length;
            n.checked = con.length > 0 && soTich === con.length;
            n.indeterminate = soTich > 0 && soTich < con.length;
        });
        document.querySelectorAll('.pq-dem').forEach(function (el) {
            el.textContent = la.filter(function (c) { return c.dataset.vt === el.dataset.vt && c.checked; }).length;
        });
    }

    bang.addEventListener('change', function (e) {
        const c = e.target;
        if (c.classList.contains('pq-nhom-chk')) {
            laCon(c.dataset.vt, c.dataset.khoa).forEach(function (x) { x.checked = c.checked; });
        }
        capNhatNhom();
    });

    // Bấm tên tab: thu gọn / mở các dòng của tab.
    function datMoTab(tab, mo) {
        bang.querySelectorAll('tr[data-tab="' + tab + '"]:not(.pq-cap-0)').forEach(function (tr) { tr.hidden = !mo; });
        const dau = bang.querySelector('tr.pq-cap-0[data-khoa="' + tab + '"] .pq-mui');
        if (dau) dau.textContent = mo ? '▾' : '▸';
    }
    bang.addEventListener('click', function (e) {
        const td = e.target.closest('tr.pq-cap-0 td.pq-ten');
        if (!td) return;
        const tr = td.parentElement;
        const dangMo = !bang.querySelector('tr[data-tab="' + tr.dataset.khoa + '"]:not(.pq-cap-0)[hidden]');
        datMoTab(tr.dataset.khoa, !dangMo);
    });
    const tabs = Array.from(bang.querySelectorAll('tr.pq-cap-0')).map(function (tr) { return tr.dataset.khoa; });
    document.getElementById('pq-mo-het').addEventListener('click', function () { tabs.forEach(function (t) { datMoTab(t, true); }); });
    document.getElementById('pq-thu-het').addEventListener('click', function () { tabs.forEach(function (t) { datMoTab(t, false); }); });

    // Lọc: giữ dòng khớp + các dòng cha của nó.
    document.getElementById('pq-loc').addEventListener('input', function () {
        const q = this.value.trim().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd');
        const rows = Array.from(bang.querySelectorAll('tbody tr'));
        if (!q) { rows.forEach(function (tr) { tr.hidden = false; }); tabs.forEach(function (t) { datMoTab(t, true); }); return; }
        const khop = new Set();
        rows.forEach(function (tr) {
            const ten = tr.querySelector('.pq-ten').textContent.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd');
            if (ten.indexOf(q) >= 0) {
                const parts = tr.dataset.khoa.split('/');
                for (let i = 1; i <= parts.length; i++) khop.add(parts.slice(0, i).join('/'));
                rows.forEach(function (r) { if (r.dataset.khoa.indexOf(tr.dataset.khoa + '/') === 0) khop.add(r.dataset.khoa); });
            }
        });
        rows.forEach(function (tr) { tr.hidden = !khop.has(tr.dataset.khoa); });
    });

    capNhatNhom();
})();
</script>
@endpush
