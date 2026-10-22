@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Cấu hình bài dạy theo hạng')

@push('styles')
<style>
    .bai-day-table td { vertical-align: middle; }
    .bai-day-table .col-thu-tu { width: 5rem; white-space: nowrap; }
    .bai-day-table .col-ngay { width: 6.5rem; min-width: 5rem; }
    .bai-day-table .col-gio { width: 6.5rem; min-width: 5rem; }
    .bai-day-table .col-time { width: 9rem; min-width: 7rem; }
    .bai-day-table .col-xoa { width: 3rem; }
    .bai-day-table .o-noi-dung { min-width: 18rem; }
    .bai-day-table .tay-keo { cursor: grab; color: #6c757d; user-select: none; padding: 0 .35rem; font-size: 1.1rem; }
    .bai-day-table .tay-keo:active { cursor: grabbing; }
    .bai-day-table tr.dang-keo { background: #e8f0fe; }
    .bai-day-table tr.vi-tri-tha { opacity: .4; }
    .bai-day-table .o-bai-hoc { cursor: pointer; }
    .bai-day-table .o-gio { white-space: nowrap; }
    .bai-day-table .o-bai-hoc:hover { background: #fff8e1; }
    .bai-day-table .o-bai-hoc .text-muted { font-style: italic; }
    .bai-day-table .o-noi-dung .chua-chon { display: block; border: 1px dashed #dc3545; border-radius: .2rem; padding: .1rem .4rem; color: #dc3545; font-style: italic; }
    .bai-day-table td.tha-doi-bai { outline: 2px dashed #007bff; outline-offset: -3px; background: #e8f0fe; }
    .bai-day-table tr.chen-tren td { box-shadow: inset 0 3px 0 #007bff; }
    .bai-day-table tr.chen-duoi td { box-shadow: inset 0 -3px 0 #007bff; }
    #vung-tha-cuoi { border: 2px dashed #ced4da; color: #6c757d; }
    #vung-tha-cuoi.dang-tro { border-color: #007bff; background: #e8f0fe; color: #007bff; }
    .bai-hoc-panel { position: sticky; top: .5rem; }
    .bai-hoc-list { max-height: calc(100vh - 14rem); overflow-y: auto; }
    .bai-hoc-item { cursor: grab; user-select: none; }
    .bai-hoc-item:hover { background: #f8f9fa; }
    .bai-hoc-item .ten { font-weight: 600; }
    .bai-hoc-item .gio { font-size: .8rem; color: #6c757d; }
</style>
@endpush

@section('content')
    <div class="card card-panel">
        <div class="card-header">Cấu hình bài dạy theo hạng</div>
        <div class="card-body">
            <ul class="nav nav-tabs mb-3">
                @foreach ($hangOptions as $maHang => $nhanHangTab)
                    @foreach ($caOptions as $maCa => $nhanCa)
                        @php $dangChon = $maHang === $hang && $maCa === $ca; @endphp
                        <li class="nav-item">
                            <a class="nav-link {{ $dangChon ? 'active font-weight-bold' : '' }}"
                               href="{{ route('qldtoto.cau-hinh-bai-day.index', ['hang' => $maHang, 'ca' => $maCa]) }}">
                                Hạng {{ $nhanHangTab }} ({{ $nhanCa }})
                                <span class="badge badge-{{ $dangChon ? 'primary' : 'secondary' }} ml-1">{{ (int) ($soBai[$maHang.'|'.$maCa] ?? 0) }}</span>
                            </a>
                        </li>
                    @endforeach
                @endforeach
            </ul>

            <div id="bai-hoc-thong-bao" class="alert d-none py-2" role="alert"></div>

            <div class="row">
                <div class="col-xl-9 mb-3">
                    <p class="small text-muted mb-2">
                        Mỗi dòng là một ngày. Kéo bài học ở khung bên phải thả vào ô <strong>Nội dung</strong> để đổi bài,
                        thả vào giữa các dòng để chèn dòng mới. Giờ BĐ / Giờ KT lấy theo bài học — bấm vào Nội dung hoặc ô giờ để sửa bài học / khung giờ.
                        Giữ <strong>☰</strong> rồi kéo để đổi thứ tự. Để trống <strong>Số giờ</strong> mà mỗi ô giờ có đúng một mốc thì hệ thống tự tính.
                    </p>

                    <form method="POST" action="{{ route('qldtoto.cau-hinh-bai-day.update', ['hang' => $hang, 'ca' => $ca]) }}" id="form-bai-day">
                        @csrf
                        @method('PUT')
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0 bai-day-table">
                                <thead class="thead-light">
                                    <tr>
                                        <th class="col-thu-tu">Thứ tự</th>
                                        <th class="col-ngay">Ngày số</th>
                                        <th class="col-gio">Số giờ</th>
                                        <th>Nội dung</th>
                                        <th class="col-time">Giờ BĐ</th>
                                        <th class="col-time">Giờ KT</th>
                                        <th class="col-xoa"></th>
                                    </tr>
                                </thead>
                                <tbody id="bai-day-body">
                                    @foreach ($rows as $i => $r)
                                        <tr class="bai-day-row">
                                            <td class="col-thu-tu">
                                                <span class="tay-keo" role="button" aria-label="Kéo để đổi thứ tự" title="Kéo để đổi thứ tự">☰</span>
                                                <span class="so-thu-tu">{{ $i + 1 }}</span>
                                            </td>
                                            <td><input type="number" min="1" max="999" class="form-control form-control-sm" data-field="ngay_so"
                                                       name="bai[{{ $i }}][ngay_so]" value="{{ $r['ngay_so'] ?? '' }}" required></td>
                                            <td><input type="number" min="0" max="24" step="0.25" class="form-control form-control-sm" data-field="so_gio"
                                                       name="bai[{{ $i }}][so_gio]" value="{{ $r['so_gio'] ?? '' }}"></td>
                                            <td class="o-bai-hoc o-noi-dung" title="Kéo bài học vào để đổi; bấm để sửa bài học">
                                                <input type="hidden" class="bai-hoc-id" data-field="bai_hoc_id"
                                                       name="bai[{{ $i }}][bai_hoc_id]" value="{{ $r['bai_hoc_id'] ?? '' }}">
                                                <span class="ten-bai-hoc"></span>
                                            </td>
                                            <td class="o-bai-hoc o-gio gio-bd" title="Bấm để sửa khung giờ của bài học"></td>
                                            <td class="o-bai-hoc o-gio gio-kt" title="Bấm để sửa khung giờ của bài học"></td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-danger py-0 btn-xoa-dong" title="Xoá dòng">×</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div id="vung-tha-cuoi" class="text-center small py-2 mb-2 border-top-0">
                            <span id="bai-day-trong" class="{{ count($rows) > 0 ? 'd-none' : '' }}">Chưa có bài dạy cho {{ $nhanTab }}. </span>
                            Thả bài học vào đây để thêm dòng cuối
                        </div>

                        <div class="d-flex flex-wrap align-items-center">
                            <button type="button" class="btn btn-sm btn-outline-primary mr-2 mb-2" id="btn-them-dong">＋ Thêm dòng</button>
                            <span class="small text-muted mb-2 mr-auto">
                                Tổng: <strong id="bai-day-tong">{{ count($rows) }}</strong> bài
                                <span id="bai-day-chua-luu" class="text-warning font-weight-bold d-none ml-2">• Có thay đổi chưa lưu</span>
                            </span>
                            <button type="submit" class="btn btn-navy mb-2">Lưu {{ $nhanTab }}</button>
                        </div>
                    </form>
                </div>

                <div class="col-xl-3 mb-3">
                    <div class="card bai-hoc-panel">
                        <div class="card-header d-flex align-items-center py-2">
                            <span class="mr-auto">Bài học {{ $nhanTab }}</span>
                            <button type="button" class="btn btn-sm btn-outline-primary py-0" id="btn-them-bai-hoc">＋ Thêm</button>
                        </div>
                        <div class="small text-muted px-3 pt-2">
                            Riêng cho tab này. Kéo thả sang bảng; bấm ✎ để sửa — đổi khung giờ là mọi ngày đang gán bài đó đổi theo.
                        </div>
                        <ul class="list-group list-group-flush bai-hoc-list" id="bai-hoc-list"></ul>
                        <div class="card-body py-2 small text-muted d-none" id="bai-hoc-rong">
                            {{ $nhanTab }} chưa có bài học. Bấm <strong>＋ Thêm</strong> để tạo.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <template id="bai-day-mau">
        <tr class="bai-day-row">
            <td class="col-thu-tu">
                <span class="tay-keo" role="button" aria-label="Kéo để đổi thứ tự" title="Kéo để đổi thứ tự">☰</span>
                <span class="so-thu-tu"></span>
            </td>
            <td><input type="number" min="1" max="999" class="form-control form-control-sm" data-field="ngay_so" required></td>
            <td><input type="number" min="0" max="24" step="0.25" class="form-control form-control-sm" data-field="so_gio"></td>
            <td class="o-bai-hoc o-noi-dung" title="Kéo bài học vào để đổi; bấm để sửa bài học">
                <input type="hidden" class="bai-hoc-id" data-field="bai_hoc_id">
                <span class="ten-bai-hoc"></span>
            </td>
            <td class="o-bai-hoc o-gio gio-bd" title="Bấm để sửa khung giờ của bài học"></td>
            <td class="o-bai-hoc o-gio gio-kt" title="Bấm để sửa khung giờ của bài học"></td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger py-0 btn-xoa-dong" title="Xoá dòng">×</button>
            </td>
        </tr>
    </template>

    <div class="modal fade" id="modalBaiHoc" tabindex="-1" role="dialog" aria-labelledby="modalBaiHocLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form class="modal-content" id="form-bai-hoc" autocomplete="off">
                <div class="modal-header py-2">
                    <h5 class="modal-title" id="modalBaiHocLabel">Bài học</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger py-2 d-none" id="bai-hoc-loi"></div>
                    <div class="form-group">
                        <label for="bai-hoc-ten">Tên bài học — {{ $nhanTab }}</label>
                        <input type="text" class="form-control" id="bai-hoc-ten" maxlength="500" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-6">
                            <label for="bai-hoc-gio-bd">Giờ BĐ</label>
                            <input type="text" class="form-control" id="bai-hoc-gio-bd" maxlength="50" placeholder="vd 5h59 hoặc 13H59' -17H59'">
                        </div>
                        <div class="form-group col-6">
                            <label for="bai-hoc-gio-kt">Giờ KT</label>
                            <input type="text" class="form-control" id="bai-hoc-gio-kt" maxlength="50" placeholder="vd 13h59 hoặc 18h00-22h00">
                        </div>
                    </div>
                    <div class="small text-info d-none" id="bai-hoc-ap-dung"></div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-outline-danger mr-auto d-none" id="btn-xoa-bai-hoc">Xoá bài học</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-navy" id="btn-luu-bai-hoc">Lưu</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<script>
    (function () {
        var dsBaiHoc = @json($baiHocs);
        var urlBaiHoc = @json(route('qldtoto.cau-hinh-bai-day.bai-hoc.store', ['hang' => $hang, 'ca' => $ca]));
        var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        var body = document.getElementById('bai-day-body');
        var form = document.getElementById('form-bai-day');
        var chuaLuu = document.getElementById('bai-day-chua-luu');
        var vungThaCuoi = document.getElementById('vung-tha-cuoi');
        var thongBao = document.getElementById('bai-hoc-thong-bao');
        var daDoi = false;

        function timBaiHoc(id) {
            id = parseInt(id, 10);
            for (var i = 0; i < dsBaiHoc.length; i++) {
                if (dsBaiHoc[i].id === id) {
                    return dsBaiHoc[i];
                }
            }
            return null;
        }

        function danhDauDaDoi() {
            daDoi = true;
            chuaLuu.classList.remove('d-none');
        }

        function baoThongBao(loai, noiDung) {
            thongBao.className = 'alert alert-' + loai + ' py-2';
            thongBao.textContent = noiDung;
        }

        // Tên input theo vị trí hiện tại => thứ tự lưu = thứ tự trên màn.
        function danhSoLai() {
            var rows = body.querySelectorAll('.bai-day-row');
            rows.forEach(function (tr, i) {
                tr.querySelector('.so-thu-tu').textContent = String(i + 1);
                tr.querySelectorAll('[data-field]').forEach(function (inp) {
                    inp.name = 'bai[' + i + '][' + inp.getAttribute('data-field') + ']';
                });
            });
            document.getElementById('bai-day-trong').classList.toggle('d-none', rows.length > 0);
            document.getElementById('bai-day-tong').textContent = String(rows.length);
        }

        function oGio(td, text) {
            td.innerHTML = '';
            var span = document.createElement('span');
            if (text) {
                span.textContent = text;
            } else {
                span.className = 'text-muted';
                span.textContent = '—';
            }
            td.appendChild(span);
        }

        function capNhatDong(tr) {
            var bai = timBaiHoc(tr.querySelector('.bai-hoc-id').value);
            var ten = tr.querySelector('.ten-bai-hoc');
            ten.className = 'ten-bai-hoc' + (bai ? '' : ' chua-chon');
            ten.textContent = bai ? bai.ten : 'Kéo bài học thả vào đây';
            oGio(tr.querySelector('.gio-bd'), bai ? bai.gio_bd : '');
            oGio(tr.querySelector('.gio-kt'), bai ? bai.gio_kt : '');
        }

        function ganBaiHoc(tr, baiHocId) {
            tr.querySelector('.bai-hoc-id').value = baiHocId || '';
            capNhatDong(tr);
        }

        // Danh sách bài học đổi (thêm/sửa/xoá) => vẽ lại tên + giờ của mọi dòng.
        function lamMoiBang() {
            body.querySelectorAll('.bai-day-row').forEach(capNhatDong);
        }

        function veDanhSachBaiHoc() {
            var ul = document.getElementById('bai-hoc-list');
            ul.innerHTML = '';
            dsBaiHoc.forEach(function (b) {
                var li = document.createElement('li');
                li.className = 'list-group-item px-3 py-2 d-flex align-items-start bai-hoc-item';
                li.draggable = true;
                li.setAttribute('data-id', String(b.id));
                li.title = 'Kéo thả vào bảng bên trái';

                var info = document.createElement('div');
                info.className = 'mr-auto';
                var ten = document.createElement('div');
                ten.className = 'ten';
                ten.textContent = b.ten;
                var gio = document.createElement('div');
                gio.className = 'gio';
                gio.textContent = (b.gio_bd || '—') + ' → ' + (b.gio_kt || '—');
                info.appendChild(ten);
                info.appendChild(gio);

                var phai = document.createElement('div');
                phai.className = 'text-nowrap ml-2';
                var badge = document.createElement('span');
                badge.className = 'badge badge-light mr-1';
                badge.title = 'Số ngày đã lưu đang gán bài này';
                badge.textContent = b.so_ngay + ' ngày';
                var sua = document.createElement('button');
                sua.type = 'button';
                sua.className = 'btn btn-sm btn-link p-0 btn-sua-bai-hoc';
                sua.title = 'Sửa bài học / khung giờ';
                sua.setAttribute('aria-label', 'Sửa bài học ' + b.ten);
                sua.textContent = '✎';
                phai.appendChild(badge);
                phai.appendChild(sua);

                li.appendChild(info);
                li.appendChild(phai);
                ul.appendChild(li);
            });
            document.getElementById('bai-hoc-rong').classList.toggle('d-none', dsBaiHoc.length > 0);
        }

        function taoDong(ngaySo) {
            var tr = document.getElementById('bai-day-mau').content.firstElementChild.cloneNode(true);
            tr.querySelector('[data-field="ngay_so"]').value = ngaySo;
            return tr;
        }

        function ngaySoCuaDong(tr) {
            return tr ? parseInt(tr.querySelector('[data-field="ngay_so"]').value, 10) || 0 : 0;
        }

        function chenDong(baiHocId, truocDong) {
            var dongTren = truocDong ? truocDong.previousElementSibling : body.lastElementChild;
            var tr = taoDong(ngaySoCuaDong(dongTren) + 1);
            body.insertBefore(tr, truocDong || null);
            ganBaiHoc(tr, baiHocId);
            danhSoLai();
            danhDauDaDoi();
            return tr;
        }

        // --- Bảng ---
        lamMoiBang();
        veDanhSachBaiHoc();

        if (window.Sortable) {
            Sortable.create(body, {
                handle: '.tay-keo',
                draggable: '.bai-day-row',
                animation: 150,
                chosenClass: 'dang-keo',
                ghostClass: 'vi-tri-tha',
                onEnd: function (evt) {
                    if (evt.oldIndex !== evt.newIndex) {
                        danhSoLai();
                        danhDauDaDoi();
                    }
                }
            });
        }

        document.getElementById('btn-them-dong').addEventListener('click', function () {
            var rows = body.querySelectorAll('.bai-day-row');
            var last = rows.length ? rows[rows.length - 1] : null;
            var tr = taoDong(last ? last.querySelector('[data-field="ngay_so"]').value : 1);
            body.appendChild(tr);
            ganBaiHoc(tr, null);
            danhSoLai();
            danhDauDaDoi();
        });

        body.addEventListener('click', function (e) {
            var btn = e.target.closest('.btn-xoa-dong');
            if (btn) {
                btn.closest('tr').remove();
                danhSoLai();
                danhDauDaDoi();
                return;
            }
            var o = e.target.closest('.o-bai-hoc');
            if (o) {
                var bai = timBaiHoc(o.closest('tr').querySelector('.bai-hoc-id').value);
                if (bai) {
                    moModal(bai);
                }
            }
        });

        body.addEventListener('input', danhDauDaDoi);

        form.addEventListener('submit', function (e) {
            var thieu = [];
            body.querySelectorAll('.bai-day-row').forEach(function (tr, i) {
                if (! timBaiHoc(tr.querySelector('.bai-hoc-id').value)) {
                    thieu.push(i + 1);
                }
            });
            if (thieu.length) {
                e.preventDefault();
                baoThongBao('danger', 'Dòng ' + thieu.join(', ') + ' chưa có Nội dung: kéo bài học thả vào ô Nội dung hoặc xoá dòng.');
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }
            daDoi = false;
        });

        window.addEventListener('beforeunload', function (e) {
            if (daDoi) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // --- Kéo bài học từ khung bên phải thả vào bảng ---
        var keoBaiHocId = null;
        var list = document.getElementById('bai-hoc-list');

        function xoaDauHieuTha() {
            body.querySelectorAll('.tha-doi-bai').forEach(function (el) { el.classList.remove('tha-doi-bai'); });
            body.querySelectorAll('.chen-tren, .chen-duoi').forEach(function (el) { el.classList.remove('chen-tren', 'chen-duoi'); });
            vungThaCuoi.classList.remove('dang-tro');
        }

        // Thả vào ô Nội dung => đổi bài của dòng đó; thả vào ô khác => chèn dòng mới trên/dưới dòng đó.
        function viTriTha(e) {
            var tr = e.target.closest('.bai-day-row');
            if (! tr) {
                return null;
            }
            if (e.target.closest('.o-noi-dung')) {
                return { kieu: 'doi', tr: tr };
            }
            var r = tr.getBoundingClientRect();
            return { kieu: e.clientY < r.top + r.height / 2 ? 'tren' : 'duoi', tr: tr };
        }

        list.addEventListener('dragstart', function (e) {
            var li = e.target.closest('.bai-hoc-item');
            if (! li) {
                return;
            }
            keoBaiHocId = li.getAttribute('data-id');
            e.dataTransfer.effectAllowed = 'copy';
            e.dataTransfer.setData('text/plain', timBaiHoc(keoBaiHocId).ten);
        });
        list.addEventListener('dragend', function () {
            keoBaiHocId = null;
            xoaDauHieuTha();
        });

        body.addEventListener('dragover', function (e) {
            if (keoBaiHocId === null) {
                return;
            }
            e.preventDefault();
            e.dataTransfer.dropEffect = 'copy';
            xoaDauHieuTha();
            var vt = viTriTha(e);
            if (! vt) {
                return;
            }
            if (vt.kieu === 'doi') {
                vt.tr.querySelector('.o-noi-dung').classList.add('tha-doi-bai');
            } else {
                vt.tr.classList.add(vt.kieu === 'tren' ? 'chen-tren' : 'chen-duoi');
            }
        });
        body.addEventListener('dragleave', function (e) {
            if (keoBaiHocId !== null && ! body.contains(e.relatedTarget)) {
                xoaDauHieuTha();
            }
        });
        body.addEventListener('drop', function (e) {
            if (keoBaiHocId === null) {
                return;
            }
            e.preventDefault();
            var id = keoBaiHocId;
            var vt = viTriTha(e);
            xoaDauHieuTha();
            if (! vt) {
                return;
            }
            if (vt.kieu === 'doi') {
                ganBaiHoc(vt.tr, id);
                danhDauDaDoi();
            } else {
                chenDong(id, vt.kieu === 'tren' ? vt.tr : vt.tr.nextElementSibling);
            }
        });

        vungThaCuoi.addEventListener('dragover', function (e) {
            if (keoBaiHocId === null) {
                return;
            }
            e.preventDefault();
            e.dataTransfer.dropEffect = 'copy';
            vungThaCuoi.classList.add('dang-tro');
        });
        vungThaCuoi.addEventListener('dragleave', function () {
            vungThaCuoi.classList.remove('dang-tro');
        });
        vungThaCuoi.addEventListener('drop', function (e) {
            if (keoBaiHocId === null) {
                return;
            }
            e.preventDefault();
            var id = keoBaiHocId;
            xoaDauHieuTha();
            chenDong(id, null);
        });

        // --- Modal thêm / sửa / xoá bài học ---
        var $modal = $('#modalBaiHoc');
        var dangSua = null;
        var loi = document.getElementById('bai-hoc-loi');
        var inpTen = document.getElementById('bai-hoc-ten');
        var inpBd = document.getElementById('bai-hoc-gio-bd');
        var inpKt = document.getElementById('bai-hoc-gio-kt');
        var apDung = document.getElementById('bai-hoc-ap-dung');
        var btnXoa = document.getElementById('btn-xoa-bai-hoc');
        var btnLuu = document.getElementById('btn-luu-bai-hoc');

        function soDongTrenBang(id) {
            var n = 0;
            body.querySelectorAll('.bai-hoc-id').forEach(function (inp) {
                if (String(inp.value) === String(id)) {
                    n++;
                }
            });
            return n;
        }

        function moModal(bai) {
            dangSua = bai;
            loi.classList.add('d-none');
            document.getElementById('modalBaiHocLabel').textContent = bai ? 'Sửa bài học' : 'Thêm bài học';
            inpTen.value = bai ? bai.ten : '';
            inpBd.value = bai ? bai.gio_bd : '';
            inpKt.value = bai ? bai.gio_kt : '';
            btnXoa.classList.toggle('d-none', ! bai);
            if (bai && bai.so_ngay > 0) {
                apDung.textContent = 'Đang gán cho ' + bai.so_ngay + ' ngày (đã lưu). Sửa khung giờ sẽ áp dụng cho tất cả các ngày đó.';
                apDung.classList.remove('d-none');
            } else {
                apDung.classList.add('d-none');
            }
            $modal.modal('show');
        }

        $modal.on('shown.bs.modal', function () {
            (dangSua ? inpBd : inpTen).focus();
        });

        function goiApi(method, url, data) {
            return fetch(url, {
                method: method,
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: data ? JSON.stringify(data) : undefined,
            }).then(function (res) {
                return res.json().catch(function () { return {}; }).then(function (json) {
                    if (! res.ok) {
                        var msg = json.message || ('Lỗi ' + res.status);
                        if (json.errors) {
                            msg = Object.keys(json.errors).map(function (k) { return json.errors[k].join(' '); }).join('\n');
                        }
                        throw new Error(msg);
                    }
                    return json;
                });
            });
        }

        function baoLoi(err) {
            loi.textContent = err.message;
            loi.classList.remove('d-none');
        }

        document.getElementById('btn-them-bai-hoc').addEventListener('click', function () {
            moModal(null);
        });

        list.addEventListener('click', function (e) {
            var btn = e.target.closest('.btn-sua-bai-hoc');
            if (btn) {
                moModal(timBaiHoc(btn.closest('.bai-hoc-item').getAttribute('data-id')));
            }
        });

        document.getElementById('form-bai-hoc').addEventListener('submit', function (e) {
            e.preventDefault();
            btnLuu.disabled = true;
            var data = { ten: inpTen.value, gio_bd: inpBd.value, gio_kt: inpKt.value };
            var req = dangSua
                ? goiApi('PUT', urlBaiHoc + '/' + dangSua.id, data)
                : goiApi('POST', urlBaiHoc, data);
            req.then(function (json) {
                var b = json.bai_hoc;
                var cu = timBaiHoc(b.id);
                if (cu) {
                    Object.assign(cu, b);
                } else {
                    dsBaiHoc.push(b);
                }
                veDanhSachBaiHoc();
                lamMoiBang();
                baoThongBao('success', json.message);
                $modal.modal('hide');
            }).catch(baoLoi).then(function () {
                btnLuu.disabled = false;
            });
        });

        btnXoa.addEventListener('click', function () {
            if (! dangSua) {
                return;
            }
            var tren = soDongTrenBang(dangSua.id);
            if (tren > 0) {
                baoLoi(new Error('Bảng đang có ' + tren + ' dòng chọn bài này. Đổi các dòng đó sang bài khác trước khi xoá.'));
                return;
            }
            if (! window.confirm('Xoá bài học "' + dangSua.ten + '"?')) {
                return;
            }
            var id = dangSua.id;
            goiApi('DELETE', urlBaiHoc + '/' + id).then(function (json) {
                dsBaiHoc = dsBaiHoc.filter(function (b) { return b.id !== id; });
                veDanhSachBaiHoc();
                lamMoiBang();
                baoThongBao('success', json.message);
                $modal.modal('hide');
            }).catch(baoLoi);
        });
    })();
</script>
@endpush
