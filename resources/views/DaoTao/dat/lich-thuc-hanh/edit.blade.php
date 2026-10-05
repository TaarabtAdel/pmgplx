@extends('PMGPLX.layouts.quan-ly')

@section('title', $duAn ? 'Cấu hình lịch TH' : 'Tạo dự án lịch TH')

@push('styles')
<style>
    .lich-th-section { border: 1px solid #dee2e6; border-radius: 4px; padding: 1rem; margin-bottom: 1rem; background: #fff; }
    .lich-th-section h6 { font-weight: 600; margin-bottom: 0.75rem; }
    .nghi-block { border: 1px solid #e9ecef; border-radius: 4px; padding: 0.75rem 1rem; margin-bottom: 1rem; background: #fafafa; }
    .nghi-block-title { font-weight: 600; font-size: 0.875rem; margin-bottom: 0.35rem; }
    #cap-xe-table td { vertical-align: middle; }
    .select2-container { font-size: 0.875rem; }
</style>
@endpush

@section('content')
    @php
        use App\Support\DaoTao\LichThucHanh\CauHinhDefaults;

        $action = $duAn
            ? route('daotao.pdt.dat.lich-thuc-hanh.update', $duAn->Id)
            : route('daotao.pdt.dat.lich-thuc-hanh.store');

        $capXeRows = old('cap_xe', $cauHinh['cap_xe'] ?? []);
        if (! is_array($capXeRows)) {
            $capXeRows = [];
        }
        $chuongTrinhRows = old('chuong_trinh', $cauHinh['chuong_trinh'] ?? CauHinhDefaults::chuongTrinhHangB());
        $nghiCoDinh = old('nghi_co_dinh', $cauHinh['nghi_co_dinh'] ?? []);
        $nghiRiengRows = old('nghi_rieng', $cauHinh['nghi_rieng'] ?? []);
        $soHvMacDinh = (int) old('so_hoc_vien_mac_dinh', $cauHinh['so_hoc_vien_mac_dinh'] ?? 5);
        $ngayKgCfg = trim((string) ($cauHinh['ngay_khai_giang'] ?? ''));
        $ngayKtCfg = trim((string) ($cauHinh['ngay_ket_thuc_du_kien'] ?? ''));
        $ngayKtTruocKg = $ngayKgCfg !== '' && $ngayKtCfg !== '' && $ngayKtCfg < $ngayKgCfg;
    @endphp

    <div class="card card-panel mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <span>{{ $duAn ? 'Cấu hình lịch TH' : 'Tạo dự án lịch TH' }} — {{ $cauHinh['ma_khoa'] ?: 'khóa mới' }}</span>
            <div class="d-flex flex-wrap align-items-center">
                @if ($duAn && ($coLichDaSinh ?? false))
                    <a href="{{ route('daotao.pdt.dat.lich-thuc-hanh.preview', $duAn->Id) }}"
                       class="btn btn-sm btn-outline-success mr-2 mb-1 mb-md-0">Xem trước lịch</a>
                @endif
                <a href="{{ route('daotao.pdt.dat.lich-thuc-hanh.index') }}" class="btn btn-sm btn-outline-secondary">← Danh sách dự án</a>
            </div>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success small py-2">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger small py-2">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger small py-2">
                    <ul class="mb-0 pl-3">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ $action }}" id="form-lich-th-cau-hinh">
                @csrf
                @if ($duAn) @method('PUT') @endif

                @php
                    $hangHienTai = old('hang_dao_tao', $cauHinh['hang_dao_tao'] ?? 'B');
                @endphp

                <div class="lich-th-section">
                    <h6>Thông tin khóa</h6>
                    <div class="row">
                        <div class="col-md-2 form-group">
                            <label class="small mb-1">Hạng</label>
                            <select class="form-control form-control-sm" name="hang_dao_tao" id="js-hang-dao-tao"
                                    data-initial-hang="{{ $hangHienTai }}">
                                @foreach (['B' => 'B (sàn)', 'B.01' => 'B tự động', 'C1' => 'C1'] as $val => $label)
                                    <option value="{{ $val }}" @selected($hangHienTai === $val)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 form-group">
                            <label class="small mb-1">Mã khóa <span class="text-danger">*</span></label>
                            <input class="form-control form-control-sm" name="ma_khoa" required
                                   value="{{ old('ma_khoa', $cauHinh['ma_khoa'] ?? '') }}">
                        </div>
                        <div class="col-md-2 form-group">
                            <label class="small mb-1">Ngày khai giảng <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm" name="ngay_khai_giang" required
                                   value="{{ old('ngay_khai_giang', $cauHinh['ngay_khai_giang'] ?? '') }}">
                        </div>
                        <div class="col-md-2 form-group">
                            <label class="small mb-1">Ngày kết thúc (dự kiến)</label>
                            <input type="date" class="form-control form-control-sm @if($ngayKtTruocKg) is-invalid @endif" name="ngay_ket_thuc_du_kien"
                                   value="{{ old('ngay_ket_thuc_du_kien', $cauHinh['ngay_ket_thuc_du_kien'] ?? '') }}">
                            <small class="text-muted">Để trống = hệ thống ước ~48 ngày từ khai giảng</small>
                            @if ($ngayKtTruocKg)
                                <div class="invalid-feedback d-block">Ngày kết thúc đang trước ngày khai giảng — sinh lịch sẽ không có dòng ngày.</div>
                            @endif
                        </div>
                        <div class="col-md-2 form-group">
                            <label class="small mb-1">Hệ số quy đổi</label>
                            <input type="number" step="0.1" class="form-control form-control-sm" name="he_so_quy_doi"
                                   value="{{ old('he_so_quy_doi', $cauHinh['he_so_quy_doi'] ?? 2) }}">
                            <span class="badge badge-warning mt-1">chưa được sếp xác nhận</span>
                        </div>
                        <div class="col-md-1 form-group">
                            <label class="small mb-1">Giờ/ngày</label>
                            <select class="form-control form-control-sm" name="gio_day_moi_ngay">
                                @foreach ([8, 9, 10] as $h)
                                    <option value="{{ $h }}" @selected((int) old('gio_day_moi_ngay', $cauHinh['gio_day_moi_ngay'] ?? 8) === $h)>{{ $h }}h</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2 form-group mb-0">
                            <label class="small mb-1">Số HV mặc định / GV</label>
                            <input type="number" min="1" max="99" class="form-control form-control-sm" name="so_hoc_vien_mac_dinh"
                                   value="{{ $soHvMacDinh }}">
                        </div>
                        <div class="col-md-2 form-group mb-0">
                            <label class="small mb-1">Tối đa giờ/ngày</label>
                            <input type="number" class="form-control form-control-sm" name="gio_day_moi_ngay_toi_da"
                                   value="{{ old('gio_day_moi_ngay_toi_da', $cauHinh['gio_day_moi_ngay_toi_da'] ?? 10) }}">
                        </div>
                    </div>
                </div>

                @if ($cauHinh['dieu_kien_dat_hang'] ?? null)
                    @php
                        $dkDat = $cauHinh['dieu_kien_dat_hang'];
                        $gt = (float) ($dkDat['gio_cao_toc_gio'] ?? 0);
                        $fmtDatSo = static function ($v): string {
                            return rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
                        };
                    @endphp
                    @if ($gt <= 0)
                        <div class="alert alert-warning small py-2">
                            Hạng {{ $hangHienTai }} chưa khai giờ cao tốc trên
                            <a href="{{ route('daotao.pdt.dat.dieu-kien-dat') }}">Điều kiện đạt DAT</a>.
                        </div>
                    @endif
                    <div class="mb-3">
                        <div class="small mb-1">
                            <strong>Điều kiện DAT (từ DB)</strong>
                            — <a href="{{ route('daotao.pdt.dat.dieu-kien-dat') }}">Quản lý ngưỡng</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0 bg-light">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Hạng</th>
                                        <th>Tập lái ban đêm (giờ)</th>
                                        <th>Xe số tự động (giờ)</th>
                                        <th>Giờ cao tốc</th>
                                        <th>Số giờ học</th>
                                        <th>Tổng quãng đường (km)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>{{ $dkDat['hang'] ?? $hangHienTai }}</td>
                                        <td>{{ $fmtDatSo($dkDat['tap_lai_ban_dem_gio'] ?? 0) }}</td>
                                        <td>{{ $fmtDatSo($dkDat['xe_so_tu_dong_gio'] ?? 0) }}</td>
                                        <td>{{ $fmtDatSo($dkDat['gio_cao_toc_gio'] ?? 0) }}</td>
                                        <td>{{ $fmtDatSo($dkDat['so_gio_hoc'] ?? 0) }}</td>
                                        <td>{{ $fmtDatSo($dkDat['tong_quang_duong_km'] ?? 0) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <div class="lich-th-section">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0">Cặp xe / giáo viên</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-them-cap">+ Thêm cặp</button>
                    </div>
                    <p class="small text-muted mb-1">Mỗi dòng = một cặp xe (GV sáng + GV chiều cùng biển số).</p>
                    <p class="small text-muted mb-2">GV sáng và GV chiều có thể chọn cùng một giáo viên.</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0" id="cap-xe-table">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width:3rem">#</th>
                                    <th>Biển số</th>
                                    <th>GV sáng</th>
                                    <th>GV chiều</th>
                                    <th style="width:5rem">HV sáng</th>
                                    <th style="width:5rem">HV chiều</th>
                                    <th style="width:3rem"></th>
                                </tr>
                            </thead>
                            <tbody id="cap-xe-body">
                                @forelse ($capXeRows as $i => $cap)
                                    @if (! is_array($cap)) @continue @endif
                                    <tr>
                                        <td class="text-center align-middle">
                                            <span class="cap-stt-label">{{ $cap['stt'] ?? ($i + 1) }}</span>
                                            <input type="hidden" class="cap-stt-input" name="cap_xe[{{ $i }}][stt]" value="{{ $cap['stt'] ?? ($i + 1) }}">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" name="cap_xe[{{ $i }}][bien_so]"
                                                   list="datalist-bien-so" value="{{ $cap['bien_so'] ?? '' }}">
                                        </td>
                                        <td>
                                            <select class="form-control form-control-sm gv-select" name="cap_xe[{{ $i }}][gv_sang]" data-ten-target=".ten-sang">
                                                <option value="">— Chọn GV —</option>
                                                @foreach ($giaoViens as $gv)
                                                    <option value="{{ $gv->MaGV }}" data-ten="{{ $gv->ho_ten }}"
                                                        @selected(($cap['gv_sang'] ?? '') == $gv->MaGV)>{{ $gv->ho_ten }} ({{ $gv->MaGV }})</option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" class="ten-sang" name="cap_xe[{{ $i }}][ten_gv_sang]" value="{{ $cap['ten_gv_sang'] ?? '' }}">
                                        </td>
                                        <td>
                                            <select class="form-control form-control-sm gv-select" name="cap_xe[{{ $i }}][gv_chieu]" data-ten-target=".ten-chieu">
                                                <option value="">— Chọn GV —</option>
                                                @foreach ($giaoViens as $gv)
                                                    <option value="{{ $gv->MaGV }}" data-ten="{{ $gv->ho_ten }}"
                                                        @selected(($cap['gv_chieu'] ?? '') == $gv->MaGV)>{{ $gv->ho_ten }} ({{ $gv->MaGV }})</option>
                                                @endforeach
                                            </select>
                                            <input type="hidden" class="ten-chieu" name="cap_xe[{{ $i }}][ten_gv_chieu]" value="{{ $cap['ten_gv_chieu'] ?? '' }}">
                                        </td>
                                        <td>
                                            <input type="number" min="1" class="form-control form-control-sm" name="cap_xe[{{ $i }}][so_hoc_vien_gv_sang]"
                                                   value="{{ $cap['so_hoc_vien_gv_sang'] ?? $soHvMacDinh }}">
                                        </td>
                                        <td>
                                            <input type="number" min="1" class="form-control form-control-sm" name="cap_xe[{{ $i }}][so_hoc_vien_gv_chieu]"
                                                   value="{{ $cap['so_hoc_vien_gv_chieu'] ?? $soHvMacDinh }}">
                                        </td>
                                        <td class="text-center align-middle">
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-xoa-cap">&times;</button>
                                        </td>
                                    </tr>
                                @empty
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <datalist id="datalist-bien-so">
                        @foreach ($xeTaps as $xe)
                            <option value="{{ $xe->BienSo }}"></option>
                        @endforeach
                    </datalist>
                </div>

                <div class="lich-th-section">
                    <button type="button" class="btn btn-link btn-sm px-0 text-left w-100 mb-1" data-toggle="collapse"
                            data-target="#chuong-trinh-collapse" aria-expanded="false" aria-controls="chuong-trinh-collapse">
                        <h6 class="mb-0 d-inline font-weight-bold text-body">Chương trình giờ</h6>
                        <span class="small text-muted ml-1">(mặc định theo hạng — bấm để mở)</span>
                    </button>
                    <div class="collapse" id="chuong-trinh-collapse">
                        <div class="d-flex justify-content-end mb-2 mt-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-chuong-trinh-mac-dinh">Khôi phục mặc định theo hạng</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Mã</th>
                                        <th>Tên giai đoạn</th>
                                        <th style="width:6rem">Giờ</th>
                                        <th style="width:5rem">Thứ tự</th>
                                        <th style="width:4rem">DAT</th>
                                    </tr>
                                </thead>
                                <tbody id="chuong-trinh-body">
                                    @foreach ($chuongTrinhRows as $idx => $row)
                                        @if (! is_array($row)) @continue @endif
                                        <tr>
                                            <td>
                                                <input type="hidden" name="chuong_trinh[{{ $idx }}][ma]" value="{{ $row['ma'] ?? '' }}">
                                                <code class="small">{{ $row['ma'] ?? '' }}</code>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm" name="chuong_trinh[{{ $idx }}][ten]" value="{{ $row['ten'] ?? '' }}">
                                            </td>
                                            <td>
                                                <input type="number" step="0.5" min="0" class="form-control form-control-sm" name="chuong_trinh[{{ $idx }}][gio]" value="{{ $row['gio'] ?? 0 }}">
                                            </td>
                                            <td>
                                                <input type="number" min="0" class="form-control form-control-sm" name="chuong_trinh[{{ $idx }}][thu_tu]" value="{{ $row['thu_tu'] ?? 0 }}">
                                            </td>
                                            <td class="text-center">
                                                <input type="checkbox" name="chuong_trinh[{{ $idx }}][tinh_dat]" value="1"
                                                    @checked(filter_var($row['tinh_dat'] ?? false, FILTER_VALIDATE_BOOLEAN))>
                                            </td>
                                            <input type="hidden" name="chuong_trinh[{{ $idx }}][mau]" value="{{ $row['mau'] ?? ($row['ma'] ?? '') }}">
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="lich-th-section">
                    <h6>Nghỉ học</h6>
                    <p class="small text-muted mb-3">
                        <strong>Nghỉ cả khóa</strong> (lễ, thứ nghỉ cố định, …) hoặc <strong>nghỉ riêng từng cặp xe</strong>.
                    </p>

                    <div class="nghi-block">
                        <div class="nghi-block-title">1. Ngày nghỉ cả khóa</div>
                        <p class="small text-muted mb-2">Mọi cặp xe đều nghỉ vào các ngày này.</p>
                        <div class="mb-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btn-nghi-auto-thu-nam">
                                Tự điền các ngày thứ năm (khai giảng → kết thúc)
                            </button>
                            <span class="small text-muted ml-1">Dùng ngày ở mục Thông tin khóa; nếu chưa có ngày kết thúc thì ước thêm 48 ngày.</span>
                        </div>
                        <div id="nghi-co-dinh-list" class="mb-2">
                            @foreach ($nghiCoDinh as $d)
                                <div class="input-group input-group-sm mb-1 nghi-ngay-row">
                                    <input type="date" class="form-control" name="nghi_co_dinh[]" value="{{ $d }}">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary btn-xoa-ngay" title="Xóa">&times;</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-them-nghi-co-dinh">+ Thêm ngày</button>
                    </div>

                    <div class="nghi-block mb-0">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <div class="nghi-block-title mb-0">2. Nghỉ riêng theo cặp xe</div>
                                <p class="small text-muted mb-0">Chỉ cặp xe có STT/biển số tương ứng nghỉ; các cặp khác vẫn học.</p>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0 ml-2" id="btn-them-nghi-rieng">+ Thêm cặp</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0 bg-white">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width:4rem">STT cặp</th>
                                        <th style="width:8rem">Biển số</th>
                                        <th>Ngày nghỉ của cặp này</th>
                                        <th style="width:3rem"></th>
                                    </tr>
                                </thead>
                                <tbody id="nghi-rieng-body">
                                    @foreach ($nghiRiengRows as $i => $nr)
                                        @if (! is_array($nr)) @continue @endif
                                        @php
                                            $ngayRieng = $nr['ngay'] ?? [];
                                            if (! is_array($ngayRieng)) {
                                                $ngayRieng = [];
                                            }
                                            if ($ngayRieng === [] && ! empty($nr['ngay_text'])) {
                                                $ngayRieng = preg_split('/[\s,;]+/', (string) $nr['ngay_text']) ?: [];
                                            }
                                            $ngayRieng = array_values(array_filter(array_map('trim', $ngayRieng)));
                                        @endphp
                                        <tr class="nghi-rieng-row">
                                            <td><input type="number" min="1" class="form-control form-control-sm" name="nghi_rieng[{{ $i }}][cap_stt]" value="{{ $nr['cap_stt'] ?? '' }}"></td>
                                            <td><input type="text" class="form-control form-control-sm" name="nghi_rieng[{{ $i }}][bien_so]" list="datalist-bien-so" value="{{ $nr['bien_so'] ?? '' }}"></td>
                                            <td>
                                                <div class="nghi-rieng-ngay-list">
                                                    @foreach ($ngayRieng as $d)
                                                        <div class="input-group input-group-sm mb-1">
                                                            <input type="date" class="form-control" name="nghi_rieng[{{ $i }}][ngay][]" value="{{ $d }}">
                                                            <div class="input-group-append">
                                                                <button type="button" class="btn btn-outline-secondary btn-xoa-rieng-ngay">&times;</button>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-secondary btn-them-rieng-ngay">+ Ngày</button>
                                            </td>
                                            <td class="text-center align-middle"><button type="button" class="btn btn-sm btn-outline-danger btn-xoa-rieng">&times;</button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-sm">Lưu cấu hình</button>
                <a href="{{ route('daotao.pdt.dat.lich-thuc-hanh.index') }}" class="btn btn-outline-secondary btn-sm">Hủy</a>
            </form>

            @if ($duAn)
                <hr class="my-4">
                <h6 class="font-weight-bold">Sinh lịch</h6>
                <p class="small text-muted mb-2">
                    <strong>① Lưu cấu hình</strong> → <strong>② Sinh lịch</strong> → <strong>③ Xem trước lịch</strong>.
                </p>
                <form method="POST" action="{{ route('daotao.pdt.dat.lich-thuc-hanh.generate', $duAn->Id) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm">Sinh lịch</button>
                </form>
                @if ($coLichDaSinh ?? false)
                    <a href="{{ route('daotao.pdt.dat.lich-thuc-hanh.preview', $duAn->Id) }}" class="btn btn-sm btn-outline-success ml-2">Xem trước lịch</a>
                @endif
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
window.lichThucHanhForm = {
    giaoViens: @json($giaoViens->map(fn ($g) => ['ma' => (string) $g->MaGV, 'ten' => (string) $g->ho_ten])->values()),
    bienSoXe: @json($xeTaps->pluck('BienSo')->values()),
    chuongTrinhMacDinhB: @json(CauHinhDefaults::chuongTrinhHangB()),
    chuongTrinhMacDinhB01: @json(CauHinhDefaults::chuongTrinhHangB01()),
    soHocVienMacDinh: {{ $soHvMacDinh }},
    capXeSeed: @json(array_values($capXeRows)),
    hangReloadUrl: @json($duAn
        ? route('daotao.pdt.dat.lich-thuc-hanh.edit', $duAn->Id)
        : route('daotao.pdt.dat.lich-thuc-hanh.create')),
};
</script>
<script src="{{ asset('js/lich-thuc-hanh-cau-hinh.js') }}"></script>
<script>
$(function () {
    if ($('#cap-xe-body tr').length === 0 && window.lichThucHanhForm.capXeSeed.length === 0) {
        $('#btn-them-cap').trigger('click');
    }
});
</script>
@endpush
