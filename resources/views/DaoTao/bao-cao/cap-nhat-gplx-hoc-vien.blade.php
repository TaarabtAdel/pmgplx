@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Quản lý cập nhật GPLX học viên')

@php
    use App\Support\DaoTao\CapNhatGPLXHienThi;
    use App\Support\PMGPLX\NgayVn;

    $fmtNgayPm = function (mixed $value): string {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('d/m/Y');
        }
        $s = trim((string) $value);
        if ($s === '') {
            return '';
        }
        if (strlen($s) >= 10 && preg_match('/^\d{4}-\d{2}-\d{2}/', $s)) {
            return substr($s, 8, 2).'/'.substr($s, 5, 2).'/'.substr($s, 0, 4);
        }

        return NgayVn::format($s, 'yyyymmdd');
    };
@endphp

@push('styles')
<style>
    .gplx-table td, .gplx-table th { vertical-align: middle; white-space: nowrap; }
    .gplx-table .col-ho-ten { white-space: normal; min-width: 14rem; max-width: 22rem; }
    .gplx-table .col-ho-ten .ma-hoc-vien { font-size: .85rem; color: #6c757d; }
    .gplx-table .col-noi-hk { white-space: normal; min-width: 11rem; max-width: 18rem; overflow-wrap: anywhere; font-size: .85rem; }
    .gplx-table .cell-ml { background: #fffef5; }
    .gplx-table tbody tr.selected { background-color: #dcf2e1 !important; color: #212529; }
    .gplx-table tfoot td { font-weight: 600; background: #f1f3f5; }
    #bang-ung-vien-khung { max-height: 22rem; overflow-y: auto; }
    #bang-ung-vien tr.da-co td { color: #adb5bd; }
</style>
@endpush

@section('content')
    @php
        $moThemHv = $themKh !== '' || request()->boolean('mo_them');
        $urlBoChonKhoa = route('daotao.pdt.bc.cap-nhat-gplx', array_merge(request()->except(['them_kh', 'page']), ['mo_them' => 1]));
    @endphp

    <div class="card card-panel">
        <div class="card-header d-flex align-items-center">
            <span class="mr-auto">Thêm học viên vào danh sách cập nhật GPLX</span>
            <button type="button" class="btn btn-sm {{ $moThemHv ? 'btn-outline-secondary' : 'btn-success' }}" id="btn-mo-them-hv"
                    data-toggle="collapse" data-target="#khung-them-hv" aria-expanded="{{ $moThemHv ? 'true' : 'false' }}" aria-controls="khung-them-hv">
                {{ $moThemHv ? '▲ Ẩn' : '＋ Thêm học viên' }}
            </button>
        </div>
        <div class="collapse {{ $moThemHv ? 'show' : '' }}" id="khung-them-hv">
        <div class="card-body">
            <p class="small text-muted mb-3">Danh sách lưu trên MANHLINH. Không ghi / sửa dữ liệu bên <code>GPLX_BAN_MOI</code>.</p>
            <form method="GET" action="{{ route('daotao.pdt.bc.cap-nhat-gplx') }}" class="form-row align-items-end">
                @foreach (array_filter(['tu_khoa' => $filters['tu_khoa'], 'ma_kh' => $filters['ma_kh'], 'cap_nhat' => $filters['cap_nhat']]) as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach
                <div class="form-group col-md-5 mb-0">
                    <label for="them_kh">Chọn khoá</label>
                    <select name="them_kh" id="them_kh" class="form-control form-control-sm">
                        <option value=""></option>
                        @foreach ($khoaHocs as $kh)
                            <option value="{{ $kh->MaKH }}" @selected($themKh === $kh->MaKH)>{{ $kh->TenKH }} ({{ $kh->MaKH }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2 mb-0">
                    <button type="submit" class="btn btn-sm btn-primary btn-block">Xem học viên của khoá</button>
                </div>
            </form>

            <div class="border-top mt-3 pt-3">
                <p class="small text-muted mb-2">Hoặc tìm theo tên / CMT / Mã ĐK (không cần chọn khoá trước):</p>
                <form method="POST" action="{{ route('daotao.pdt.bc.cap-nhat-gplx.store') }}" class="form-row align-items-end" id="form-them-tim-hv">
                    @csrf
                    <div class="form-group col-md-9 mb-0">
                        <label for="tim_hv_them">Học viên</label>
                        <select name="ma_dk[]" id="tim_hv_them" class="form-control form-control-sm" required></select>
                    </div>
                    <div class="form-group col-md-3 mb-0">
                        <button type="submit" class="btn btn-sm btn-success btn-block">＋ Thêm học viên</button>
                    </div>
                </form>
            </div>

            @if ($ungVien !== null)
                @php $soChuaCo = $ungVien->where('da_co', false)->count(); @endphp
                <form method="POST" action="{{ route('daotao.pdt.bc.cap-nhat-gplx.store') }}" class="mt-3" id="form-them-hv">
                    @csrf
                    <input type="hidden" name="ma_kh" value="{{ $themKh }}">
                    <div class="d-flex flex-wrap align-items-center mb-2">
                        <span class="mr-3">
                            Khoá <strong>{{ $themKh }}</strong>: {{ $ungVien->count() }} học viên,
                            {{ $ungVien->count() - $soChuaCo }} đã có trong danh sách.
                        </span>
                        <a href="{{ $urlBoChonKhoa }}" class="btn btn-sm btn-outline-secondary mr-2 {{ $soChuaCo > 0 ? 'order-last ml-2' : '' }}"
                           title="Ẩn danh sách học viên của khoá và chọn lại khoá khác">✕ Ẩn danh sách</a>
                        @if ($soChuaCo > 0)
                            <button type="button" class="btn btn-sm btn-link px-1" data-chon="1">Chọn tất cả</button>
                            <button type="button" class="btn btn-sm btn-link px-1 mr-auto" data-chon="0">Bỏ chọn</button>
                            <button type="submit" class="btn btn-sm btn-success" id="btn-them-hv">＋ Thêm <span id="so-chon">{{ $soChuaCo }}</span> học viên</button>
                        @endif
                    </div>
                    @if ($ungVien->isEmpty())
                        <p class="text-muted mb-0">Khoá này chưa có học viên.</p>
                    @else
                        <div class="table-responsive" id="bang-ung-vien-khung">
                            <table class="table table-sm table-bordered table-hover mb-0" id="bang-ung-vien">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 2.5rem;"></th>
                                        <th>Mã ĐK</th>
                                        <th>Họ và tên</th>
                                        <th>Ngày sinh</th>
                                        <th>Số CMT</th>
                                        <th>Số hồ sơ</th>
                                        <th>Hạng GPLX</th>
                                        <th>GPLX đã có</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($ungVien as $hv)
                                        <tr class="{{ $hv->da_co ? 'da-co' : '' }}">
                                            <td class="text-center">
                                                @unless ($hv->da_co)
                                                    <input type="checkbox" class="chon-hv" name="ma_dk[]" value="{{ $hv->MaDK }}" checked>
                                                @endunless
                                            </td>
                                            <td>{{ $hv->MaDK }}</td>
                                            <td>{{ $hv->HoVaTen ?: trim(($hv->HoDemNLX ?? '').' '.($hv->TenNLX ?? '')) }}</td>
                                            <td>{{ NgayVn::format($hv->NgaySinh, 'yyyymmdd') }}</td>
                                            <td>{{ $hv->SoCMT }}</td>
                                            <td>{{ $hv->SoHoSo }}</td>
                                            <td>{{ $hv->HangGPLX }}</td>
                                            <td>{{ $hv->SoGPLXDaCo }}</td>
                                            <td>@if ($hv->da_co)<span class="badge badge-secondary">Đã có</span>@endif</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </form>
            @endif
        </div>
        </div>
    </div>

    <div class="card card-panel">
        <div class="card-header">Tìm kiếm</div>
        <div class="card-body">
            <form method="GET" action="{{ route('daotao.pdt.bc.cap-nhat-gplx') }}" class="form-row align-items-end">
                <div class="form-group col-md-4 mb-0">
                    <label>Mã ĐK|Họ tên|CMT|Số hồ sơ|Số GPLX</label>
                    <input type="text" class="form-control form-control-sm" name="tu_khoa" value="{{ $filters['tu_khoa'] }}" placeholder="Nhập từ khoá">
                </div>
                <div class="form-group col-md-4 mb-0">
                    <label for="loc_ma_kh">Khoá</label>
                    <select name="ma_kh" id="loc_ma_kh" class="form-control form-control-sm">
                        <option value=""></option>
                        @foreach ($khoaTrongDanhSach as $kh)
                            <option value="{{ $kh->MaKH }}" @selected($filters['ma_kh'] === (string) $kh->MaKH)>
                                {{ $kh->TenKH ? $kh->TenKH.' ('.$kh->MaKH.')' : $kh->MaKH }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2 mb-0">
                    <label>Cập nhật GPLX</label>
                    <select name="cap_nhat" class="form-control form-control-sm">
                        <option value="">—Tất cả—</option>
                        <option value="1" @selected($filters['cap_nhat'] === '1')>Đã cập nhật</option>
                        <option value="0" @selected($filters['cap_nhat'] === '0')>Chưa cập nhật</option>
                    </select>
                </div>
                <div class="form-group col-md-2 mb-0">
                    <button type="submit" class="btn btn-sm btn-primary btn-block">Tìm kiếm</button>
                    <a href="{{ route('daotao.pdt.bc.cap-nhat-gplx') }}" class="btn btn-sm btn-outline-secondary btn-block mt-1" title="Làm mới">↻</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-panel">
        <div class="card-header">Danh sách cập nhật GPLX học viên</div>
        <div class="card-body">
            <form method="POST" action="{{ route('daotao.pdt.bc.cap-nhat-gplx.hang-loat') }}" id="form-hang-loat">
                @csrf
                <input type="hidden" name="hanh_dong" id="hanh_dong">
            </form>

            <div class="d-flex flex-wrap align-items-center mb-2">
                <div class="btn-group btn-group-sm mr-2 mb-2">
                    <button type="button" class="btn btn-success btn-hang-loat" data-hanh-dong="cap_nhat">✔ Đã cập nhật GPLX</button>
                    <button type="button" class="btn btn-outline-success btn-hang-loat" data-hanh-dong="bo_cap_nhat">Bỏ cập nhật</button>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger mr-2 mb-2 btn-hang-loat" data-hanh-dong="xoa">✕ Xoá khỏi danh sách</button>
                <span class="small text-muted mb-2 mr-auto">Đã chọn <strong id="so-tick">0</strong></span>
                <a href="{{ route('daotao.pdt.bc.cap-nhat-gplx.export', array_filter(request()->only(['tu_khoa', 'ma_kh', 'cap_nhat']), fn ($v) => $v !== null && $v !== '')) }}"
                   class="btn btn-sm btn-outline-success mr-3 mb-2"
                   title="Xuất toàn bộ {{ number_format($items->total()) }} học viên theo bộ lọc hiện tại (không phân trang)">⬇ Xuất Excel ({{ number_format($items->total()) }})</a>

                <div class="d-flex flex-wrap align-items-center mb-2">
                    <span class="mr-3">Tổng số bản ghi: <strong>{{ number_format($items->total()) }}</strong></span>
                    <form method="GET" action="{{ route('daotao.pdt.bc.cap-nhat-gplx') }}" class="form-inline mr-3">
                        @foreach (request()->except(['per_page', 'page']) as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        <label class="mr-2 mb-0">Số bản ghi/trang</label>
                        <select name="per_page" class="form-control form-control-sm" onchange="this.form.submit()">
                            @foreach ([20, 50, 100, 200] as $n)
                                <option value="{{ $n }}" @selected((int) $filters['per_page'] === $n)>{{ $n }}</option>
                            @endforeach
                        </select>
                    </form>
                    {{ $items->onEachSide(1)->links('pagination::bootstrap-4') }}
                </div>
            </div>

            <p class="small text-muted mb-2">
                Cột giống mẫu Excel: HKTT, Đã có GPLX, Ngày trúng tuyển lấy MANHLINH nếu đã nhập, không có thì PMGPLX (địa chỉ qua <code>DM_DVHC</code>).
                <strong>Số GPLX</strong> / <strong>Ngày cấp GPLX</strong> ưu tiên GPLX cập nhật trên MANHLINH. Dòng dưới họ tên = <strong>Mã ĐK</strong> (vd. <code>44007-…</code>).
                Ô nền vàng nhạt = giá trị MANHLINH (<code>CapNhatGPLXHocVienBoSung</code>).
            </p>

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped table-hover table-data mb-0 gplx-table">
                    <thead>
                        <tr>
                            <th class="text-center"><input type="checkbox" id="tick-tat-ca" title="Chọn cả trang"></th>
                            <th class="text-center" style="width: 1.75rem;" title="Đã cập nhật GPLX"></th>
                            <th>STT</th>
                            <th class="col-ho-ten">Họ và tên</th>
                            <th>Ngày sinh</th>
                            <th>Số CMND</th>
                            <th class="col-noi-hk" title="HKTT — MANHLINH hoặc PMGPLX (DM_DVHC)">Nơi đăng ký hộ khẩu</th>
                            <th>Đã có GPLX</th>
                            <th>Số GPLX</th>
                            <th>Ngày cấp GPLX</th>
                            <th>Ngày trúng tuyển</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $i => $row)
                            @php
                                $hv = $hocVien[(string) $row->MaDK] ?? null;
                                $bs = $row->boSung;
                                $ht = CapNhatGPLXHienThi::dong($row, $hv, $bs, $dvhcMap ?? []);
                                $hoTen = $ht['ho_ten'];
                                $formBs = CapNhatGPLXHienThi::giaTriFormBoSung($bs, $hv, $dvhcMap ?? []);
                                $formGplx = CapNhatGPLXHienThi::giaTriFormGplx($row, $hv);
                                $cellMl = static function (string $val, bool $tuMl): string {
                                    if ($val === '') {
                                        return '<span class="text-muted">—</span>';
                                    }
                                    $cls = $tuMl ? ' cell-ml' : '';

                                    return '<span class="'.$cls.'">'.e($val).'</span>';
                                };
                            @endphp
                            <tr>
                                <td class="text-center"><input type="checkbox" class="tick-dong" name="ids[]" value="{{ $row->Id }}" form="form-hang-loat"></td>
                                <td class="text-center">
                                    @if ($row->DaCapNhatGPLX)
                                        <span class="text-success font-weight-bold" title="Đã cập nhật GPLX">✓</span>
                                    @endif
                                </td>
                                <td>{{ $items->firstItem() + $i }}</td>
                                <td class="col-ho-ten">
                                    @if ($hoTen !== '')
                                        <div>{{ $hoTen }}</div>
                                        @if ($ht['ma_hoc_vien'] !== '')
                                            <div class="ma-hoc-vien" title="Mã ĐK">{{ $ht['ma_hoc_vien'] }}</div>
                                        @endif
                                    @else
                                        <div class="text-danger">Không tìm thấy bên PMGPLX</div>
                                    @endif
                                </td>
                                <td>{!! $cellMl($ht['ngay_sinh'], false) !!}</td>
                                <td><code>{!! $cellMl($ht['so_cmnd'], false) !!}</code></td>
                                <td class="col-noi-hk">{!! $cellMl($ht['noi_dk_ho_khau'], $ht['ml']['noi_dk_ho_khau']) !!}</td>
                                <td>{!! $cellMl($ht['da_co_gplx'], $ht['ml']['da_co_gplx']) !!}</td>
                                <td>{!! $cellMl($ht['so_gplx'], (bool) $row->SoGPLX) !!}</td>
                                <td>{!! $cellMl($ht['ngay_cap_gplx'], (bool) $row->NgayCapGPLX) !!}</td>
                                <td>{!! $cellMl($ht['ngay_trung_tuyen'], $ht['ml']['ngay_trung_tuyen']) !!}</td>
                                <td class="text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-primary py-0 btn-sua"
                                            data-url="{{ route('daotao.pdt.bc.cap-nhat-gplx.update', $row->Id) }}"
                                            data-ten="{{ $row->MaDK }} — {{ $hoTen }}"
                                            data-cap-nhat="{{ (int) $row->DaCapNhatGPLX }}"
                                            data-so-gplx="{{ $formGplx['so_gplx'] }}"
                                            data-ngay-cap="{{ $formGplx['ngay_cap_gplx'] }}"
                                            data-noi-hk="{{ $formBs['noi_dk_ho_khau'] }}"
                                            data-da-co-gplx="{{ $formBs['da_co_gplx'] }}"
                                            data-ngay-tt="{{ $formBs['ngay_trung_tuyen'] }}"
                                            data-ngay-sinh="{{ $ht['ngay_sinh'] }}"
                                            data-so-cmnd="{{ $ht['so_cmnd'] }}"
                                            data-pm-hk="{{ $ht['noi_dk_ho_khau_pm'] }}"
                                            data-pm-da-co="{{ $hv?->HangGPLXDaCo ?? '' }}"
                                            data-pm-so="{{ $hv?->SoGPLXDaCo ?? '' }}"
                                            data-pm-ngay-cap="{{ $fmtNgayPm($hv?->NgayCapGPLXDaCo) }}"
                                            data-pm-ngay-tt="{{ $fmtNgayPm($hv?->NgayTTGPLXDaCo) }}"
                                            title="Sửa">✎</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center py-4">
                                    @if (collect($filters)->except('per_page')->filter(fn ($v) => $v !== '')->isNotEmpty())
                                        Không có học viên khớp bộ lọc.
                                    @else
                                        Chưa có học viên. Chọn khoá ở phần trên để thêm.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($items->total() > 0)
                        <tfoot>
                            <tr>
                                <td colspan="9" class="text-right">Theo bộ lọc ({{ number_format($tongTrangThai['tong']) }} học viên)</td>
                                <td colspan="3">
                                    Đã cập nhật: {{ number_format($tongTrangThai['da_cap_nhat']) }}
                                    · Chưa: {{ number_format($tongTrangThai['chua_cap_nhat']) }}
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalSuaGplx" tabindex="-1" role="dialog" aria-labelledby="modalSuaGplxLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form method="POST" class="modal-content" id="form-sua-gplx">
                @csrf
                @method('PUT')
                <div class="modal-header py-2">
                    <h5 class="modal-title" id="modalSuaGplxLabel">Cập nhật GPLX học viên</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="font-weight-bold mb-1" id="sua-ten"></p>
                    <p class="small text-muted mb-2" id="sua-pm-co-ban"></p>
                    <div class="small text-muted border rounded p-2 mb-3 bg-light" id="sua-pm-gplx"></div>
                    <div class="custom-control custom-checkbox mb-3">
                        <input type="hidden" name="da_cap_nhat_gplx" value="0">
                        <input type="checkbox" class="custom-control-input" id="sua-cap-nhat" name="da_cap_nhat_gplx" value="1">
                        <label class="custom-control-label" for="sua-cap-nhat">Đã cập nhật GPLX</label>
                    </div>
                    <p class="small text-muted mb-2">Các ô dưới trùng cột Excel. Trống HKTT / Đã có / Ngày TT = bỏ ghi MANHLINH, hiển thị lại PMGPLX.</p>
                    <div class="form-group mb-2">
                        <label for="sua-noi-hk" class="small mb-1">Nơi đăng ký hộ khẩu</label>
                        <input type="text" class="form-control form-control-sm" id="sua-noi-hk" name="noi_dk_ho_khau" maxlength="500">
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6 mb-2">
                            <label for="sua-da-co-gplx" class="small mb-1">Đã có GPLX</label>
                            <input type="text" class="form-control form-control-sm" id="sua-da-co-gplx" name="da_co_gplx" maxlength="30">
                        </div>
                        <div class="form-group col-md-6 mb-2">
                            <label for="sua-ngay-tt" class="small mb-1">Ngày trúng tuyển</label>
                            <input type="date" class="form-control form-control-sm" id="sua-ngay-tt" name="ngay_trung_tuyen">
                        </div>
                        <div class="form-group col-md-6 mb-0">
                            <label for="sua-so-gplx" class="small mb-1">Số GPLX</label>
                            <input type="text" class="form-control form-control-sm" id="sua-so-gplx" name="so_gplx" maxlength="30">
                        </div>
                        <div class="form-group col-md-6 mb-0">
                            <label for="sua-ngay-cap" class="small mb-1">Ngày cấp GPLX</label>
                            <input type="date" class="form-control form-control-sm" id="sua-ngay-cap" name="ngay_cap_gplx">
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-navy">Lưu</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        ['#them_kh', '#loc_ma_kh'].forEach(function (id) {
            $(id).select2({
                theme: 'bootstrap4',
                placeholder: id === '#them_kh' ? 'Tìm khoá học...' : '—Tất cả—',
                allowClear: true,
                width: '100%',
                dropdownParent: $(id).closest('.form-group'),
                language: { noResults: function () { return 'Không tìm thấy khoá học'; } },
            });
        });

        $('#tim_hv_them').select2({
            theme: 'bootstrap4',
            placeholder: 'Nhập tên, CMT hoặc Mã ĐK...',
            allowClear: true,
            width: '100%',
            dropdownParent: $('#tim_hv_them').closest('.form-group'),
            minimumInputLength: 1,
            ajax: {
                url: @json(route('daotao.pdt.bc.cap-nhat-gplx.tim-hoc-vien')),
                dataType: 'json',
                delay: 300,
                data: function (params) {
                    return { q: params.term || '', page: params.page || 1 };
                },
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    return {
                        results: data.results || [],
                        pagination: { more: !!(data.pagination && data.pagination.more) },
                    };
                },
                cache: true,
            },
            language: {
                inputTooShort: function () { return 'Nhập ít nhất 1 ký tự'; },
                searching: function () { return 'Đang tìm...'; },
                noResults: function () { return 'Không tìm thấy học viên'; },
            },
        });
        $('#form-them-tim-hv').on('submit', function () {
            var v = $('#tim_hv_them').val();
            if (! v) {
                alert('Chọn học viên cần thêm.');
                return false;
            }
        });

        $('#khung-them-hv').on('show.bs.collapse', function () {
            $('#btn-mo-them-hv').text('▲ Ẩn').removeClass('btn-success').addClass('btn-outline-secondary');
        }).on('shown.bs.collapse', function () {
            if (! $('#them_kh').val()) {
                $('#them_kh').select2('open');
            }
        }).on('hide.bs.collapse', function () {
            $('#btn-mo-them-hv').text('＋ Thêm học viên').removeClass('btn-outline-secondary').addClass('btn-success');
        });

        var formThem = document.getElementById('form-them-hv');
        if (formThem) {
            var demChon = function () {
                var n = formThem.querySelectorAll('.chon-hv:checked').length;
                document.getElementById('so-chon').textContent = String(n);
                document.getElementById('btn-them-hv').disabled = n === 0;
            };
            formThem.addEventListener('change', demChon);
            formThem.querySelectorAll('[data-chon]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var bat = btn.getAttribute('data-chon') === '1';
                    formThem.querySelectorAll('.chon-hv').forEach(function (c) { c.checked = bat; });
                    demChon();
                });
            });
        }

        var ticks = function () { return document.querySelectorAll('.tick-dong:checked'); };
        var demTick = function () { document.getElementById('so-tick').textContent = String(ticks().length); };
        var tatCa = document.getElementById('tick-tat-ca');
        if (tatCa) {
            tatCa.addEventListener('change', function () {
                document.querySelectorAll('.tick-dong').forEach(function (c) { c.checked = tatCa.checked; });
                demTick();
            });
        }
        document.addEventListener('change', function (e) {
            if (e.target.classList.contains('tick-dong')) {
                demTick();
            }
        });
        document.querySelectorAll('.btn-hang-loat').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var n = ticks().length;
                if (! n) {
                    alert('Tick chọn học viên trước.');
                    return;
                }
                if (! confirm(btn.textContent.trim() + ' cho ' + n + ' học viên?')) {
                    return;
                }
                document.getElementById('hanh_dong').value = btn.getAttribute('data-hanh-dong');
                document.getElementById('form-hang-loat').submit();
            });
        });

        $(document).on('click', '.btn-sua', function () {
            var d = this.dataset;
            var form = document.getElementById('form-sua-gplx');
            form.action = d.url;
            document.getElementById('sua-ten').textContent = d.ten;
            document.getElementById('sua-cap-nhat').checked = d.capNhat === '1';
            document.getElementById('sua-so-gplx').value = d.soGplx || '';
            document.getElementById('sua-ngay-cap').value = d.ngayCap || '';
            document.getElementById('sua-noi-hk').value = d.noiHk || '';
            document.getElementById('sua-da-co-gplx').value = d.daCoGplx || '';
            document.getElementById('sua-ngay-tt').value = d.ngayTt || '';
            var coBan = [];
            if (d.ngaySinh) { coBan.push('Ngày sinh: ' + d.ngaySinh); }
            if (d.soCmnd) { coBan.push('CMND: ' + d.soCmnd); }
            document.getElementById('sua-pm-co-ban').textContent = coBan.join(' · ');
            var pm = [];
            if (d.pmHk) { pm.push('HKTT: ' + d.pmHk); }
            if (d.pmDaCo) { pm.push('Đã có GPLX: ' + d.pmDaCo); }
            if (d.pmSo) { pm.push('Số GPLX: ' + d.pmSo); }
            if (d.pmNgayCap) { pm.push('Ngày cấp: ' + d.pmNgayCap); }
            if (d.pmNgayTt) { pm.push('Ngày TT: ' + d.pmNgayTt); }
            document.getElementById('sua-pm-gplx').textContent = pm.length
                ? ('PMGPLX (tham chiếu) — ' + pm.join(' · '))
                : 'PMGPLX — chưa có dữ liệu tương ứng cột Excel.';
            $('#modalSuaGplx').modal('show');
        });
    })();
</script>
@endpush
