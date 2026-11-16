@php
    use App\Support\QldtOto\KtmBaoCao\KtmBaoCaoFormMacDinh as KtmForm;
    $bbth = $form['bbth'] ?? [];
    $chiTiet = $form['chi_tiet'] ?? [];
    $bcChung = $form['bc_chung'] ?? [];
    $valNgay = function (string $prefix, string $key, ?string $stored) {
        $old = old($prefix.'.'.$key);
        if ($old !== null) {
            return KtmForm::ngaySangInput((string) $old);
        }

        return KtmForm::ngaySangInput($stored);
    };
    $valGio = function (string $key, ?string $stored) {
        $old = old('form.bbth.'.$key);
        if ($old !== null) {
            return KtmForm::gioSangInput((string) $old);
        }

        return KtmForm::gioSangInput($stored);
    };
    $thanhVien = $bbth['thanh_vien'] ?? ['', '', '', ''];
    while (count($thanhVien) < 4) {
        $thanhVien[] = '';
    }
    $nhanXet = $bbth['nhan_xet'] ?? [];
    while (count($nhanXet) < 5) {
        $nhanXet[] = '';
    }
@endphp

@if (session('success'))
    <div class="alert alert-success py-2">{{ session('success') }}</div>
@endif

<form method="POST" action="{{ route('qldtoto.xuat-bao-cao-ktm.update-tham-so') }}" id="form-tham-so-ktm">
    @csrf
    <div class="card card-panel mb-3">
        <div class="card-header py-2 d-flex flex-wrap align-items-center gap-2">
            <span>Tham số báo cáo</span>
            <span class="text-muted small font-weight-normal ml-auto">Mỗi file một tab — bấm «Lưu tham số» trước khi tải</span>
        </div>
        <div class="card-body pb-2">
            <ul class="nav nav-tabs nav-tabs-tham-so mb-3" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#tab-bbth" role="tab">Biên bản tổng hợp</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-chi-tiet" role="tab">Báo cáo chi tiết</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tab-bc-chung" role="tab">Báo cáo chung</a>
                </li>
            </ul>

            <div class="tab-content small">
                <div class="tab-pane fade show active" id="tab-bbth" role="tabpanel">
                    <p class="text-muted">Biên bản tổng hợp — ô <strong>I4</strong>, <strong>A8</strong>, danh sách HĐ <strong>B9–B12</strong>, nhận xét <strong>B40–B44</strong>, chữ ký <strong>B51 / F51 / M51 / G58</strong>.</p>
                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label>Tên khóa (đoạn A8)</label>
                            <input type="text" name="form[bbth][ten_khoa]" class="form-control form-control-sm" value="{{ old('form.bbth.ten_khoa', $bbth['ten_khoa'] ?? '') }}">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Địa danh (dòng ngày I4)</label>
                            <input type="text" name="form[bbth][dia_diem_so]" class="form-control form-control-sm" value="{{ old('form.bbth.dia_diem_so', $bbth['dia_diem_so'] ?? '') }}">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Ngày lập (I4)</label>
                            <input type="date" name="form[bbth][ngay_lap]" class="form-control form-control-sm"
                                   value="{{ $valNgay('form.bbth', 'ngay_lap', $bbth['ngay_lap'] ?? null) }}">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Ngày họp (A8)</label>
                            <input type="date" name="form[bbth][ngay_hop]" class="form-control form-control-sm"
                                   value="{{ $valNgay('form.bbth', 'ngay_hop', $bbth['ngay_hop'] ?? null) }}">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Giờ họp</label>
                            <input type="time" name="form[bbth][gio_hop]" class="form-control form-control-sm"
                                   value="{{ $valGio('gio_hop', $bbth['gio_hop'] ?? null) }}" step="60">
                        </div>
                        <div class="form-group col-12">
                            <label>Địa điểm họp</label>
                            <input type="text" name="form[bbth][dia_diem_hop]" class="form-control form-control-sm" value="{{ old('form.bbth.dia_diem_hop', $bbth['dia_diem_hop'] ?? '') }}">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Số QĐ thành lập HĐ</label>
                            <input type="text" name="form[bbth][so_qd]" class="form-control form-control-sm" value="{{ old('form.bbth.so_qd', $bbth['so_qd'] ?? '') }}" placeholder="vd. 125">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Ngày QĐ</label>
                            <input type="date" name="form[bbth][ngay_qd]" class="form-control form-control-sm"
                                   value="{{ $valNgay('form.bbth', 'ngay_qd', $bbth['ngay_qd'] ?? null) }}">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Tỷ lệ đạt — mẫu số</label>
                            <select name="form[bbth][ty_le_mau_so]" class="form-control form-control-sm">
                                <option value="du_thi" @selected(($bbth['ty_le_mau_so'] ?? '') === 'du_thi')>Số dự thi</option>
                                <option value="ho_so" @selected(($bbth['ty_le_mau_so'] ?? '') === 'ho_so')>Số hồ sơ</option>
                            </select>
                        </div>
                    </div>

                    <label class="d-block font-weight-bold mb-1">Thành viên hội đồng (B9 → B12)</label>
                    @foreach ($thanhVien as $i => $line)
                        <div class="form-group mb-1">
                            <input type="text" name="form[bbth][thanh_vien][]" class="form-control form-control-sm" value="{{ old('form.bbth.thanh_vien.'.$i, $line) }}" placeholder="Dòng {{ $i + 1 }}">
                        </div>
                    @endforeach

                    <label class="d-block font-weight-bold mt-2 mb-1">Nhận xét (B40 → B44)</label>
                    @foreach ($nhanXet as $i => $line)
                        <div class="form-group mb-1">
                            <input type="text" name="form[bbth][nhan_xet][]" class="form-control form-control-sm" value="{{ old('form.bbth.nhan_xet.'.$i, $line) }}">
                        </div>
                    @endforeach

                    <div class="form-row mt-2">
                        <div class="form-group col-md-3">
                            <label>Ủy viên (B51)</label>
                            <input type="text" name="form[bbth][ky_uy_vien]" class="form-control form-control-sm" value="{{ old('form.bbth.ky_uy_vien', $bbth['ky_uy_vien'] ?? '') }}">
                        </div>
                        <div class="form-group col-md-3">
                            <label>Phó chủ tịch / F51</label>
                            <input type="text" name="form[bbth][ky_pho_chu_tich]" class="form-control form-control-sm" value="{{ old('form.bbth.ky_pho_chu_tich', $bbth['ky_pho_chu_tich'] ?? '') }}">
                        </div>
                        <div class="form-group col-md-3">
                            <label>Chủ tịch (M51)</label>
                            <input type="text" name="form[bbth][ky_chu_tich]" class="form-control form-control-sm" value="{{ old('form.bbth.ky_chu_tich', $bbth['ky_chu_tich'] ?? '') }}">
                        </div>
                        <div class="form-group col-md-3">
                            <label>Thư ký (G58)</label>
                            <input type="text" name="form[bbth][ky_thu_ky]" class="form-control form-control-sm" value="{{ old('form.bbth.ky_thu_ky', $bbth['ky_thu_ky'] ?? '') }}">
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-chi-tiet" role="tabpanel">
                    <p class="text-muted">Báo cáo chi tiết — <strong>O4</strong>, mục 1–2 (<strong>A8, A9</strong>), tên ký dưới bảng (<strong>P…</strong>).</p>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Địa danh (O4)</label>
                            <input type="text" name="form[chi_tiet][dia_diem_so]" class="form-control form-control-sm" value="{{ old('form.chi_tiet.dia_diem_so', $chiTiet['dia_diem_so'] ?? '') }}">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Ngày lập báo cáo (O4)</label>
                            <input type="date" name="form[chi_tiet][ngay_lap]" class="form-control form-control-sm"
                                   value="{{ $valNgay('form.chi_tiet', 'ngay_lap', $chiTiet['ngay_lap'] ?? null) }}">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Ngày kiểm tra (A9)</label>
                            <input type="date" name="form[chi_tiet][ngay_thi]" class="form-control form-control-sm"
                                   value="{{ $valNgay('form.chi_tiet', 'ngay_thi', $chiTiet['ngay_thi'] ?? null) }}">
                        </div>
                        <div class="form-group col-12">
                            <label>Trung tâm sát hạch (A8)</label>
                            <input type="text" name="form[chi_tiet][sat_hach]" class="form-control form-control-sm" value="{{ old('form.chi_tiet.sat_hach', $chiTiet['sat_hach'] ?? '') }}">
                        </div>
                        <div class="form-group col-md-6">
                            <label>KT. Giám đốc — tên ký (P, dưới bảng)</label>
                            <input type="text" name="form[chi_tiet][ky_giam_doc]" class="form-control form-control-sm" value="{{ old('form.chi_tiet.ky_giam_doc', $chiTiet['ky_giam_doc'] ?? '') }}">
                        </div>
                        <div class="form-group col-md-6">
                            <label>P. Giám đốc — tên (nếu dùng)</label>
                            <input type="text" name="form[chi_tiet][ky_pho_giam_doc]" class="form-control form-control-sm" value="{{ old('form.chi_tiet.ky_pho_giam_doc', $chiTiet['ky_pho_giam_doc'] ?? '') }}">
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-bc-chung" role="tabpanel">
                    <p class="text-muted">Báo cáo chung — <strong>O4</strong>, <strong>A8/A9</strong>, tên ký <strong>P27</strong> (tách riêng khỏi BC chi tiết).</p>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Địa danh (O4)</label>
                            <input type="text" name="form[bc_chung][dia_diem_so]" class="form-control form-control-sm" value="{{ old('form.bc_chung.dia_diem_so', $bcChung['dia_diem_so'] ?? '') }}">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Ngày lập báo cáo (O4)</label>
                            <input type="date" name="form[bc_chung][ngay_lap]" class="form-control form-control-sm"
                                   value="{{ $valNgay('form.bc_chung', 'ngay_lap', $bcChung['ngay_lap'] ?? null) }}">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Ngày kiểm tra (A9)</label>
                            <input type="date" name="form[bc_chung][ngay_thi]" class="form-control form-control-sm"
                                   value="{{ $valNgay('form.bc_chung', 'ngay_thi', $bcChung['ngay_thi'] ?? null) }}">
                        </div>
                        <div class="form-group col-12">
                            <label>Trung tâm sát hạch (A8)</label>
                            <input type="text" name="form[bc_chung][sat_hach]" class="form-control form-control-sm" value="{{ old('form.bc_chung.sat_hach', $bcChung['sat_hach'] ?? '') }}">
                        </div>
                        <div class="form-group col-md-6">
                            <label>KT. Giám đốc (P27)</label>
                            <input type="text" name="form[bc_chung][ky_giam_doc]" class="form-control form-control-sm" value="{{ old('form.bc_chung.ky_giam_doc', $bcChung['ky_giam_doc'] ?? '') }}">
                        </div>
                        <div class="form-group col-md-6">
                            <label>P. Giám đốc (nếu dùng)</label>
                            <input type="text" name="form[bc_chung][ky_pho_giam_doc]" class="form-control form-control-sm" value="{{ old('form.bc_chung.ky_pho_giam_doc', $bcChung['ky_pho_giam_doc'] ?? '') }}">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer py-2 d-flex flex-wrap align-items-center gap-2">
            <button type="submit" class="btn btn-sm btn-primary">Lưu tham số</button>
            @if ($duLieu->canhBao !== [])
                <span class="small text-warning ml-2">Có {{ count($duLieu->canhBao) }} cảnh báo — xem phía trên.</span>
            @endif
        </div>
    </div>

    @if ($duLieu->canhBao !== [])
        <div class="alert alert-warning small py-2 mb-2">
            <strong>Cảnh báo</strong>
            <ul class="mb-0 pl-3">
                @foreach ($duLieu->canhBao as $w)
                    <li>{{ $w }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="d-flex flex-wrap gap-2 mb-2">
        @foreach ([
            'bbth' => 'Tải Biên bản tổng hợp',
            'chi-tiet' => 'Tải Báo cáo chi tiết',
            'chung' => 'Tải Báo cáo chung',
            'zip' => 'Tải cả 3 (.zip)',
        ] as $loai => $nhan)
            <button type="submit" class="btn btn-sm btn-{{ $loai === 'zip' ? 'navy' : 'outline-primary' }}" formaction="{{ route('qldtoto.xuat-bao-cao-ktm.export', ['loai' => $loai]) }}">{{ $nhan }}</button>
        @endforeach
    </div>
    <p class="small text-muted mb-3">Bấm tải = lưu tham số hiện tại trên form rồi xuất file.</p>
</form>

<style>
    .nav-tabs-tham-so .nav-link { font-size: .9rem; padding: .35rem .75rem; }
</style>