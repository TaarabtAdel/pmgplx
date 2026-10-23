<?php

namespace App\Http\Controllers\DaoTao;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\ThuTienHocVien;
use App\Models\DaoTao\ThuTienHocVienGiaoDich;
use App\Models\PMGPLX\KhoaHoc;
use App\Models\PMGPLX\NguoiLX;
use App\Support\DaoTao\ThuTienHocVienExcelExporter;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Phòng Đào Tạo → Báo cáo → Quản lý thu tiền học viên.
 * Bảng ThuTienHocVien (MANHLINH) chỉ giữ MaDK + trạng thái; thông tin học viên lấy từ NguoiLX / NguoiLX_HoSo (PMGPLX)
 * theo MaDK — hai DB khác nhau nên không JOIN mà tra theo danh sách MaDK.
 */
class ThuTienHocVienController extends Controller
{
    private const SO_DONG_TRANG = [20, 50, 100, 200];

    public function index(Request $request): View
    {
        $filters = self::boLoc($request);
        $perPage = $filters['per_page'];

        $items = $this->locDanhSach($filters)
            ->kemSoDu()
            ->with('lanThis')
            ->orderByDesc('MaKhoaHoc')
            ->orderBy('Id')
            ->paginate($perPage)
            ->withQueryString();

        $themKh = trim((string) $request->input('them_kh', ''));
        $khoaHocs = KhoaHoc::query()->orderByDesc('TenKH')->get(['MaKH', 'TenKH']);
        $maTrongDanhSach = ThuTienHocVien::query()->whereNotNull('MaKhoaHoc')->distinct()->pluck('MaKhoaHoc')
            ->map(fn ($m) => (string) $m)->flip();

        return view('DaoTao.bao-cao.thu-tien-hoc-vien', [
            'items' => $items,
            'hocVien' => self::thongTinHocVien($items->pluck('MaDK')),
            'tong' => ThuTienHocVien::tongTheoKhoan($this->locDanhSach($filters)),
            'tongPhaiThu' => ThuTienHocVien::tongPhaiThuTheoKhoan($this->locDanhSach($filters)),
            'filters' => $filters,
            'khoaHocs' => $khoaHocs,
            'tenKhoa' => $khoaHocs->mapWithKeys(fn ($kh) => [(string) $kh->MaKH => (string) $kh->TenKH]),
            // Lọc theo khoá: chỉ các khoá đã có học viên trong danh sách, hiện "Tên khoá (Mã khoá)" như ô Chọn khoá.
            'khoaTrongDanhSach' => $khoaHocs->filter(fn ($kh) => $maTrongDanhSach->has((string) $kh->MaKH))
                ->concat($maTrongDanhSach->keys()
                    ->diff($khoaHocs->pluck('MaKH')->map(fn ($m) => (string) $m))
                    ->map(fn (string $ma) => (object) ['MaKH' => $ma, 'TenKH' => null]))
                ->values(),
            'themKh' => $themKh,
            'ungVien' => $themKh !== '' ? self::hocVienCuaKhoa($themKh) : null,
        ]);
    }

    /** Xuất Excel toàn bộ danh sách theo bộ lọc hiện tại (không phân trang). */
    public function export(Request $request): StreamedResponse
    {
        $filters = self::boLoc($request);
        $rows = $this->locDanhSach($filters)->kemSoDu()->with('lanThis')->orderByDesc('MaKhoaHoc')->orderBy('Id')->get();

        $moTa = [];
        if ($filters['ma_kh'] !== '') {
            $moTa[] = 'Khoá '.$filters['ma_kh'];
        }
        if ($filters['tu_khoa'] !== '') {
            $moTa[] = 'Từ khoá "'.$filters['tu_khoa'].'"';
        }
        if (in_array($filters['tot_nghiep'], ['0', '1'], true)) {
            $moTa[] = $filters['tot_nghiep'] === '1' ? 'Đã tốt nghiệp' : 'Chưa tốt nghiệp';
        }
        if (in_array($filters['thu_tien'], ['0', '1'], true)) {
            $moTa[] = $filters['thu_tien'] === '1' ? 'Đã thu tiền' : 'Chưa thu tiền';
        }
        if ($filters['can_thu'] === 'bat_ky') {
            $moTa[] = 'Cần thu tiền: bất kỳ môn nào';
        } elseif (isset(ThuTienHocVien::NHAN_MON[$filters['can_thu']])) {
            $moTa[] = 'Cần thu tiền: '.ThuTienHocVien::NHAN_MON[$filters['can_thu']];
        }

        $tenFile = 'thu-tien-hoc-vien'
            .($filters['ma_kh'] !== '' ? '-'.(preg_replace('/[^A-Za-z0-9_-]+/', '-', $filters['ma_kh']) ?: 'khoa') : '')
            .'-'.now()->format('Ymd-His').'.xlsx';

        return ThuTienHocVienExcelExporter::download($rows, self::thongTinHocVien($rows->pluck('MaDK')), $moTa, $tenFile);
    }

    /** @return array{tu_khoa: string, ma_kh: string, tot_nghiep: string, thu_tien: string, can_thu: string, per_page: int} */
    private static function boLoc(Request $request): array
    {
        $perPage = (int) $request->input('per_page', 50);

        return [
            'tu_khoa' => trim((string) $request->input('tu_khoa', '')),
            'ma_kh' => trim((string) $request->input('ma_kh', '')),
            'tot_nghiep' => (string) $request->input('tot_nghiep', ''),
            'thu_tien' => (string) $request->input('thu_tien', ''),
            'can_thu' => (string) $request->input('can_thu', ''),
            'per_page' => in_array($perPage, self::SO_DONG_TRANG, true) ? $perPage : 50,
        ];
    }

    /** Thêm các học viên đã chọn của một khoá vào danh sách (bỏ qua học viên đã có). */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ma_kh' => ['required', 'string', 'max:50'],
            'ma_dk' => ['required', 'array', 'min:1'],
            'ma_dk.*' => ['string', 'max:50'],
        ], [
            'ma_dk.required' => 'Chọn ít nhất một học viên để thêm.',
        ]);

        $hopLe = self::hocVienCuaKhoa($data['ma_kh'])->pluck('MaDK')->map(fn ($m) => (string) $m)->all();
        $chon = array_values(array_intersect(array_map('strval', $data['ma_dk']), $hopLe));
        $daCo = ThuTienHocVien::query()->whereIn('MaDK', $chon ?: [''])->pluck('MaDK')->map(fn ($m) => (string) $m)->all();
        $moi = array_values(array_diff($chon, $daCo));

        $now = Carbon::now();
        foreach (array_chunk($moi, 200) as $chunk) {
            ThuTienHocVien::query()->insert(array_map(fn (string $maDk): array => [
                'MaDK' => $maDk,
                'MaKhoaHoc' => $data['ma_kh'],
                'DaTotNghiep' => false,
                'DaThuTien' => false,
                'NguoiTaoId' => auth()->id(),
                'NgayTao' => $now,
                'NgayCapNhat' => $now,
            ], $chunk));
        }

        return redirect()
            ->route('daotao.pdt.bc.thu-tien-hoc-vien', ['ma_kh' => $data['ma_kh']])
            ->with('success', 'Đã thêm '.count($moi).' học viên khoá '.$data['ma_kh'].' vào danh sách'
                .(count($daCo) ? ' (bỏ qua '.count($daCo).' học viên đã có)' : '').'.');
    }

    /** Thao tác hàng loạt trên các dòng đã tick. */
    public function hangLoat(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'hanh_dong' => ['required', Rule::in(['thu_tien', 'bo_thu_tien', 'tot_nghiep', 'bo_tot_nghiep', 'xoa'])],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ], [
            'ids.required' => 'Tick chọn ít nhất một học viên.',
        ]);

        $q = ThuTienHocVien::query()->whereIn('Id', $data['ids']);
        $now = Carbon::now();
        $doiThuTien = function (bool $daThu) use ($q): int {
            // Đổi trạng thái thu tiền phải ghi giao dịch từng học viên.
            return DB::connection('sqlsrv_manhlinh')->transaction(function () use ($q, $daThu): int {
                $ds = (clone $q)->where('DaThuTien', ! $daThu)->get();
                foreach ($ds as $hv) {
                    $hv->capNhatTrangThai(['DaThuTien' => $daThu], auth()->id());
                }

                return $ds->count();
            });
        };
        [$n, $viec] = match ($data['hanh_dong']) {
            'thu_tien' => [$doiThuTien(true), 'đánh dấu đã thu tiền'],
            'bo_thu_tien' => [$doiThuTien(false), 'bỏ đã thu tiền'],
            'tot_nghiep' => [(clone $q)->update(['DaTotNghiep' => true, 'NgayCapNhat' => $now]), 'đánh dấu đã tốt nghiệp'],
            'bo_tot_nghiep' => [(clone $q)->update(['DaTotNghiep' => false, 'NgayCapNhat' => $now]), 'bỏ đã tốt nghiệp'],
            'xoa' => [(clone $q)->delete(), 'xoá khỏi danh sách'],
        };

        return back()->with('success', 'Đã '.$viec.' '.$n.' học viên.');
    }

    public function update(Request $request, ThuTienHocVien $thuTien): RedirectResponse
    {
        $data = $request->validate([
            'da_tot_nghiep' => ['boolean'],
            'da_thu_tien' => ['boolean'],
            'ghi_chu' => ['nullable', 'string', 'max:500'],
        ]);

        DB::connection('sqlsrv_manhlinh')->transaction(fn () => $thuTien->capNhatTrangThai([
            'DaTotNghiep' => (bool) ($data['da_tot_nghiep'] ?? false),
            'DaThuTien' => (bool) ($data['da_thu_tien'] ?? false),
            'GhiChu' => trim((string) ($data['ghi_chu'] ?? '')) ?: null,
        ], auth()->id()));

        return back()->with('success', 'Đã cập nhật học viên '.$thuTien->MaDK.'.');
    }

    /** Modal thu tiền: số dư từng khoản + lịch sử thu/trừ. */
    public function giaoDich(ThuTienHocVien $thuTien): JsonResponse
    {
        return response()->json(self::duLieuModal($thuTien));
    }

    /** Thu thêm / điều chỉnh tay một khoản (vd nộp lại tiền thi sau khi rớt). */
    public function themGiaoDich(Request $request, ThuTienHocVien $thuTien): JsonResponse
    {
        $data = $request->validate([
            'khoan' => ['required', Rule::in(array_keys(ThuTienHocVien::PHI))],
            'loai' => ['required', Rule::in(['thu', 'tru'])],
            'so_tien' => ['required', 'integer', 'min:1000', 'max:100000000'],
            'noi_dung' => ['nullable', 'string', 'max:255'],
        ], [
            'so_tien.required' => 'Nhập số tiền.',
            'so_tien.min' => 'Số tiền tối thiểu 1.000.',
        ]);

        $thuTien->giaoDichs()->create([
            'Khoan' => $data['khoan'],
            'SoTien' => ($data['loai'] === 'thu' ? 1 : -1) * (int) $data['so_tien'],
            'Nguon' => ThuTienHocVienGiaoDich::THU_CONG,
            'NoiDung' => trim((string) ($data['noi_dung'] ?? '')) ?: ($data['loai'] === 'thu' ? 'Thu tiền' : 'Trừ tiền'),
            'NguoiTaoId' => auth()->id(),
            'NgayTao' => Carbon::now(),
        ]);
        $thuTien->forceFill(['NgayCapNhat' => Carbon::now()])->save();

        return response()->json(self::duLieuModal($thuTien) + [
            'message' => ($data['loai'] === 'thu' ? 'Đã thu ' : 'Đã trừ ').number_format((int) $data['so_tien'], 0, ',', '.')
                .' — '.ThuTienHocVien::NHAN_MON[$data['khoan']].'.',
        ]);
    }

    /** Ghi kết quả lần thi tiếp theo của một môn (lần 1, thi lại lần 2, 3…). */
    public function themLanThi(Request $request, ThuTienHocVien $thuTien): JsonResponse
    {
        $data = $request->validate([
            'khoan' => ['required', Rule::in(array_keys(ThuTienHocVien::COT_KET_QUA))],
            'ket_qua' => ['required', Rule::in([ThuTienHocVien::DAU, ThuTienHocVien::ROT])],
            'ngay_thi' => ['nullable', 'date_format:Y-m-d'],
        ]);

        try {
            $lan = DB::connection('sqlsrv_manhlinh')->transaction(
                fn () => $thuTien->themLanThi($data['khoan'], $data['ket_qua'], $data['ngay_thi'] ?? null, auth()->id())
            );
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(self::duLieuModal($thuTien->fresh()) + [
            'message' => ThuTienHocVien::NHAN_MON[$data['khoan']].' lần '.$lan->LanThi.': '.ThuTienHocVien::NHAN_KQ[$data['ket_qua']]
                .' — đã trừ '.ThuTienHocVien::dinhDangTien(ThuTienHocVien::PHI[$data['khoan']]).'.',
        ]);
    }

    /** Huỷ lần thi cuối của một môn (nhập nhầm) — hoàn phí. */
    public function huyLanThi(ThuTienHocVien $thuTien, string $khoan): JsonResponse
    {
        abort_unless(isset(ThuTienHocVien::COT_KET_QUA[$khoan]), 404);
        try {
            DB::connection('sqlsrv_manhlinh')->transaction(fn () => $thuTien->huyLanThiCuoi($khoan, auth()->id()));
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(self::duLieuModal($thuTien->fresh()) + [
            'message' => 'Đã huỷ lần thi cuối môn '.ThuTienHocVien::NHAN_MON[$khoan].', hoàn '.ThuTienHocVien::dinhDangTien(ThuTienHocVien::PHI[$khoan]).'.',
        ]);
    }

    /** Chỉ xoá được giao dịch nhập tay; giao dịch tự động đi theo trạng thái. */
    public function xoaGiaoDich(ThuTienHocVien $thuTien, ThuTienHocVienGiaoDich $giaoDich): JsonResponse
    {
        abort_unless((int) $giaoDich->ThuTienHocVienId === (int) $thuTien->Id, 404);
        if ($giaoDich->Nguon !== ThuTienHocVienGiaoDich::THU_CONG) {
            return response()->json(['message' => 'Giao dịch tự động (theo trạng thái thu tiền / kết quả thi) không xoá được — sửa trạng thái để điều chỉnh.'], 422);
        }
        $giaoDich->delete();

        return response()->json(self::duLieuModal($thuTien) + ['message' => 'Đã xoá giao dịch.']);
    }

    /** @return array<string, mixed> */
    private static function duLieuModal(ThuTienHocVien $thuTien): array
    {
        $hv = self::thongTinHocVien(collect([$thuTien->MaDK]))->first();
        $soDu = [];
        $phaiThu = [];
        foreach (array_keys(ThuTienHocVien::PHI) as $k) {
            $soDu[$k] = $thuTien->tien($k);
            $phaiThu[$k] = $thuTien->phaiThu($k);
        }

        return [
            'ma_dk' => (string) $thuTien->MaDK,
            'ho_ten' => $hv ? ($hv->HoVaTen ?: trim(($hv->HoDemNLX ?? '').' '.($hv->TenNLX ?? ''))) : '',
            'ma_kh' => (string) $thuTien->MaKhoaHoc,
            'so_du' => $soDu,
            'tong' => array_sum($soDu),
            'phai_thu' => $phaiThu,
            'tong_phai_thu' => array_sum($phaiThu),
            'ket_qua' => collect(ThuTienHocVien::COT_KET_QUA)->map(fn (string $cot, string $k): array => [
                'mon' => ThuTienHocVien::NHAN_MON[$k],
                'hien_tai' => $thuTien->{$cot},
                'nhan' => $thuTien->nhanKetQua($k),
                'lan' => $thuTien->lanThis()->where('Khoan', $k)->orderBy('LanThi')->get()
                    ->map(fn ($l): array => [
                        'lan' => $l->LanThi,
                        'kq' => $l->KetQua,
                        'ngay' => $l->NgayThi?->format('d/m/Y'),
                    ])->all(),
            ])->all(),
            'giao_dich' => $thuTien->giaoDichs()
                ->with('nguoiTao:Id,Ten')
                ->orderByDesc('NgayTao')
                ->orderByDesc('Id')
                ->get()
                ->map(fn (ThuTienHocVienGiaoDich $g): array => [
                    'id' => $g->Id,
                    'ngay' => $g->NgayTao?->copy()->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i'),
                    'khoan' => ThuTienHocVien::NHAN_MON[$g->Khoan] ?? $g->Khoan,
                    'so_tien' => $g->SoTien,
                    'noi_dung' => (string) $g->NoiDung,
                    'nguoi' => (string) $g->nguoiTao?->Ten,
                    'thu_cong' => $g->Nguon === ThuTienHocVienGiaoDich::THU_CONG,
                ])
                ->all(),
        ];
    }

    /** @param  array<string, mixed>  $filters */
    private function locDanhSach(array $filters): Builder
    {
        $q = ThuTienHocVien::query();
        if ($filters['ma_kh'] !== '') {
            $q->where('MaKhoaHoc', $filters['ma_kh']);
        }
        if (in_array($filters['tot_nghiep'], ['0', '1'], true)) {
            $q->where('DaTotNghiep', $filters['tot_nghiep'] === '1');
        }
        if (in_array($filters['thu_tien'], ['0', '1'], true)) {
            $q->where('DaThuTien', $filters['thu_tien'] === '1');
        }
        // Cần thu tiền: môn còn phải thu (rớt chưa nộp lại phí thi lại, hoặc đang âm).
        if ($filters['can_thu'] === 'bat_ky') {
            $q->where(function ($w): void {
                foreach (array_keys(ThuTienHocVien::COT_KET_QUA) as $mon) {
                    $w->orWhereRaw(ThuTienHocVien::sqlPhaiThu($mon).' > 0');
                }
            });
        } elseif (isset(ThuTienHocVien::COT_KET_QUA[$filters['can_thu']])) {
            $q->whereRaw(ThuTienHocVien::sqlPhaiThu($filters['can_thu']).' > 0');
        }
        if ($filters['tu_khoa'] !== '') {
            $tk = $filters['tu_khoa'];
            $maTheoTen = NguoiLX::query()
                ->from('NguoiLX as n')
                ->leftJoin('NguoiLX_HoSo as h', 'h.MaDK', '=', 'n.MaDK')
                ->where(fn ($w) => $w->where('n.HoVaTen', 'like', '%'.$tk.'%')
                    ->orWhere('n.TenNLX', 'like', '%'.$tk.'%')
                    ->orWhere('n.HoDemNLX', 'like', '%'.$tk.'%')
                    ->orWhere('n.SoCMT', 'like', '%'.$tk.'%')
                    ->orWhere('h.SoHoSo', 'like', '%'.$tk.'%'))
                ->limit(2000)
                ->pluck('n.MaDK')
                ->map(fn ($m) => (string) $m)
                ->all();
            $q->where(fn ($w) => $w->where('MaDK', 'like', '%'.$tk.'%')->orWhereIn('MaDK', $maTheoTen ?: ['']));
        }

        return $q;
    }

    /**
     * Thông tin học viên bên PMGPLX theo MaDK.
     *
     * @param  Collection<int, mixed>  $maDks
     * @return Collection<string, object>
     */
    private static function thongTinHocVien(Collection $maDks): Collection
    {
        $ma = $maDks->map(fn ($m) => (string) $m)->filter()->unique()->values()->all();
        if ($ma === []) {
            return collect();
        }

        // SQL Server giới hạn ~2100 tham số mỗi câu lệnh.
        return collect(array_chunk($ma, 1000))
            ->flatMap(fn (array $phan) => self::truyVanHocVien()->whereIn('n.MaDK', $phan)->get())
            ->keyBy(fn ($r) => (string) $r->MaDK);
    }

    /** @return Collection<int, object> */
    private static function hocVienCuaKhoa(string $maKh): Collection
    {
        $ds = self::truyVanHocVien()
            ->where('h.MaKhoaHoc', $maKh)
            ->orderByRaw('n.TenNLX COLLATE Vietnamese_CI_AS ASC')
            ->orderByRaw('n.HoDemNLX COLLATE Vietnamese_CI_AS ASC')
            ->orderBy('n.MaDK')
            ->get();
        $daCo = ThuTienHocVien::query()
            ->whereIn('MaDK', $ds->pluck('MaDK')->map(fn ($m) => (string) $m)->all() ?: [''])
            ->pluck('MaDK')
            ->map(fn ($m) => (string) $m)
            ->flip();

        return $ds->each(function ($r) use ($daCo): void {
            $r->da_co = $daCo->has((string) $r->MaDK);
        });
    }

    private static function truyVanHocVien(): Builder
    {
        return NguoiLX::query()
            ->from('NguoiLX as n')
            ->leftJoin('NguoiLX_HoSo as h', 'h.MaDK', '=', 'n.MaDK')
            ->select([
                'n.MaDK', 'n.HoDemNLX', 'n.TenNLX', 'n.HoVaTen', 'n.NgaySinh', 'n.GioiTinh', 'n.SoCMT', 'n.TrangThai',
                'h.SoHoSo', 'h.MaKhoaHoc', 'h.HangGPLX',
            ]);
    }
}
