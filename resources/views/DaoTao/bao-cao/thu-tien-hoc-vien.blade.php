@extends('PMGPLX.layouts.quan-ly')

@section('title', 'Quản lý thu tiền học viên')

@php
    use App\Models\DaoTao\ThuTienHocVien;
    use App\Support\PMGPLX\NgayVn;

    $khoan = array_keys(ThuTienHocVien::PHI);
    $tien = fn (int $v): string => number_format($v, 0, ',', '.');
@endphp

@push('styles')
<style>
    .thu-tien-table td, .thu-tien-table th { vertical-align: middle; white-space: nowrap; }
    .thu-tien-table .so-tien { text-align: right; font-variant-numeric: tabular-nums; }
    .thu-tien-table .so-tien.am { color: #dc3545; font-weight: 600; }
    .thu-tien-table .so-tien.khong { color: #adb5bd; }
    .thu-tien-table .kq { display: block; font-size: .7rem; line-height: 1.1; }
    .thu-tien-table tfoot td { font-weight: 600; background: #f1f3f5; }
    .thu-tien-table .col-ghi-chu { white-space: normal; width: 14rem; min-width: 14rem; max-width: 14rem; overflow-wrap: anywhere; font-size: .85rem; }
    .thu-tien-table tbody tr.selected { background-color: #dcf2e1 !important; color: #212529; }
    .thu-tien-table tbody tr.selected td { box-shadow: inset 0 1px 0 #b7e1c1, inset 0 -1px 0 #b7e1c1; }
    #bang-ung-vien-khung { max-height: 22rem; overflow-y: auto; }
    #modalSoCai .o-so-du { border: 1px solid #dee2e6; border-radius: .25rem; padding: .35rem .5rem; text-align: right; }
    #modalSoCai .o-so-du .nhan { display: block; text-align: left; font-size: .75rem; color: #6c757d; }
    #modalSoCai .o-so-du .so { font-weight: 600; font-variant-numeric: tabular-nums; }
    #modalSoCai .o-so-du.am { border-color: #f5c2c7; background: #fff5f5; }
    #modalSoCai .o-so-du.am .so { color: #dc3545; }
    #modalSoCai .bang-gd td { vertical-align: middle; font-size: .85rem; }
    #modalSoCai .bang-gd .tien-thu { color: #198754; font-weight: 600; text-align: right; white-space: nowrap; }
    #modalSoCai .bang-gd .tien-tru { color: #dc3545; font-weight: 600; text-align: right; white-space: nowrap; }
    #modalSoCai .bang-gd-khung { max-height: 18rem; overflow-y: auto; }
    #bang-ung-vien tr.da-co td { color: #adb5bd; }
</style>
@endpush

@section('content')
    @php
        use App\Http\Controllers\DaoTao\ThuTienHocVienController as ThuTienCtrl;
        $tab = $tab ?? ($filters['tab'] ?? ThuTienCtrl::TAB_DANG_XU_LY);
        $laDaXuLy = $tab === ThuTienCtrl::TAB_DA_XU_LY;
        $moThemHv = ! $laDaXuLy && ($themKh !== '' || request()->boolean('mo_them'));
        $queryGiuLoc = array_merge(request()->except(['page', 'them_kh']), ['tab' => $tab]);
        $urlTabDang = route('daotao.pdt.bc.thu-tien-hoc-vien', array_merge($queryGiuLoc, ['tab' => ThuTienCtrl::TAB_DANG_XU_LY]));
        $urlTabDa = route('daotao.pdt.bc.thu-tien-hoc-vien', array_merge($queryGiuLoc, ['tab' => ThuTienCtrl::TAB_DA_XU_LY]));
        // Ẩn danh sách học viên của khoá: bỏ them_kh, giữ bộ lọc, accordion vẫn mở để chọn khoá khác.
        $urlBoChonKhoa = route('daotao.pdt.bc.thu-tien-hoc-vien', array_merge(request()->except(['them_kh', 'page']), ['mo_them' => 1, 'tab' => ThuTienCtrl::TAB_DANG_XU_LY]));
    @endphp

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link {{ ! $laDaXuLy ? 'active font-weight-bold' : '' }}" href="{{ $urlTabDang }}">Danh sách thu tiền học viên</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $laDaXuLy ? 'active font-weight-bold' : '' }}" href="{{ $urlTabDa }}">Danh sách đã xử lý</a>
        </li>
    </ul>

    @if (! $laDaXuLy)
    <div class="card card-panel">
        <div class="card-header d-flex align-items-center">
            <span class="mr-auto">Thêm học viên vào danh sách thu tiền</span>
            <button type="button" class="btn btn-sm {{ $moThemHv ? 'btn-outline-secondary' : 'btn-success' }}" id="btn-mo-them-hv"
                    data-toggle="collapse" data-target="#khung-them-hv" aria-expanded="{{ $moThemHv ? 'true' : 'false' }}" aria-controls="khung-them-hv">
                {{ $moThemHv ? '▲ Ẩn' : '＋ Thêm học viên' }}
            </button>
        </div>
        <div class="collapse {{ $moThemHv ? 'show' : '' }}" id="khung-them-hv">
        <div class="card-body">
            <form method="GET" action="{{ route('daotao.pdt.bc.thu-tien-hoc-vien') }}" class="form-row align-items-end">
                <input type="hidden" name="tab" value="{{ ThuTienCtrl::TAB_DANG_XU_LY }}">
                @foreach (array_filter(['tu_khoa' => $filters['tu_khoa'], 'ma_kh' => $filters['ma_kh'], 'tot_nghiep' => $filters['tot_nghiep'], 'thu_tien' => $filters['thu_tien'], 'can_thu' => $filters['can_thu']]) as $k => $v)
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
                <form method="POST" action="{{ route('daotao.pdt.bc.thu-tien-hoc-vien.store') }}" class="form-row align-items-end" id="form-them-tim-hv">
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
                <form method="POST" action="{{ route('daotao.pdt.bc.thu-tien-hoc-vien.store') }}" class="mt-3" id="form-them-hv">
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
    @endif

    <div class="card card-panel">
        <div class="card-header">Tìm kiếm{{ $laDaXuLy ? ' — đã xử lý' : '' }}</div>
        <div class="card-body">
            <form method="GET" action="{{ route('daotao.pdt.bc.thu-tien-hoc-vien') }}" class="form-row align-items-end">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="form-group col-md-2 mb-0">
                    <label>Mã ĐK|Họ tên|CMT|Số hồ sơ</label>
                    <input type="text" class="form-control form-control-sm" name="tu_khoa" value="{{ $filters['tu_khoa'] }}" placeholder="Nhập từ khoá">
                </div>
                <div class="form-group col-md-2 mb-0">
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
                    <label title="Thủ công hoặc KetLuanCSDT PMGPLX">Tốt nghiệp</label>
                    <select name="tot_nghiep" class="form-control form-control-sm">
                        <option value="">—Tất cả—</option>
                        <option value="1" @selected($filters['tot_nghiep'] === '1')>Đã tốt nghiệp</option>
                        <option value="0" @selected($filters['tot_nghiep'] === '0')>Chưa tốt nghiệp</option>
                    </select>
                </div>
                <div class="form-group col-md-2 mb-0">
                    <label>Thu tiền</label>
                    <select name="thu_tien" class="form-control form-control-sm">
                        <option value="">—Tất cả—</option>
                        <option value="1" @selected($filters['thu_tien'] === '1')>Đã thu</option>
                        <option value="0" @selected($filters['thu_tien'] === '0')>Chưa thu</option>
                    </select>
                </div>
                <div class="form-group col-md-2 mb-0">
                    <label title="Môn đang âm: rớt, phải nộp lại">Cần thu tiền</label>
                    <select name="can_thu" class="form-control form-control-sm {{ $filters['can_thu'] !== '' ? 'border-danger' : '' }}">
                        <option value="">—Tất cả—</option>
                        <option value="bat_ky" @selected($filters['can_thu'] === 'bat_ky')>Bất kỳ môn nào</option>
                        @foreach (ThuTienHocVien::COT_KET_QUA as $mon => $_)
                            <option value="{{ $mon }}" @selected($filters['can_thu'] === $mon)>{{ ThuTienHocVien::NHAN_MON[$mon] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2 mb-0">
                    <button type="submit" class="btn btn-sm btn-primary btn-block">Tìm kiếm</button>
                    <a href="{{ route('daotao.pdt.bc.thu-tien-hoc-vien', ['tab' => $tab]) }}" class="btn btn-sm btn-outline-secondary btn-block mt-1" title="Làm mới">↻</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-panel">
        <div class="card-header">{{ $laDaXuLy ? 'Danh sách đã xử lý' : 'Danh sách thu tiền học viên' }}</div>
        <div class="card-body">
            <form method="POST" action="{{ route('daotao.pdt.bc.thu-tien-hoc-vien.hang-loat') }}" id="form-hang-loat">
                @csrf
                <input type="hidden" name="hanh_dong" id="hanh_dong">
            </form>

            <div class="d-flex flex-wrap align-items-center mb-2">
                <div class="btn-group btn-group-sm mr-2 mb-2">
                    <button type="button" class="btn btn-success btn-hang-loat" data-hanh-dong="thu_tien">✔ Đã thu tiền</button>
                    <button type="button" class="btn btn-outline-success btn-hang-loat" data-hanh-dong="bo_thu_tien">Bỏ thu tiền</button>
                </div>
                <div class="btn-group btn-group-sm mr-2 mb-2">
                    <button type="button" class="btn btn-info btn-hang-loat" data-hanh-dong="tot_nghiep">✔ Đã tốt nghiệp</button>
                    <button type="button" class="btn btn-outline-info btn-hang-loat" data-hanh-dong="bo_tot_nghiep">Bỏ tốt nghiệp</button>
                </div>
                @if ($laDaXuLy)
                    <button type="button" class="btn btn-sm btn-outline-primary mr-2 mb-2 btn-hang-loat" data-hanh-dong="chuyen_dang_xu_ly">← Về danh sách thu tiền</button>
                @else
                    <button type="button" class="btn btn-sm btn-outline-secondary mr-2 mb-2 btn-hang-loat" data-hanh-dong="chuyen_da_xu_ly">→ Chuyển sang đã xử lý</button>
                @endif
                <button type="button" class="btn btn-sm btn-outline-danger mr-2 mb-2 btn-hang-loat" data-hanh-dong="xoa">✕ Xoá khỏi danh sách</button>
                <span class="small text-muted mb-2 mr-auto">Đã chọn <strong id="so-tick">0</strong></span>
                <a href="{{ route('daotao.pdt.bc.thu-tien-hoc-vien.export', array_filter(array_merge(request()->only(['tu_khoa', 'ma_kh', 'tot_nghiep', 'thu_tien', 'can_thu', 'tab']), ['tab' => $tab]), fn ($v) => $v !== null && $v !== '')) }}"
                   class="btn btn-sm btn-outline-success mr-3 mb-2"
                   title="Xuất toàn bộ {{ number_format($items->total()) }} học viên theo bộ lọc hiện tại (không phân trang)">⬇ Xuất Excel ({{ number_format($items->total()) }})</a>

                <div class="d-flex flex-wrap align-items-center mb-2">
                    <span class="mr-3">Tổng số bản ghi: <strong>{{ number_format($items->total()) }}</strong></span>
                    <form method="GET" action="{{ route('daotao.pdt.bc.thu-tien-hoc-vien') }}" class="form-inline mr-3">
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
                Số tiền mỗi cột = tổng thu/trừ trong sổ (nút <strong>$</strong> để thu thêm và xem lịch sử). Đã thu tiền thì cộng: Lý thuyết {{ $tien(ThuTienHocVien::PHI['ly_thuyet']) }} · Sa hình {{ $tien(ThuTienHocVien::PHI['sa_hinh']) }}
                · Đường {{ $tien(ThuTienHocVien::PHI['duong']) }} · Bìa cứng {{ $tien(ThuTienHocVien::PHI['bia']) }}.
                Có kết quả thi (đậu hoặc rớt) thì trừ phí môn đó (về 0); rớt thì ghi <span class="text-danger">Rớt (-phí)</span> = số phải thu lại, thu xong số tiền trở lại bằng phí.
            </p>

            <div class="table-responsive">
                <table class="table table-sm table-bordered table-striped table-hover table-data mb-0 thu-tien-table">
                    <thead>
                        <tr>
                            <th class="text-center"><input type="checkbox" id="tick-tat-ca" title="Chọn cả trang"></th>
                            <th>STT</th>
                            <th>Họ và tên / Mã ĐK</th>
                            <th>Số CMT / Ngày sinh</th>
                            <th>Tên khoá học / Hạng</th>
                            <th>Tốt nghiệp</th>
                            <th>Thu tiền</th>
                            <th class="text-right">Tiền LT</th>
                            <th class="text-right">Tiền Sa hình</th>
                            <th class="text-right">Tiền Đường</th>
                            <th class="text-right">Bìa</th>
                            <th class="text-right">Tổng</th>
                            <th class="col-ghi-chu">Ghi chú</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $i => $row)
                            @php
                                $hv = $hocVien[(string) $row->MaDK] ?? null;
                                $hoTen = $hv ? ($hv->HoVaTen ?: trim(($hv->HoDemNLX ?? '').' '.($hv->TenNLX ?? ''))) : '';
                            @endphp
                            <tr>
                                <td class="text-center"><input type="checkbox" class="tick-dong" name="ids[]" value="{{ $row->Id }}" form="form-hang-loat"></td>
                                <td>{{ $items->firstItem() + $i }}</td>
                                <td>
                                    @if ($hv)
                                        <div>{{ $hoTen }}</div>
                                    @else
                                        <div class="text-danger">Không tìm thấy bên PMGPLX</div>
                                    @endif
                                    <small class="text-muted">{{ $row->MaDK }}</small>
                                </td>
                                <td>
                                    <div>{{ $hv?->SoCMT }}</div>
                                    @if ($hv)
                                        <small class="text-muted">{{ NgayVn::format($hv->NgaySinh, 'yyyymmdd') }}</small>
                                    @endif
                                </td>
                                <td>
                                    <div title="{{ $row->MaKhoaHoc }}">{{ ($tenKhoa[(string) $row->MaKhoaHoc] ?? '') ?: $row->MaKhoaHoc }}</div>
                                    @if ($hv?->HangGPLX)
                                        <small class="text-muted">Hạng {{ $hv->HangGPLX }}</small>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $tnPm = ThuTienHocVien::daTotNghiepTuHoSo($hv);
                                        $tnHien = ThuTienHocVien::daTotNghiepHienThi($hv, (bool) $row->DaTotNghiep);
                                    @endphp
                                    @if ($tnHien)
                                        <span class="badge badge-info">Đã tốt nghiệp</span>
                                        @if ($tnPm && ! $row->DaTotNghiep)
                                            <span class="badge badge-light border" title="KetLuanCSDT = 1">PM</span>
                                        @elseif ($row->DaTotNghiep && ! $tnPm)
                                            <span class="badge badge-light border" title="Đánh dấu thủ công">Tay</span>
                                        @endif
                                    @else
                                        <span class="badge badge-light border">Chưa</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($row->DaThuTien)
                                        <span class="badge badge-success" title="Thu lúc {{ $row->NgayThuTien?->format('d/m/Y H:i') }}">Đã thu {{ $row->NgayThuTien?->format('d/m') }}</span>
                                    @else
                                        <span class="badge badge-light border">Chưa thu</span>
                                    @endif
                                </td>
                                @foreach ($khoan as $k)
                                    @php
                                        $v = $row->tien($k);
                                        $kq = isset(ThuTienHocVien::COT_KET_QUA[$k]) ? $row->{ThuTienHocVien::COT_KET_QUA[$k]} : null;
                                    @endphp
                                    <td class="so-tien {{ $v < 0 ? 'am' : ($v === 0 ? 'khong' : '') }}">
                                        {{ $tien($v) }}
                                        @if ($kq !== null)
                                            <span class="kq {{ $kq === ThuTienHocVien::DAU ? 'text-success' : 'text-danger' }}">{{ $row->nhanKetQua($k) }}</span>
                                        @elseif ($v < 0)
                                            <span class="kq text-danger">Phải thu {{ $tien(-$v) }}</span>
                                        @endif
                                    </td>
                                @endforeach
                                @php $tongDong = $row->tongTien(); @endphp
                                <td class="so-tien font-weight-bold {{ $tongDong < 0 ? 'am' : '' }}">{{ $tien($tongDong) }}</td>
                                <td class="col-ghi-chu">{{ $row->GhiChu }}</td>
                                <td class="text-nowrap">
                                    <button type="button" class="btn btn-sm btn-outline-success py-0 btn-so-cai"
                                            data-url="{{ route('daotao.pdt.bc.thu-tien-hoc-vien.giao-dich', $row->Id) }}"
                                            title="Thu tiền / lịch sử thu trừ">$</button>
                                    <button type="button" class="btn btn-sm btn-outline-primary py-0 btn-sua"
                                            data-url="{{ route('daotao.pdt.bc.thu-tien-hoc-vien.update', $row->Id) }}"
                                            data-url-gd="{{ route('daotao.pdt.bc.thu-tien-hoc-vien.giao-dich', $row->Id) }}"
                                            data-url-lan-thi="{{ route('daotao.pdt.bc.thu-tien-hoc-vien.lan-thi.store', $row->Id) }}"
                                            data-ten="{{ $row->MaDK }} — {{ $hoTen }}"
                                            data-tot-nghiep="{{ (int) ThuTienHocVien::daTotNghiepHienThi($hv, (bool) $row->DaTotNghiep) }}"
                                            data-da-xu-ly="{{ (int) $row->DaXuLy }}"
                                            data-thu-tien="{{ (int) $row->DaThuTien }}"
                                            data-ghi-chu="{{ $row->GhiChu }}"
                                            title="Sửa">✎</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="14" class="text-center py-4">
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
                                <td colspan="7" class="text-right">Tổng theo bộ lọc ({{ number_format($items->total()) }} học viên)</td>
                                @foreach ($khoan as $k)
                                    <td class="so-tien {{ $tong[$k] < 0 ? 'am' : '' }}">{{ $tien($tong[$k]) }}</td>
                                @endforeach
                                <td class="so-tien {{ $tong['tong'] < 0 ? 'am' : '' }}">{{ $tien($tong['tong']) }}</td>
                                <td colspan="2"></td>
                            </tr>
                            <tr>
                                <td colspan="7" class="text-right text-danger">Phải thu (rớt chưa nộp lại phí thi lại)</td>
                                @foreach ($khoan as $k)
                                    <td class="so-tien {{ $tongPhaiThu[$k] > 0 ? 'am' : 'khong' }}">{{ $tongPhaiThu[$k] > 0 ? $tien(-$tongPhaiThu[$k]) : '0' }}</td>
                                @endforeach
                                <td class="so-tien {{ $tongPhaiThu['tong'] > 0 ? 'am' : 'khong' }}">{{ $tongPhaiThu['tong'] > 0 ? $tien(-$tongPhaiThu['tong']) : '0' }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalSuaThuTien" tabindex="-1" role="dialog" aria-labelledby="modalSuaThuTienLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form method="POST" class="modal-content" id="form-sua-thu-tien">
                @csrf
                @method('PUT')
                <div class="modal-header py-2">
                    <h5 class="modal-title" id="modalSuaThuTienLabel">Cập nhật học viên</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="font-weight-bold mb-3" id="sua-ten"></p>
                    <div class="custom-control custom-checkbox mb-2">
                        <input type="hidden" name="da_tot_nghiep" value="0">
                        <input type="checkbox" class="custom-control-input" id="sua-tot-nghiep" name="da_tot_nghiep" value="1">
                        <label class="custom-control-label" for="sua-tot-nghiep">Đã tốt nghiệp (thủ công)</label>
                    </div>
                    <p class="small text-muted mb-2"><code>KetLuanCSDT = 1</code> trên PMGPLX sẽ tự bật khi mở danh sách.</p>
                    <div class="custom-control custom-checkbox mb-2">
                        <input type="hidden" name="da_xu_ly" value="0">
                        <input type="checkbox" class="custom-control-input" id="sua-da-xu-ly" name="da_xu_ly" value="1">
                        <label class="custom-control-label" for="sua-da-xu-ly">Đã xử lý (tab riêng)</label>
                    </div>
                    <p class="small text-muted mb-2">Tự chuyển sang tab «Đã xử lý» khi <strong>Lý thuyết + Sa hình + Đường</strong> đều <strong>Đậu</strong>.</p>
                    <div class="custom-control custom-checkbox mb-3">
                        <input type="hidden" name="da_thu_tien" value="0">
                        <input type="checkbox" class="custom-control-input" id="sua-thu-tien" name="da_thu_tien" value="1">
                        <label class="custom-control-label" for="sua-thu-tien">Đã thu tiền</label>
                    </div>
                    <div class="border rounded p-2 mb-3">
                        <div class="d-flex align-items-center mb-2">
                            <strong class="mr-auto">Kết quả thi</strong>
                            <label for="sua-ngay-thi" class="small mb-0 mr-2">Ngày thi</label>
                            <input type="date" class="form-control form-control-sm" id="sua-ngay-thi" style="width: 10rem;">
                        </div>
                        <div id="sua-kq-thong-bao" class="alert py-1 px-2 small d-none mb-2"></div>
                        <table class="table table-sm table-bordered mb-1" id="sua-bang-kq">
                            <thead class="thead-light">
                                <tr><th style="width: 6rem;">Môn</th><th>Các lần thi</th><th style="width: 9.5rem;"></th></tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                        <p class="small text-muted mb-0">
                            Mỗi lần thi trừ phí môn đó. Rớt thì thu lại phí ở nút <strong>$</strong> rồi ghi lần thi tiếp theo.
                            Ghi nhầm thì bấm ↶ để huỷ lần cuối (hoàn phí).
                        </p>
                    </div>
                    <div class="form-group mb-0">
                        <label for="sua-ghi-chu" class="small mb-1">Ghi chú</label>
                        <input type="text" class="form-control form-control-sm" id="sua-ghi-chu" name="ghi_chu" maxlength="500">
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <span class="small text-muted mr-auto">Lưu: tốt nghiệp (tay), thu tiền, ghi chú. Kết quả thi ghi ngay khi bấm.</span>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-navy">Lưu</button>
                </div>
            </form>
        </div>
    </div>
    <div class="modal fade" id="modalSoCai" tabindex="-1" role="dialog" aria-labelledby="modalSoCaiLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title" id="modalSoCaiLabel">Thu tiền học viên</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2"><strong id="sc-ten"></strong> <small class="text-muted" id="sc-ma"></small></p>
                    <div class="form-row mb-3" id="sc-so-du">
                        @foreach (ThuTienHocVien::NHAN_MON as $k => $nhan)
                            <div class="col"><div class="o-so-du" data-khoan="{{ $k }}"><span class="nhan">{{ $nhan }}</span><span class="so">0</span><span class="phai-thu small text-danger d-block"></span></div></div>
                        @endforeach
                        <div class="col"><div class="o-so-du" data-khoan="tong"><span class="nhan">Tổng</span><span class="so">0</span><span class="phai-thu small text-danger d-block"></span></div></div>
                    </div>

                    <div id="sc-thong-bao" class="alert py-2 d-none"></div>

                    <form id="form-so-cai" class="form-row align-items-end border rounded px-2 pt-2 mb-3 mx-0" autocomplete="off">
                        <div class="form-group col-md-3 mb-2">
                            <label class="small mb-1" for="sc-khoan">Khoản</label>
                            <select class="form-control form-control-sm" id="sc-khoan" name="khoan">
                                @foreach (ThuTienHocVien::NHAN_MON as $k => $nhan)
                                    <option value="{{ $k }}">{{ $nhan }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-2 mb-2">
                            <label class="small mb-1" for="sc-loai">Loại</label>
                            <select class="form-control form-control-sm" id="sc-loai" name="loai">
                                <option value="thu">Thu</option>
                                <option value="tru">Trừ</option>
                            </select>
                        </div>
                        <div class="form-group col-md-2 mb-2">
                            <label class="small mb-1" for="sc-so-tien">Số tiền</label>
                            <input type="number" class="form-control form-control-sm" id="sc-so-tien" name="so_tien" min="1000" step="1000" required>
                        </div>
                        <div class="form-group col-md-3 mb-2">
                            <label class="small mb-1" for="sc-noi-dung">Nội dung</label>
                            <input type="text" class="form-control form-control-sm" id="sc-noi-dung" name="noi_dung" maxlength="255" placeholder="vd Nộp lại tiền thi">
                        </div>
                        <div class="form-group col-md-2 mb-2">
                            <button type="submit" class="btn btn-sm btn-success btn-block" id="sc-ghi">Ghi</button>
                        </div>
                    </form>

                    <h6 class="mb-2">Lịch sử thu / trừ</h6>
                    <div class="bang-gd-khung">
                        <table class="table table-sm table-bordered mb-0 bang-gd">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 8.5rem;">Thời gian</th>
                                    <th style="width: 6rem;">Khoản</th>
                                    <th class="text-right" style="width: 7rem;">Số tiền</th>
                                    <th>Nội dung</th>
                                    <th style="width: 8rem;">Người ghi</th>
                                    <th style="width: 2.5rem;"></th>
                                </tr>
                            </thead>
                            <tbody id="sc-lich-su"></tbody>
                        </table>
                    </div>
                    <p class="small text-muted mt-2 mb-0">
                        Dòng "tự động" sinh khi đổi trạng thái thu tiền / kết quả thi (nút ✎). Dòng nhập tay xoá được nếu ghi nhầm.
                        Rớt → Đậu là thi lại đậu (trừ phí thi lại đã thu). Thi lại vẫn rớt thì ghi <em>Trừ</em> phí môn đó ở đây.
                    </p>
                </div>
            </div>
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
                url: @json(route('daotao.pdt.bc.thu-tien-hoc-vien.tim-hoc-vien')),
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

        // Accordion "Thêm học viên": đổi chữ nút theo trạng thái mở / đóng.
        $('#khung-them-hv').on('show.bs.collapse', function () {
            $('#btn-mo-them-hv').text('▲ Ẩn').removeClass('btn-success').addClass('btn-outline-secondary');
        }).on('shown.bs.collapse', function () {
            if (! $('#them_kh').val()) {
                $('#them_kh').select2('open');
            }
        }).on('hide.bs.collapse', function () {
            $('#btn-mo-them-hv').text('＋ Thêm học viên').removeClass('btn-outline-secondary').addClass('btn-success');
        });

        // Thêm học viên: đếm số đang chọn.
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

        // Thao tác hàng loạt.
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

        // Sổ thu / trừ tiền của một học viên.
        var sc = { url: null, data: null, daDoi: false };
        var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        var dinhDang = function (v) { return (v < 0 ? '-' : '') + Math.abs(v).toLocaleString('vi-VN'); };
        var thongBaoSc = function (loai, text) {
            var el = document.getElementById('sc-thong-bao');
            el.className = 'alert py-2 alert-' + loai + (text ? '' : ' d-none');
            el.textContent = text || '';
        };
        var goiApi = function (method, url, body) {
            return fetch(url, {
                method: method,
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: body ? JSON.stringify(body) : undefined,
            }).then(function (res) {
                return res.json().catch(function () { return {}; }).then(function (json) {
                    if (! res.ok) {
                        var msg = json.message || ('Lỗi ' + res.status);
                        if (json.errors) {
                            msg = Object.keys(json.errors).map(function (k) { return json.errors[k].join(' '); }).join(' ');
                        }
                        throw new Error(msg);
                    }
                    return json;
                });
            });
        };
        // Gợi ý số tiền: khoản còn phải thu thì điền đúng số đó.
        var goiYSoTien = function () {
            if (! sc.data) {
                return;
            }
            var can = sc.data.phai_thu[document.getElementById('sc-khoan').value] || 0;
            if (document.getElementById('sc-loai').value === 'thu' && can > 0) {
                document.getElementById('sc-so-tien').value = can;
            }
        };
        var veSoCai = function (d) {
            sc.data = d;
            document.getElementById('sc-ten').textContent = d.ho_ten || '(không tìm thấy bên PMGPLX)';
            document.getElementById('sc-ma').textContent = d.ma_dk + (d.ma_kh ? ' · ' + d.ma_kh : '');
            document.querySelectorAll('#sc-so-du .o-so-du').forEach(function (o) {
                var k = o.getAttribute('data-khoan');
                var v = k === 'tong' ? d.tong : (d.so_du[k] || 0);
                var can = k === 'tong' ? d.tong_phai_thu : (d.phai_thu[k] || 0);
                o.querySelector('.so').textContent = dinhDang(v);
                o.querySelector('.phai-thu').textContent = can > 0 ? 'Phải thu ' + dinhDang(can) : '';
                o.classList.toggle('am', v < 0 || can > 0);
            });
            var tb = document.getElementById('sc-lich-su');
            tb.innerHTML = '';
            if (! d.giao_dich.length) {
                tb.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Chưa có giao dịch.</td></tr>';
            }
            d.giao_dich.forEach(function (g) {
                var tr = document.createElement('tr');
                var o = function (text, cls) {
                    var td = document.createElement('td');
                    td.textContent = text;
                    if (cls) { td.className = cls; }
                    tr.appendChild(td);
                    return td;
                };
                o(g.ngay);
                o(g.khoan);
                o((g.so_tien > 0 ? '+' : '') + dinhDang(g.so_tien), g.so_tien > 0 ? 'tien-thu' : 'tien-tru');
                var nd = o(g.noi_dung);
                if (! g.thu_cong) {
                    var badge = document.createElement('span');
                    badge.className = 'badge badge-light border ml-1';
                    badge.textContent = 'tự động';
                    nd.appendChild(badge);
                }
                o(g.nguoi);
                var tdXoa = o('', 'text-center');
                if (g.thu_cong) {
                    var b = document.createElement('button');
                    b.type = 'button';
                    b.className = 'btn btn-sm btn-link text-danger p-0 sc-xoa';
                    b.title = 'Xoá giao dịch nhập nhầm';
                    b.setAttribute('data-id', g.id);
                    b.textContent = '✕';
                    tdXoa.appendChild(b);
                }
                tb.appendChild(tr);
            });
            goiYSoTien();
        };

        $(document).on('click', '.btn-so-cai', function () {
            sc.url = this.dataset.url;
            sc.daDoi = false;
            thongBaoSc('', '');
            document.getElementById('form-so-cai').reset();
            goiApi('GET', sc.url).then(function (d) {
                veSoCai(d);
                $('#modalSoCai').modal('show');
            }).catch(function (e) { alert(e.message); });
        });
        document.getElementById('sc-khoan').addEventListener('change', goiYSoTien);
        document.getElementById('sc-loai').addEventListener('change', goiYSoTien);

        document.getElementById('form-so-cai').addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = document.getElementById('sc-ghi');
            btn.disabled = true;
            goiApi('POST', sc.url, {
                khoan: document.getElementById('sc-khoan').value,
                loai: document.getElementById('sc-loai').value,
                so_tien: parseInt(document.getElementById('sc-so-tien').value, 10),
                noi_dung: document.getElementById('sc-noi-dung').value,
            }).then(function (d) {
                sc.daDoi = true;
                document.getElementById('sc-so-tien').value = '';
                document.getElementById('sc-noi-dung').value = '';
                veSoCai(d);
                thongBaoSc('success', d.message);
            }).catch(function (err) {
                thongBaoSc('danger', err.message);
            }).then(function () { btn.disabled = false; });
        });

        document.getElementById('sc-lich-su').addEventListener('click', function (e) {
            var b = e.target.closest('.sc-xoa');
            if (! b || ! confirm('Xoá giao dịch này?')) {
                return;
            }
            goiApi('DELETE', sc.url + '/' + b.getAttribute('data-id')).then(function (d) {
                sc.daDoi = true;
                veSoCai(d);
                thongBaoSc('success', d.message);
            }).catch(function (err) { thongBaoSc('danger', err.message); });
        });

        // Đóng modal sau khi thu/trừ thì tải lại để danh sách và tổng cập nhật.
        $('#modalSoCai').on('hidden.bs.modal', function () {
            if (sc.daDoi) {
                location.reload();
            }
        });

        // Sửa từng học viên + kết quả thi nhiều lần.
        var sua = { urlGd: null, urlLanThi: null, daDoi: false, daGui: false };
        var thongBaoKq = function (loai, text) {
            var el = document.getElementById('sua-kq-thong-bao');
            el.className = 'alert py-1 px-2 small mb-2 alert-' + loai + (text ? '' : ' d-none');
            el.textContent = text || '';
        };
        var veKetQua = function (d) {
            var tb = document.querySelector('#sua-bang-kq tbody');
            tb.innerHTML = '';
            Object.keys(d.ket_qua).forEach(function (k) {
                var m = d.ket_qua[k];
                var tr = document.createElement('tr');
                var tdMon = document.createElement('td');
                tdMon.textContent = m.mon;
                var tdLan = document.createElement('td');
                if (! m.lan.length) {
                    tdLan.innerHTML = '<span class="text-muted">Chưa thi</span>';
                }
                m.lan.forEach(function (l) {
                    var b = document.createElement('span');
                    b.className = 'badge mr-1 mb-1 ' + (l.kq === 'dau' ? 'badge-success' : 'badge-danger');
                    b.textContent = 'Lần ' + l.lan + ': ' + (l.kq === 'dau' ? 'Đậu' : 'Rớt') + (l.ngay ? ' · ' + l.ngay.slice(0, 5) : '');
                    tdLan.appendChild(b);
                });
                if (m.hien_tai === 'rot' && d.phai_thu[k] > 0) {
                    var can = document.createElement('div');
                    can.className = 'small text-danger';
                    can.textContent = 'Phải thu ' + dinhDang(d.phai_thu[k]) + ' trước khi thi lại';
                    tdLan.appendChild(can);
                }
                var tdNut = document.createElement('td');
                tdNut.className = 'text-nowrap';
                var nut = function (text, cls, title, fn) {
                    var b = document.createElement('button');
                    b.type = 'button';
                    b.className = 'btn btn-sm py-0 px-1 mr-1 ' + cls;
                    b.textContent = text;
                    b.title = title;
                    b.addEventListener('click', fn);
                    tdNut.appendChild(b);
                };
                if (m.hien_tai !== 'dau') {
                    var lanToi = m.lan.length + 1;
                    nut('＋ Đậu', 'btn-outline-success', 'Ghi lần ' + lanToi + ' đậu', function () { ghiLanThi(k, 'dau'); });
                    nut('＋ Rớt', 'btn-outline-danger', 'Ghi lần ' + lanToi + ' rớt', function () { ghiLanThi(k, 'rot'); });
                }
                if (m.lan.length) {
                    nut('↶', 'btn-outline-secondary', 'Huỷ lần thi cuối (hoàn phí)', function () { huyLanThi(k, m); });
                }
                tr.appendChild(tdMon);
                tr.appendChild(tdLan);
                tr.appendChild(tdNut);
                tb.appendChild(tr);
            });
        };
        var ghiLanThi = function (khoan, kq) {
            goiApi('POST', sua.urlLanThi, { khoan: khoan, ket_qua: kq, ngay_thi: document.getElementById('sua-ngay-thi').value || null })
                .then(function (d) { sua.daDoi = true; veKetQua(d); thongBaoKq('success', d.message); })
                .catch(function (e) { thongBaoKq('danger', e.message); });
        };
        var huyLanThi = function (khoan, m) {
            var cuoi = m.lan[m.lan.length - 1];
            if (! confirm('Huỷ ' + m.mon + ' lần ' + cuoi.lan + ' (' + (cuoi.kq === 'dau' ? 'Đậu' : 'Rớt') + ')? Phí thi sẽ được hoàn.')) {
                return;
            }
            goiApi('DELETE', sua.urlLanThi + '/' + khoan)
                .then(function (d) { sua.daDoi = true; veKetQua(d); thongBaoKq('success', d.message); })
                .catch(function (e) { thongBaoKq('danger', e.message); });
        };

        $(document).on('click', '.btn-sua', function () {
            var d = this.dataset;
            var form = document.getElementById('form-sua-thu-tien');
            form.action = d.url;
            sua.urlGd = d.urlGd;
            sua.urlLanThi = d.urlLanThi;
            sua.daDoi = false;
            sua.daGui = false;
            thongBaoKq('', '');
            document.getElementById('sua-ten').textContent = d.ten;
            document.getElementById('sua-tot-nghiep').checked = d.totNghiep === '1';
            document.getElementById('sua-da-xu-ly').checked = d.daXuLy === '1';
            document.getElementById('sua-thu-tien').checked = d.thuTien === '1';
            document.getElementById('sua-ghi-chu').value = d.ghiChu || '';
            document.getElementById('sua-ngay-thi').value = new Date().toISOString().slice(0, 10);
            goiApi('GET', sua.urlGd).then(function (gd) {
                veKetQua(gd);
                $('#modalSuaThuTien').modal('show');
            }).catch(function (e) { alert(e.message); });
        });
        document.getElementById('form-sua-thu-tien').addEventListener('submit', function () { sua.daGui = true; });
        $('#modalSuaThuTien').on('hidden.bs.modal', function () {
            if (sua.daDoi && ! sua.daGui) {
                location.reload();
            }
        });
    })();
</script>
@endpush
