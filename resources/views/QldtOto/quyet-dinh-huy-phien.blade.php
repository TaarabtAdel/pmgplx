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
                Xuất <strong>Quyết định huỷ phiên</strong> kèm danh sách phiên huỷ (Word có chữ ký Giám đốc)
                và file Excel danh sách. Dữ liệu phiên lấy từ bảng bên dưới hoặc
                <a href="{{ route('qldtoto.bao-cao-phien-hoc-loi.index') }}">Báo cáo phiên học lỗi</a> (nạp từ DAT).
            </p>

            <form method="GET" action="{{ route('qldtoto.quyet-dinh-huy-phien.index') }}" class="form-row align-items-end mb-3">
                <div class="form-group col-md-6 mb-0">
                    <label for="ma_kh_loc">Khoá (gợi ý nạp nhanh từ DAT)</label>
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

            @php
                $v = fn (string $key, $def = '') => old($key, $form[$key] ?? $def);
                $paMacDinh = \App\Support\QldtOto\BaoCaoPhienHocLoiDocx::PHUONG_AN_MAC_DINH;
            @endphp

            <form method="POST" id="form-huy-phien">
                @csrf
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="so_qd">Số quyết định</label>
                        <input type="text" class="form-control form-control-sm" id="so_qd" name="so_qd" value="{{ $v('so_qd') }}" maxlength="20" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="ngay_quyet_dinh">Ngày quyết định</label>
                        <input type="date" class="form-control form-control-sm" id="ngay_quyet_dinh" name="ngay_quyet_dinh"
                               value="{{ $v('ngay_quyet_dinh', now()->format('Y-m-d')) }}" required>
                    </div>
                </div>

                @if ($maKh !== '')
                    <div class="form-group">
                        <label for="tim-hv">Thêm học viên (theo khoá đã chọn)</label>
                        <select class="form-control form-control-sm" id="tim-hv" data-ma-kh="{{ $maKh }}"></select>
                        <small class="text-muted">Chọn học viên → tự điền họ tên, mã học viên, khóa và giáo viên (nếu có trên DAT).</small>
                    </div>
                @endif

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="mb-0 font-weight-bold">Danh sách phiên huỷ</label>
                    <div>
                        @if ($maKh !== '')
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-nap-dat">Nạp từ DAT</button>
                        @endif
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-them-dong">＋ Thêm dòng</button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-2" id="tbl-phien">
                        <thead class="thead-light">
                            <tr>
                                <th style="width:2.5rem">STT</th>
                                <th>Khóa học</th>
                                <th>Giáo viên</th>
                                <th>Họ và tên</th>
                                <th>Mã học viên</th>
                                <th>Mã phiên</th>
                                <th>Phương án</th>
                                <th>Lý do</th>
                                <th style="width:2.5rem"></th>
                            </tr>
                        </thead>
                        <tbody id="tbody-phien">
                            @foreach (old('phien', $form['phien']) as $i => $row)
                                <tr class="row-phien">
                                    <td class="text-center align-middle stt-label">{{ $i + 1 }}</td>
                                    <td><input type="text" class="form-control form-control-sm" name="phien[{{ $i }}][ten_khoa]" value="{{ $row['ten_khoa'] ?? '' }}"></td>
                                    <td><input type="text" class="form-control form-control-sm" name="phien[{{ $i }}][ho_ten_gv]" value="{{ $row['ho_ten_gv'] ?? '' }}"></td>
                                    <td><input type="text" class="form-control form-control-sm" name="phien[{{ $i }}][ho_ten]" value="{{ $row['ho_ten'] ?? '' }}"></td>
                                    <td><input type="text" class="form-control form-control-sm" name="phien[{{ $i }}][ma_hv]" value="{{ $row['ma_hv'] ?? '' }}"></td>
                                    <td><input type="text" class="form-control form-control-sm" name="phien[{{ $i }}][ma_phien]" value="{{ $row['ma_phien'] ?? '' }}"></td>
                                    <td><input type="text" class="form-control form-control-sm" name="phien[{{ $i }}][phuong_an]" value="{{ $row['phuong_an'] ?? $paMacDinh }}"></td>
                                    <td><input type="text" class="form-control form-control-sm" name="phien[{{ $i }}][ly_do]" value="{{ $row['ly_do'] ?? '' }}"></td>
                                    <td class="text-center align-middle">
                                        <button type="button" class="btn btn-link btn-sm text-danger p-0 btn-xoa-dong" title="Xóa dòng">×</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="btn-group flex-wrap" role="group">
                    <button type="submit" formaction="{{ route('qldtoto.quyet-dinh-huy-phien.export-word') }}" class="btn btn-primary btn-sm">Xuất Word quyết định</button>
                    <button type="submit" formaction="{{ route('qldtoto.quyet-dinh-huy-phien.export-danh-sach') }}" class="btn btn-outline-primary btn-sm">Xuất Excel danh sách</button>
                    <button type="submit" formaction="{{ route('qldtoto.quyet-dinh-huy-phien.export-ca-hai') }}" class="btn btn-outline-secondary btn-sm">Xuất cả hai (ZIP)</button>
                </div>
            </form>
        </div>
    </div>

    <template id="tpl-row-phien">
        <tr class="row-phien">
            <td class="text-center align-middle stt-label">1</td>
            <td><input type="text" class="form-control form-control-sm" name="phien[__I__][ten_khoa]" value="{{ $tenKhoa }}"></td>
            <td><input type="text" class="form-control form-control-sm" name="phien[__I__][ho_ten_gv]" value=""></td>
            <td><input type="text" class="form-control form-control-sm" name="phien[__I__][ho_ten]" value=""></td>
            <td><input type="text" class="form-control form-control-sm" name="phien[__I__][ma_hv]" value=""></td>
            <td><input type="text" class="form-control form-control-sm" name="phien[__I__][ma_phien]" value=""></td>
            <td><input type="text" class="form-control form-control-sm" name="phien[__I__][phuong_an]" value="{{ $paMacDinh }}"></td>
            <td><input type="text" class="form-control form-control-sm" name="phien[__I__][ly_do]" value=""></td>
            <td class="text-center align-middle">
                <button type="button" class="btn btn-link btn-sm text-danger p-0 btn-xoa-dong" title="Xóa dòng">×</button>
            </td>
        </tr>
    </template>
@endsection

@push('scripts')
<script>
(function ($) {
    $(function () {
        const lang = { noResults: function () { return 'Không tìm thấy'; } };
        const $khoaLoc = $('#ma_kh_loc');
        $khoaLoc.select2({
            theme: 'bootstrap4',
            width: '100%',
            allowClear: true,
            placeholder: '— Chọn khoá —',
            dropdownParent: $khoaLoc.closest('.form-group'),
            language: lang,
        });
        $khoaLoc.on('select2:select', function () {
            this.form.submit();
        });

        const tbody = document.getElementById('tbody-phien');
            const tpl = document.getElementById('tpl-row-phien');
            const phuongAnMacDinh = @json($paMacDinh);
            const tenKhoaMacDinh = @json($tenKhoa);
            const maKh = @json($maKh);
            const napUrl = maKh ? @json(route('qldtoto.quyet-dinh-huy-phien.khoa', ['maKh' => $maKh ?: '0'])) : '';

            function reindexRows() {
                tbody.querySelectorAll('.row-phien').forEach((tr, i) => {
                    tr.querySelector('.stt-label').textContent = String(i + 1);
                    tr.querySelectorAll('input').forEach(inp => {
                        inp.name = inp.name.replace(/phien\[\d+]/, 'phien[' + i + ']');
                    });
                });
            }

            function addRow(data) {
                const i = tbody.querySelectorAll('.row-phien').length;
                const html = tpl.innerHTML.replace(/__I__/g, String(i));
                tbody.insertAdjacentHTML('beforeend', html);
                const tr = tbody.lastElementChild;
                if (data) {
                    tr.querySelector('[name$="[ten_khoa]"]').value = data.ten_khoa || tenKhoaMacDinh || '';
                    tr.querySelector('[name$="[ho_ten_gv]"]').value = data.ho_ten_gv || '';
                    tr.querySelector('[name$="[ho_ten]"]').value = data.ho_ten || '';
                    tr.querySelector('[name$="[ma_hv]"]').value = data.ma_hv || '';
                    tr.querySelector('[name$="[ma_phien]"]').value = data.ma_phien || '';
                    tr.querySelector('[name$="[phuong_an]"]').value = data.phuong_an || phuongAnMacDinh;
                    tr.querySelector('[name$="[ly_do]"]').value = data.ly_do || '';
                }
                reindexRows();
            }

            document.getElementById('btn-them-dong').addEventListener('click', () => addRow(null));

            tbody.addEventListener('click', function (e) {
                if (!e.target.closest('.btn-xoa-dong')) return;
                if (tbody.querySelectorAll('.row-phien').length <= 1) return;
                e.target.closest('.row-phien').remove();
                reindexRows();
            });

            document.getElementById('so_qd')?.addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '');
            });

            const btnNap = document.getElementById('btn-nap-dat');
            if (btnNap && napUrl) {
                btnNap.addEventListener('click', function () {
                    fetch(napUrl, { headers: { Accept: 'application/json' } })
                        .then(r => r.json())
                        .then(data => {
                            tbody.innerHTML = '';
                            const phien = data.phien || [];
                            if (phien.length === 0) {
                                addRow(null);
                                alert('Không có phiên không đạt cho khoá này.');
                                return;
                            }
                            phien.forEach(row => addRow(row));
                        })
                        .catch(() => alert('Không nạp được dữ liệu DAT.'));
                });
            }

            const $tim = $('#tim-hv');
            if ($tim.length && maKh) {
                $tim.select2({
                    theme: 'bootstrap4',
                    width: '100%',
                    placeholder: 'Gõ tên hoặc mã học viên…',
                    minimumInputLength: 2,
                    ajax: {
                        url: @json(route('qldtoto.bao-cao-phien-hoc-loi.tim-hoc-vien')),
                        delay: 300,
                        data: p => ({ q: p.term, ma_kh: maKh }),
                        processResults: data => data,
                    },
                    language: {
                        inputTooShort: () => 'Gõ ít nhất 2 ký tự',
                        noResults: () => 'Không tìm thấy học viên',
                        searching: () => 'Đang tìm…',
                    },
                });
                $tim.on('select2:select', function (e) {
                    const hv = e.params.data.hv || {};
                    addRow({
                        ten_khoa: hv.ten_khoa,
                        ho_ten_gv: hv.ho_ten_gv,
                        ho_ten: hv.ho_ten,
                        ma_hv: hv.ma_hv,
                        phuong_an: phuongAnMacDinh,
                        ly_do: '',
                    });
                    $tim.val(null).trigger('change');
                });
            }
    });
})(jQuery);
</script>
@endpush