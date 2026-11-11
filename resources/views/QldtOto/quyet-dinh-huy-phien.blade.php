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
                Mẫu <code>quyet-dinh-huy-phien-tu-choi-phien.docx</code> («Báo cáo phiên học lỗi») và file Excel danh sách cùng cột.
                Chọn khoá để nạp phiên DAT không đạt, chỉnh lý do rồi xuất Word, Excel hoặc ZIP.
            </p>

            <form method="GET" action="{{ route('qldtoto.quyet-dinh-huy-phien.index') }}" class="form-row align-items-end mb-3">
                <div class="form-group col-md-6 mb-0">
                    <label for="ma_kh_loc">Khoá (PMGPLX / DAT)</label>
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

            @php $v = fn (string $key, $def = '') => old($key, $form[$key] ?? $def); @endphp

            <form method="POST" id="form-huy-phien">
                @csrf
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="ngay_bao_cao">Ngày lập báo cáo</label>
                        <input type="date" class="form-control form-control-sm" id="ngay_bao_cao" name="ngay_bao_cao"
                               value="{{ $v('ngay_bao_cao', now()->format('Y-m-d')) }}" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="de_xuat">Đề xuất (đoạn cuối)</label>
                    <textarea class="form-control form-control-sm" id="de_xuat" name="de_xuat" rows="2">{{ $v('de_xuat') }}</textarea>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="mb-0 font-weight-bold">Danh sách phiên đề xuất hủy</label>
                    <div>
                        @if ($maKh !== '')
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-nap-dat">Nạp lại từ DAT</button>
                        @endif
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-them-dong">＋ Thêm dòng</button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-2" id="tbl-phien">
                        <thead class="thead-light">
                            <tr>
                                <th style="width:3rem">STT</th>
                                <th>Họ và tên</th>
                                <th>Mã học viên</th>
                                <th>Mã phiên học</th>
                                <th>Phương án</th>
                                <th>Lý do</th>
                                <th style="width:3rem"></th>
                            </tr>
                        </thead>
                        <tbody id="tbody-phien">
                            @php
                                $rows = old('phien', $form['phien']);
                            @endphp
                            @foreach ($rows as $i => $row)
                                <tr class="row-phien">
                                    <td class="text-center align-middle stt-label">{{ $i + 1 }}</td>
                                    <td><input type="text" class="form-control form-control-sm" name="phien[{{ $i }}][ho_ten]" value="{{ $row['ho_ten'] ?? '' }}"></td>
                                    <td><input type="text" class="form-control form-control-sm" name="phien[{{ $i }}][ma_hv]" value="{{ $row['ma_hv'] ?? '' }}"></td>
                                    <td><input type="text" class="form-control form-control-sm" name="phien[{{ $i }}][ma_phien]" value="{{ $row['ma_phien'] ?? '' }}"></td>
                                    <td><input type="text" class="form-control form-control-sm" name="phien[{{ $i }}][phuong_an]" value="{{ $row['phuong_an'] ?? \App\Support\QldtOto\QuyetDinhHuyPhienKemDanhSachDocx::PHUONG_AN_MAC_DINH }}"></td>
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
                    <button type="submit" formaction="{{ route('qldtoto.quyet-dinh-huy-phien.export-word') }}" class="btn btn-primary btn-sm">Xuất Word</button>
                    <button type="submit" formaction="{{ route('qldtoto.quyet-dinh-huy-phien.export-danh-sach') }}" class="btn btn-outline-primary btn-sm">Xuất Excel danh sách</button>
                    <button type="submit" formaction="{{ route('qldtoto.quyet-dinh-huy-phien.export-ca-hai') }}" class="btn btn-outline-secondary btn-sm">Xuất cả hai (ZIP)</button>
                </div>
            </form>
        </div>
    </div>

    <template id="tpl-row-phien">
        <tr class="row-phien">
            <td class="text-center align-middle stt-label">1</td>
            <td><input type="text" class="form-control form-control-sm" name="phien[__I__][ho_ten]" value=""></td>
            <td><input type="text" class="form-control form-control-sm" name="phien[__I__][ma_hv]" value=""></td>
            <td><input type="text" class="form-control form-control-sm" name="phien[__I__][ma_phien]" value=""></td>
            <td><input type="text" class="form-control form-control-sm" name="phien[__I__][phuong_an]" value="{{ \App\Support\QldtOto\QuyetDinhHuyPhienKemDanhSachDocx::PHUONG_AN_MAC_DINH }}"></td>
            <td><input type="text" class="form-control form-control-sm" name="phien[__I__][ly_do]" value=""></td>
            <td class="text-center align-middle">
                <button type="button" class="btn btn-link btn-sm text-danger p-0 btn-xoa-dong" title="Xóa dòng">×</button>
            </td>
        </tr>
    </template>
@endsection

@push('scripts')
    <script>
        (function () {
            const tbody = document.getElementById('tbody-phien');
            const tpl = document.getElementById('tpl-row-phien');
            const phuongAnMacDinh = @json(\App\Support\QldtOto\QuyetDinhHuyPhienKemDanhSachDocx::PHUONG_AN_MAC_DINH);
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
                    tr.querySelector('[name$="[ho_ten]"]').value = data.ho_ten || '';
                    tr.querySelector('[name$="[ma_hv]"]').value = data.ma_hv || '';
                    tr.querySelector('[name$="[ma_phien]"]').value = data.ma_phien || '';
                    tr.querySelector('[name$="[phuong_an]"]').value = data.phuong_an || phuongAnMacDinh;
                    tr.querySelector('[name$="[ly_do]"]').value = data.ly_do || '';
                }
                reindexRows();
            }

            document.getElementById('btn-them-dong').addEventListener('click', function () {
                addRow(null);
            });

            tbody.addEventListener('click', function (e) {
                if (e.target.closest('.btn-xoa-dong')) {
                    const rows = tbody.querySelectorAll('.row-phien');
                    if (rows.length <= 1) {
                        return;
                    }
                    e.target.closest('.row-phien').remove();
                    reindexRows();
                }
            });

            const btnNap = document.getElementById('btn-nap-dat');
            if (btnNap && napUrl) {
                btnNap.addEventListener('click', function () {
                    fetch(napUrl, { headers: { 'Accept': 'application/json' } })
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
        })();
    </script>
@endpush
