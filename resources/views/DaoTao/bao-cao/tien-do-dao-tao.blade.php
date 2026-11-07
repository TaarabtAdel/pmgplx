@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Báo cáo Tiến độ đào tạo')

@push('styles')
<style>
    .tien-do-intro {
        color: #444;
        margin-bottom: 1rem;
        line-height: 1.55;
    }
    .tien-do-intro h5 {
        color: #1f4e79;
        font-weight: 700;
        margin-bottom: 0.35rem;
    }
    .tien-do-goi-y-van-hanh {
        border: 1px solid #c5cdd6;
        border-left: 4px solid #1f4e79;
        border-radius: 0.2rem;
        background: #fff;
        padding: 0.85rem 1rem;
        margin-bottom: 1rem;
    }
    .tien-do-goi-y-van-hanh h6 {
        color: #1f4e79;
        font-weight: 700;
        font-size: 0.95rem;
        margin-bottom: 0.5rem;
    }
    .tien-do-goi-y-van-hanh ul {
        margin-bottom: 0;
        padding-left: 1.2rem;
        color: #444;
        font-size: 0.9rem;
        line-height: 1.5;
    }
    .tien-do-goi-y-van-hanh li + li {
        margin-top: 0.35rem;
    }
    .tien-do-summary {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem 2.5rem;
        padding: 1rem 1.25rem;
        background: #f7f9fb;
        border: 1px solid #c5cdd6;
        border-radius: 0.2rem;
        margin-bottom: 1rem;
    }
    .tien-do-summary .summary-label {
        color: #555;
        font-size: 0.9rem;
        margin-bottom: 0.25rem;
    }
    .tien-do-summary .summary-count {
        font-size: 2rem;
        font-weight: 700;
        color: #1f4e79;
        line-height: 1.2;
    }
    .tien-do-summary .summary-meta {
        color: #666;
        font-size: 0.85rem;
        margin-top: 0.35rem;
    }
    .bang-tien-do {
        border: 2px solid #1f4e79;
    }
    .bang-tien-do th,
    .bang-tien-do td {
        vertical-align: middle;
        border-color: #333 !important;
    }
    .bang-tien-do .tieu-de-bang {
        text-align: center;
        font-weight: 700;
        font-size: 1rem;
        background: #fff;
    }
    .bang-tien-do thead th {
        text-align: center;
        background: #fff;
        font-weight: 700;
    }
    .bang-tien-do td.ngay,
    .bang-tien-do td.so {
        text-align: center;
        white-space: nowrap;
    }
    .bang-tien-do td.so {
        font-weight: 700;
    }
    .bang-tien-do td.khoa,
    .bang-tien-do td.hang {
        font-weight: 700;
        color: #000;
    }
    .hang-B01 { background: #ff4040 !important; }
    .hang-C { background: #ffff00 !important; }
    .hang-B { background: #92d050 !important; }
    .bang-tien-do tr.du-kien td:not(.khoa):not(.hang) {
        background: #f3f6fa;
        color: #34495e;
    }
    .bang-tien-do tr.du-kien td.ngay {
        font-style: italic;
    }
    .badge-thuc-te {
        background: #1f4e79;
        color: #fff;
    }
    .badge-du-kien {
        background: #fff;
        color: #1f4e79;
        border: 1px dashed #1f4e79;
    }
    .bang-cau-hinh td,
    .bang-cau-hinh th {
        vertical-align: middle;
    }
    .bang-cau-hinh input.form-control-sm,
    .bang-cau-hinh select.form-control-sm {
        min-width: 4.5rem;
    }
    .bang-cau-hinh input[type="color"].mau-loai-khoa {
        width: 2.5rem;
        height: calc(1.5em + 0.5rem + 2px);
        padding: 0.1rem;
        cursor: pointer;
    }
    .modal-cau-hinh-tien-do .modal-body {
        max-height: calc(100vh - 11rem);
        overflow-y: auto;
    }
    @media (min-width: 992px) {
        .modal-cau-hinh-tien-do.modal-loai-khoa .modal-dialog {
            max-width: min(96vw, 72rem);
        }
    }
</style>
@endpush

@section('content')
    <div class="tien-do-goi-y-van-hanh">
        <h6>Gợi ý vận hành</h6>
        <ul>
            <li>Báo cáo <strong>tính lại mỗi lần mở</strong> — không lưu lịch «Dự kiến …» cũ trong hệ thống; mỗi lần xem là kết quả mới từ PMGPLX + cấu hình.</li>
            <li>Khi đã mở khoá trên <strong>PMGPLX → Quản lý khoá học</strong>, khoá đó hiển thị <strong>Thực tế</strong>; các dòng <strong>Dự kiến</strong> là các khoá <em>tiếp theo</em> trong chuỗi (ngày lấy từ tốt nghiệp thực tế + công thức loại khoá).</li>
            <li>Khoá thực tế lệch chuỗi (25 người, đêm, sàn 60…): bấm gán <strong>Loại khoá</strong> trên dòng thực tế cho khớp cấu hình «Loại khoá dự kiến».</li>
            <li>Sau khi cập nhật PMGPLX hoặc sửa cấu hình (loại khoá, tăng/giảm GV, ngày nghỉ): <strong>tải lại trang</strong> để xem tiến độ mới.</li>
        </ul>
    </div>

    <div class="tien-do-intro">
        <h5>Tiến độ đào tạo năm {{ $nam }} — thực tế và dự kiến</h5>
        <p class="mb-0">
            <strong>Thực tế</strong>: các khoá trên <strong>PMGPLX → Quản lý khoá học</strong>
            (ngày thực hành in nghiêng là ước tính vì khoá chưa có lịch xe). <strong>Dự kiến</strong>: mỗi loại khoá là một chuỗi khoá nối tiếp — khoá sau
            <strong>thực hành</strong> sau ngày tốt nghiệp khoá trước, <strong>khai giảng</strong> = thực hành − số ngày lý thuyết,
            <strong>tốt nghiệp</strong> = thực hành + số ngày thực hành. Ngày nghỉ lễ, Tết không tính vào số ngày học.
        </p>
    </div>

    <div class="card card-panel">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
            <span>Tiến độ đào tạo theo năm</span>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-secondary" data-toggle="modal" data-target="#modal-cau-hinh-loai-khoa"
                        title="Chuỗi khoá dự kiến theo loại">
                    Loại khoá dự kiến
                    <span class="badge badge-light border ml-1">{{ $loaiKhoa->where('DangSuDung', true)->count() }}</span>
                </button>
                <button type="button" class="btn btn-outline-secondary" data-toggle="modal" data-target="#modal-cau-hinh-tang-xe"
                        title="Điều chỉnh số giáo viên áp dụng từ ngày">
                    Tăng / giảm GV
                    <span class="badge badge-light border ml-1">{{ $tangXe->count() }}</span>
                </button>
                <button type="button" class="btn btn-outline-secondary" data-toggle="modal" data-target="#modal-cau-hinh-ngay-nghi"
                        title="Ngày không tính vào lịch học dự kiến">
                    Ngày nghỉ lễ, Tết
                    <span class="badge badge-light border ml-1">{{ $ngayNghi->count() }}</span>
                </button>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('daotao.pdt.bc.tien-do-dao-tao') }}">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-2">
                        <label for="nam">Năm</label>
                        <select class="form-control form-control-sm" id="nam" name="nam" onchange="document.getElementById('ngay_xem').value = this.value + '-12-31'">
                            @foreach ($namOptions as $option)
                                <option value="{{ $option }}" @selected($option === $nam)>{{ $option }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="ngay_xem">Xem số học viên đang học tại ngày</label>
                        <input type="date" class="form-control form-control-sm" id="ngay_xem" name="ngay_xem"
                               value="{{ $ngayXem->format('Y-m-d') }}">
                    </div>
                    <div class="form-group col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary btn-block">Xem</button>
                    </div>
                </div>
            </form>

            @foreach ($canhBao as $text)
                <div class="alert alert-warning py-2 mb-2">{{ $text }}</div>
            @endforeach

            @php
                $tongThucTe = array_sum(array_column($tongHop, 'hoc_vien_thuc_te'));
                $tongDuKien = array_sum(array_column($tongHop, 'hoc_vien_du_kien'));
            @endphp
            <div class="tien-do-summary mt-2">
                <div>
                    <div class="summary-label">Học viên đang học ngày {{ $ngayXem->format('d/m/Y') }}</div>
                    <div class="summary-count">{{ number_format($dangHoc['tong']) }}</div>
                    <div class="summary-meta">
                        Thực tế {{ number_format($dangHoc['thuc_te']) }} · Dự kiến {{ number_format($dangHoc['du_kien']) }}
                        · {{ $dangHoc['so_khoa'] }} khoá đang học
                    </div>
                    <div class="summary-meta">
                        @foreach ($dangHoc['theo_hang'] as $hang => $soHv)
                            <span class="mr-3">{{ $nhanHang[$hang] }}: <strong>{{ number_format($soHv) }}</strong></span>
                        @endforeach
                    </div>
                </div>
                <div>
                    <div class="summary-label">Học viên khai giảng trong năm {{ $nam }}</div>
                    <div class="summary-count">{{ number_format($tongThucTe + $tongDuKien) }}</div>
                    <div class="summary-meta">
                        Thực tế {{ number_format($tongThucTe) }} · Dự kiến {{ number_format($tongDuKien) }}
                        · {{ count($khoa) }} khoá
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered bang-tien-do mb-0">
                    <thead>
                        <tr>
                            <th colspan="10" class="tieu-de-bang">CÁC KHÓA DỰ KIẾN ĐÀO TẠO &amp; KHAI GIẢNG NĂM {{ $nam }}</th>
                        </tr>
                        <tr>
                            <th>STT</th>
                            <th>KHÓA</th>
                            <th>LOẠI KHOÁ</th>
                            <th>SỐ LƯỢNG</th>
                            <th>KHAI GIẢNG</th>
                            <th>THỰC HÀNH</th>
                            <th>TỐT NGHIỆP</th>
                            <th>XE / GV</th>
                            <th>NGUỒN</th>
                            <th>LƯU Ý</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($khoa as $index => $k)
                            @php
                                $mauLoai = $k['mau_loai_khoa'] ?? $mauTheoTenLoai[$k['loai_khoa']] ?? null;
                            @endphp
                            <tr class="{{ $k['nguon'] === 'du_kien' ? 'du-kien' : '' }}">
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td @class(['khoa' => true, 'hang' => ! $mauLoai, 'hang-'.$k['hang'] => ! $mauLoai])
                                    @if ($mauLoai) style="background-color: {{ $mauLoai }}" @endif>{{ $k['ten'] }}</td>
                                <td @class(['hang' => ! $mauLoai, 'hang-'.$k['hang'] => ! $mauLoai])
                                    @if ($mauLoai) style="background-color: {{ $mauLoai }}" @endif>
                                    @if ($k['nguon'] === 'thuc_te')
                                        <button type="button" class="btn btn-link btn-sm p-0 text-reset font-weight-bold btn-gan-loai-khoa"
                                                title="Gán loại khoá cho {{ $k['ten'] }}"
                                                data-ten="{{ $k['ten'] }}"
                                                data-ma="{{ implode(',', $k['ma_khoa_lop']) }}"
                                                data-loai="{{ $k['loai_khoa'] }}"
                                                data-hang="{{ $k['hang'] }}"
                                                data-mac-dinh="{{ \App\Support\DaoTao\TienDoDaoTaoNam::LOAI_KHOA_THEO_HANG[$k['hang']] }}"
                                                data-rieng="{{ ! empty($k['loai_khoa_rieng']) ? 1 : 0 }}">
                                            {{ $k['loai_khoa'] }}
                                            <span class="small ml-1">&#9998;</span>
                                        </button>
                                        @if (! empty($k['loai_khoa_rieng']))
                                            <div><span class="badge badge-light border font-weight-normal">gán riêng</span></div>
                                        @endif
                                    @else
                                        {{ $k['loai_khoa'] }}
                                    @endif
                                </td>
                                <td class="so">{{ number_format($k['so_hoc_vien']) }}</td>
                                <td class="ngay">{{ $k['khai_giang']->format('d/m/Y') }}</td>
                                <td class="ngay">
                                    @if ($k['thuc_hanh'] === null)
                                        —
                                    @elseif ($k['nguon'] === 'thuc_te' && $k['thuc_hanh_uoc_tinh'])
                                        <em title="Ước tính: khai giảng + số ngày lý thuyết (khoá chưa có lịch xe)">~{{ $k['thuc_hanh']->format('d/m/Y') }}</em>
                                    @else
                                        {{ $k['thuc_hanh']->format('d/m/Y') }}
                                    @endif
                                </td>
                                <td class="ngay">{{ $k['tot_nghiep']->format('d/m/Y') }}</td>
                                <td class="ngay">
                                    @if ($k['so_xe'] !== null)
                                        {{ $k['so_xe'] }} xe / {{ $k['so_giao_vien'] }} GV
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($k['nguon'] === 'thuc_te')
                                        <span class="badge badge-thuc-te">Thực tế</span>
                                        @if (! empty($k['ban_cu']))
                                            <div><span class="badge badge-light border" title="Khoá lấy từ phần mềm GPLX bản cũ">bản cũ</span></div>
                                        @endif
                                    @else
                                        <span class="badge badge-du-kien">Dự kiến</span>
                                    @endif
                                </td>
                                <td class="small">
                                    @foreach ($k['luu_y'] as $text)
                                        <div>{{ $text }}</div>
                                    @endforeach
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4">Không có khoá nào khai giảng trong năm {{ $nam }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <h6 class="section-title mt-4 mb-2">Tổng hợp khai giảng năm {{ $nam }} theo hạng</h6>
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Loại khoá</th>
                            <th class="text-center">Khoá thực tế</th>
                            <th class="text-center">HV thực tế</th>
                            <th class="text-center">Khoá dự kiến</th>
                            <th class="text-center">HV dự kiến</th>
                            <th class="text-center">Tổng HV</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tongHop as $row)
                            <tr>
                                <td>{{ $row['nhan'] }}</td>
                                <td class="text-center">{{ $row['so_khoa_thuc_te'] }}</td>
                                <td class="text-center">{{ number_format($row['hoc_vien_thuc_te']) }}</td>
                                <td class="text-center">{{ $row['so_khoa_du_kien'] }}</td>
                                <td class="text-center">{{ number_format($row['hoc_vien_du_kien']) }}</td>
                                <td class="text-center font-weight-bold">{{ number_format($row['hoc_vien_thuc_te'] + $row['hoc_vien_du_kien']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="font-weight-bold">
                            <td class="text-right">Tổng cộng</td>
                            <td class="text-center">{{ array_sum(array_column($tongHop, 'so_khoa_thuc_te')) }}</td>
                            <td class="text-center">{{ number_format($tongThucTe) }}</td>
                            <td class="text-center">{{ array_sum(array_column($tongHop, 'so_khoa_du_kien')) }}</td>
                            <td class="text-center">{{ number_format($tongDuKien) }}</td>
                            <td class="text-center">{{ number_format($tongThucTe + $tongDuKien) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- Cấu hình dự kiến (modal) --}}
    <div class="modal fade modal-cau-hinh-tien-do modal-loai-khoa" id="modal-cau-hinh-loai-khoa" tabindex="-1" role="dialog"
         aria-labelledby="modal-cau-hinh-loai-khoa-tieu-de" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title" id="modal-cau-hinh-loai-khoa-tieu-de">Loại khoá dự kiến</h6>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Mỗi loại khoá là một chuỗi khoá nối tiếp nhau. Để trống <strong>Ngày khai giảng</strong> thì chuỗi tự nối tiếp
                        khoá thực tế tốt nghiệp muộn nhất cùng loại; nhập ngày thì khoá đầu tiên của chuỗi khai giảng vào ngày đó.
                        Số GV cơ bản = SL tối đa ÷ <strong>HV/GV</strong>. Chọn <strong>màu</strong> để phân biệt loại khoá trên bảng tiến độ.
                    </p>

            <form method="POST" action="{{ route('daotao.pdt.bc.tien-do-dao-tao.loai-khoa.store') }}" class="mb-3 border rounded p-3 bg-light" id="frm-loai-khoa-moi">
                @csrf
                <input type="hidden" name="so_giao_vien_moi_xe" value="1">
                <input type="hidden" name="dang_su_dung" value="1">
                <div class="form-row align-items-end">
                    <div class="form-group col-md-1 mb-md-0">
                        <label>Màu</label>
                        <input type="color" name="mau_sac" class="form-control form-control-sm mau-loai-khoa" value="#FFB3B3" id="loai-khoa-moi-mau" title="Màu cột Loại khoá trên bảng">
                    </div>
                    <div class="form-group col-md-2 mb-md-0">
                        <label>Tên loại khoá <span class="text-danger">*</span></label>
                        <input type="text" name="ten_loai" class="form-control form-control-sm" maxlength="100" required placeholder="VD: B dòng 2">
                    </div>
                    <div class="form-group col-md-1 mb-md-0">
                        <label>Hạng</label>
                        <select name="hang" class="form-control form-control-sm" id="loai-khoa-moi-hang">
                            @foreach ($nhanHang as $hang => $nhan)
                                <option value="{{ $hang }}">{{ $hang }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-1 mb-md-0">
                        <label>SL tối đa</label>
                        <input type="number" name="so_hoc_vien_toi_da" class="form-control form-control-sm" min="1" max="1000" required>
                    </div>
                    <div class="form-group col-md-1 mb-md-0">
                        <label>Lý thuyết</label>
                        <input type="number" name="so_ngay_ly_thuyet" class="form-control form-control-sm" min="0" max="365" required>
                    </div>
                    <div class="form-group col-md-1 mb-md-0">
                        <label>Thực hành</label>
                        <input type="number" name="so_ngay_thuc_hanh" class="form-control form-control-sm" min="1" max="365" required>
                    </div>
                    <div class="form-group col-md-1 mb-md-0">
                        <label class="text-nowrap" title="1 GV dạy tối đa bao nhiêu HV">HV/GV</label>
                        <input type="number" name="so_hoc_vien_moi_xe" class="form-control form-control-sm" min="1" max="100" required
                               title="1 GV dạy tối đa bao nhiêu HV">
                    </div>
                    <div class="form-group col-md-1 mb-md-0">
                        <label>Cách (ngày)</label>
                        <input type="number" name="so_ngay_cach" class="form-control form-control-sm" min="0" max="60" value="2" required>
                    </div>
                    <div class="form-group col-md-2 mb-md-0">
                        <label>Ngày khai giảng</label>
                        <input type="date" name="ngay_khai_giang" class="form-control form-control-sm">
                    </div>
                    <div class="form-group col-md-2 mb-md-0">
                        <button type="submit" class="btn btn-sm btn-navy btn-block">Thêm loại khoá</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover bang-cau-hinh mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th class="text-center" title="Hiển thị ở cột Loại khoá bảng tiến độ">Màu</th>
                            <th>Tên loại khoá</th>
                            <th>Hạng</th>
                            <th>SL tối đa</th>
                            <th>Lý thuyết (ngày)</th>
                            <th>Thực hành (ngày)</th>
                            <th class="text-center" title="1 GV dạy tối đa bao nhiêu HV">HV/GV</th>
                            <th>Cách (ngày)</th>
                            <th>Ngày khai giảng</th>
                            <th>Dùng</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($loaiKhoa as $loai)
                            @php $form = 'frm-loai-khoa-'.$loai->Id; @endphp
                            <tr>
                                <td class="text-center">
                                    <form method="POST" action="{{ route('daotao.pdt.bc.tien-do-dao-tao.loai-khoa.update', $loai->Id) }}" id="{{ $form }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="dang_su_dung" value="0">
                                        <input type="hidden" name="so_giao_vien_moi_xe" value="1">
                                    </form>
                                    <input type="color" name="mau_sac" class="form-control form-control-sm mau-loai-khoa d-inline-block"
                                           value="{{ $loai->MauSac ?: (['B01' => '#FFB3B3', 'C' => '#FFD966', 'B' => '#C5E0B4'][$loai->Hang] ?? '#E0E0E0') }}"
                                           form="{{ $form }}" title="Màu cột Loại khoá">
                                </td>
                                <td><input type="text" name="ten_loai" class="form-control form-control-sm" maxlength="100" value="{{ $loai->TenLoai }}" required form="{{ $form }}"></td>
                                <td>
                                    <select name="hang" class="form-control form-control-sm" form="{{ $form }}">
                                        @foreach ($nhanHang as $hang => $nhan)
                                            <option value="{{ $hang }}" @selected($loai->Hang === $hang)>{{ $hang }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" name="so_hoc_vien_toi_da" class="form-control form-control-sm" min="1" max="1000" value="{{ $loai->SoHocVienToiDa }}" required form="{{ $form }}"></td>
                                <td><input type="number" name="so_ngay_ly_thuyet" class="form-control form-control-sm" min="0" max="365" value="{{ $loai->SoNgayLyThuyet }}" required form="{{ $form }}"></td>
                                <td><input type="number" name="so_ngay_thuc_hanh" class="form-control form-control-sm" min="1" max="365" value="{{ $loai->SoNgayThucHanh }}" required form="{{ $form }}"></td>
                                <td><input type="number" name="so_hoc_vien_moi_xe" class="form-control form-control-sm" min="1" max="100" value="{{ $loai->SoHocVienMoiXe }}" required form="{{ $form }}"
                                           title="1 GV dạy tối đa bao nhiêu HV"></td>
                                <td><input type="number" name="so_ngay_cach" class="form-control form-control-sm" min="0" max="60" value="{{ $loai->SoNgayCach }}" required form="{{ $form }}"></td>
                                <td>
                                    <input type="date" name="ngay_khai_giang" class="form-control form-control-sm" value="{{ $loai->NgayKhaiGiang?->format('Y-m-d') }}" form="{{ $form }}"
                                           title="Để trống = tự động nối tiếp khoá thực tế">
                                    @if ($loai->NgayKhaiGiang === null)
                                        <small class="text-muted">Tự động</small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="dang_su_dung" value="1" @checked($loai->DangSuDung) form="{{ $form }}">
                                </td>
                                <td class="text-nowrap">
                                    <button type="submit" class="btn btn-sm btn-outline-primary" form="{{ $form }}">Lưu</button>
                                    <form method="POST" action="{{ route('daotao.pdt.bc.tien-do-dao-tao.loai-khoa.destroy', $loai->Id) }}" class="d-inline"
                                          onsubmit="return confirm('Xoá loại khoá «{{ $loai->TenLoai }}» và các thay đổi số GV của loại khoá này?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Xoá</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">Chưa có loại khoá dự kiến.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-light" data-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade modal-cau-hinh-tien-do" id="modal-cau-hinh-tang-xe" tabindex="-1" role="dialog"
         aria-labelledby="modal-cau-hinh-tang-xe-tieu-de" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title" id="modal-cau-hinh-tang-xe-tieu-de">Tăng / giảm GV</h6>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Từ ngày áp dụng, các khoá dự kiến khai giảng từ ngày đó của loại khoá được cộng thêm số giáo viên
                        (số âm = giảm GV). Mỗi ±1 GV đổi học viên theo <strong>HV/GV</strong> của loại khoá; trên bảng tiến độ vẫn quy đổi 2 GV = 1 xe.
                    </p>
                    @if ($errors->has('so_gv'))
                        <div class="alert alert-danger py-2">{{ $errors->first('so_gv') }}</div>
                    @endif
                    <form method="POST" action="{{ route('daotao.pdt.bc.tien-do-dao-tao.tang-xe.store') }}" class="mb-3 border rounded p-3 bg-light">
                        @csrf
                        <div class="form-row align-items-end">
                            <div class="form-group col-md-4 mb-md-0">
                                <label>Loại khoá</label>
                                <select name="loai_khoa_id" class="form-control form-control-sm" required>
                                    @foreach ($loaiKhoa as $loai)
                                        <option value="{{ $loai->Id }}" data-hv-moi-gv="{{ $loai->SoHocVienMoiXe }}">{{ $loai->TenLoai }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-3 mb-md-0">
                                <label>Từ ngày</label>
                                <input type="date" name="tu_ngay" class="form-control form-control-sm" required>
                            </div>
                            <div class="form-group col-md-2 mb-md-0">
                                <label>Số GV</label>
                                <input type="number" name="so_gv" class="form-control form-control-sm" min="-100" max="100" step="1" value="1" required>
                            </div>
                            <div class="form-group col-md-3 mb-md-0">
                                <button type="submit" class="btn btn-sm btn-navy btn-block">Thêm</button>
                            </div>
                        </div>
                    </form>
                    <table class="table table-sm table-bordered mb-0 bang-cau-hinh">
                        <thead class="thead-light">
                            <tr>
                                <th>Loại khoá</th>
                                <th>Từ ngày</th>
                                <th class="text-center">Số GV</th>
                                <th class="text-center">Số HV</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($tangXe as $item)
                                @php $soGv = (int) $item->SoXe; @endphp
                                <tr>
                                    <td>{{ $item->loaiKhoa?->TenLoai ?? '—' }}</td>
                                    <td>{{ $item->TuNgay->format('d/m/Y') }}</td>
                                    <td class="text-center font-weight-bold">{{ $soGv > 0 ? '+' : '' }}{{ $soGv }}</td>
                                    <td class="text-center">{{ $soGv > 0 ? '+' : '' }}{{ $soGv * ($item->loaiKhoa?->SoHocVienMoiXe ?? 0) }}</td>
                                    <td class="text-right">
                                        <form method="POST" action="{{ route('daotao.pdt.bc.tien-do-dao-tao.tang-xe.destroy', $item->Id) }}" class="d-inline"
                                              onsubmit="return confirm('Xoá thay đổi số GV này?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Xoá</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">Chưa có thay đổi số GV.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-light" data-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade modal-cau-hinh-tien-do" id="modal-cau-hinh-ngay-nghi" tabindex="-1" role="dialog"
         aria-labelledby="modal-cau-hinh-ngay-nghi-tieu-de" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title" id="modal-cau-hinh-ngay-nghi-tieu-de">Ngày nghỉ lễ, Tết</h6>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        Ngày nghỉ không tính vào số ngày lý thuyết / thực hành của khoá dự kiến, nên khoá trùng ngày nghỉ kéo dài thêm.
                        <strong>Số ngày</strong> = khoảng từ <strong>Từ ngày</strong> đến <strong>Đến ngày</strong> (cả hai đầu, tính tự động).
                        Để trống <strong>Đến ngày</strong> khi thêm mới = nghỉ một ngày.
                    </p>
                    <form method="POST" action="{{ route('daotao.pdt.bc.tien-do-dao-tao.ngay-nghi.store') }}" class="mb-3 border rounded p-3 bg-light">
                        @csrf
                        <div class="form-row align-items-end">
                            <div class="form-group col-md-4 mb-md-0">
                                <label>Tên ngày nghỉ</label>
                                <input type="text" name="ten_ngay_nghi" class="form-control form-control-sm" maxlength="150" required placeholder="VD: Nghỉ lễ 30/4 – 1/5">
                            </div>
                            <div class="form-group col-md-3 mb-md-0">
                                <label>Từ ngày</label>
                                <input type="date" name="tu_ngay" class="form-control form-control-sm" required>
                            </div>
                            <div class="form-group col-md-3 mb-md-0">
                                <label>Đến ngày</label>
                                <input type="date" name="den_ngay" class="form-control form-control-sm">
                            </div>
                            <div class="form-group col-md-2 mb-md-0">
                                <button type="submit" class="btn btn-sm btn-navy btn-block">Thêm</button>
                            </div>
                        </div>
                    </form>
                    <table class="table table-sm table-bordered mb-0 bang-cau-hinh">
                        <thead class="thead-light">
                            <tr>
                                <th>Tên ngày nghỉ</th>
                                <th>Từ ngày</th>
                                <th>Đến ngày</th>
                                <th class="text-center" title="Tự tính từ Từ ngày → Đến ngày">Số ngày</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ngayNghi as $item)
                                @php
                                    $formNghi = 'frm-ngay-nghi-'.$item->Id;
                                    $soNgay = $item->TuNgay->diffInDays($item->DenNgay) + 1;
                                @endphp
                                <tr>
                                    <td>
                                        <form method="POST" action="{{ route('daotao.pdt.bc.tien-do-dao-tao.ngay-nghi.update', $item->Id) }}" id="{{ $formNghi }}">
                                            @csrf
                                            @method('PUT')
                                        </form>
                                        <input type="text" name="ten_ngay_nghi" class="form-control form-control-sm" maxlength="150"
                                               value="{{ $item->TenNgayNghi }}" required form="{{ $formNghi }}">
                                    </td>
                                    <td>
                                        <input type="date" name="tu_ngay" class="form-control form-control-sm ngay-nghi-tu"
                                               value="{{ $item->TuNgay->format('Y-m-d') }}" required form="{{ $formNghi }}">
                                    </td>
                                    <td>
                                        <input type="date" name="den_ngay" class="form-control form-control-sm ngay-nghi-den"
                                               value="{{ $item->DenNgay->format('Y-m-d') }}" form="{{ $formNghi }}">
                                    </td>
                                    <td class="text-center font-weight-bold so-ngay-nghi" data-so-ngay="{{ $soNgay }}">{{ $soNgay }}</td>
                                    <td class="text-nowrap text-right">
                                        <button type="submit" class="btn btn-sm btn-outline-primary" form="{{ $formNghi }}">Lưu</button>
                                        <form method="POST" action="{{ route('daotao.pdt.bc.tien-do-dao-tao.ngay-nghi.destroy', $item->Id) }}" class="d-inline"
                                              onsubmit="return confirm('Xoá ngày nghỉ «{{ $item->TenNgayNghi }}»?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Xoá</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">Chưa có ngày nghỉ.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-light" data-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal-loai-khoa" tabindex="-1" role="dialog" aria-labelledby="modal-loai-khoa-tieu-de" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form method="POST" action="{{ route('daotao.pdt.bc.tien-do-dao-tao.loai-khoa-thuc-te.store') }}" class="modal-content" id="frm-loai-khoa-thuc-te">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title" id="modal-loai-khoa-tieu-de">Gán loại khoá — <span data-ten></span></h6>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="d-block mb-1">Áp dụng cho khoá</label>
                        <div data-ds-ma></div>
                    </div>
                    <div class="form-group mb-2">
                        <label for="ten-loai-khoa-thuc-te">Loại khoá</label>
                        <select name="ten_loai_khoa" id="ten-loai-khoa-thuc-te" class="form-control form-control-sm" required>
                            @foreach ($loaiKhoa as $loai)
                                <option value="{{ $loai->TenLoai }}" data-hang="{{ $loai->Hang }}">
                                    {{ $loai->TenLoai }}{{ $loai->DangSuDung ? '' : ' (ngừng dùng)' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <p class="small text-muted mb-0">
                        Chọn từ danh sách «Loại khoá dự kiến» cùng hạng — thiếu loại nào thì thêm trong modal «Loại khoá dự kiến».
                        Không gán riêng thì loại khoá theo hạng (<span data-mac-dinh></span>).
                    </p>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="submit" name="mac_dinh" value="1" class="btn btn-sm btn-outline-secondary" formnovalidate data-ve-mac-dinh
                            title="Bỏ loại khoá gán riêng của các khoá đã chọn">Về mặc định theo hạng</button>
                    <div>
                        <button type="button" class="btn btn-sm btn-light" data-dismiss="modal">Huỷ</button>
                        <button type="submit" class="btn btn-sm btn-navy">Lưu</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function ($) {
        const $modal = $('#modal-loai-khoa');
        const form = document.getElementById('frm-loai-khoa-thuc-te');
        const dsMa = form.querySelector('[data-ds-ma]');
        const oTen = form.querySelector('[name="ten_loai_khoa"]');
        const esc = (s) => $('<div>').text(s).html();

        $(document).on('click', '.btn-gan-loai-khoa', function () {
            const d = this.dataset;
            const maList = (d.ma || '').split(',').filter(Boolean);
            form.querySelector('[data-ten]').textContent = d.ten;
            form.querySelector('[data-mac-dinh]').textContent = d.macDinh;
            dsMa.innerHTML = maList.map((ma, i) =>
                '<div class="custom-control custom-checkbox custom-control-inline">'
                + '<input type="checkbox" class="custom-control-input" id="ma-loai-khoa-' + i + '" name="ma_khoa_lop[]" value="' + esc(ma) + '" checked>'
                + '<label class="custom-control-label" for="ma-loai-khoa-' + i + '">' + esc(ma) + '</label></div>'
            ).join('');
            oTen.querySelectorAll('option[data-tam]').forEach((o) => o.remove());
            const cungHang = [...oTen.options].filter((o) => o.dataset.hang === d.hang);
            [...oTen.options].forEach((o) => {
                o.hidden = cungHang.length > 0 && o.dataset.hang !== d.hang;
                o.disabled = o.hidden;
            });
            if (![...oTen.options].some((o) => !o.disabled && o.value === d.loai)) {
                // Loại đang gán không còn trong danh sách: giữ để thấy hiện trạng, chọn loại khác rồi lưu.
                oTen.add(new Option(d.loai + ' (không có trong danh sách)', d.loai), 0);
                oTen.options[0].dataset.tam = '1';
                oTen.options[0].disabled = true;
            }
            oTen.value = d.loai;
            form.querySelector('[data-ve-mac-dinh]').classList.toggle('d-none', d.rieng !== '1');
            $modal.modal('show');
        });
        $modal.on('shown.bs.modal', () => oTen.focus());
        form.addEventListener('submit', function (e) {
            if (!dsMa.querySelector('input:checked')) {
                e.preventDefault();
                alert('Chọn ít nhất một khoá.');
            }
        });
    })(jQuery);

    (function () {
        const macDinh = @json($macDinhTheoHang);
        const mauMacDinh = { B01: '#FFB3B3', C: '#FFD966', B: '#C5E0B4' };
        const form = document.getElementById('frm-loai-khoa-moi');
        const hang = document.getElementById('loai-khoa-moi-hang');
        const mau = document.getElementById('loai-khoa-moi-mau');
        const dienMacDinh = () => {
            Object.entries(macDinh[hang.value] || {}).forEach(([name, value]) => {
                form.querySelector(`[name="${name}"]`).value = value;
            });
            if (mau) {
                mau.value = mauMacDinh[hang.value] || '#E0E0E0';
            }
        };
        hang.addEventListener('change', dienMacDinh);
        dienMacDinh();
    })();

    @if ($errors->has('so_gv'))
        $(function () { $('#modal-cau-hinh-tang-xe').modal('show'); });
    @endif

    (function () {
        const demNgay = (tu, den) => {
            if (! tu) {
                return '—';
            }
            const a = new Date(tu + 'T12:00:00');
            const b = new Date((den || tu) + 'T12:00:00');
            if (Number.isNaN(a.getTime()) || Number.isNaN(b.getTime()) || b < a) {
                return '—';
            }
            return String(Math.round((b - a) / 86400000) + 1);
        };
        document.querySelectorAll('#modal-cau-hinh-ngay-nghi tbody tr').forEach((row) => {
            const tu = row.querySelector('.ngay-nghi-tu');
            const den = row.querySelector('.ngay-nghi-den');
            const o = row.querySelector('.so-ngay-nghi');
            if (! tu || ! o) {
                return;
            }
            const capNhat = () => { o.textContent = demNgay(tu.value, den?.value); };
            tu.addEventListener('change', capNhat);
            den?.addEventListener('change', capNhat);
        });
    })();
</script>
@endpush
