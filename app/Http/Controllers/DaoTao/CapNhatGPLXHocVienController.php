<?php

namespace App\Http\Controllers\DaoTao;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\CapNhatGPLXHocVien;
use App\Models\PMGPLX\KhoaHoc;
use App\Models\PMGPLX\NguoiLX;
use App\Support\DaoTao\CapNhatGPLXHienThi;
use App\Support\DaoTao\CapNhatGPLXHocVienExcelExporter;
use App\Support\PMGPLX\NgayVn;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Phòng Đào Tạo → Báo cáo → Quản lý cập nhật GPLX học viên.
 * Bảng CapNhatGPLXHocVien (MANHLINH) giữ MaDK + số GPLX mới / trạng thái.
 * Thông tin học viên và GPLX đã có lấy từ NguoiLX / NguoiLX_HoSo (PMGPLX) — không sửa GPLX_BAN_MOI.
 */
class CapNhatGPLXHocVienController extends Controller
{
    private const SO_DONG_TRANG = [20, 50, 100, 200];

    public const TAB_DANG_XU_LY = 'dang_xu_ly';

    public const TAB_DA_XU_LY = 'da_xu_ly';

    public function index(Request $request): View
    {
        $filters = self::boLoc($request);
        $perPage = $filters['per_page'];

        $items = $this->locDanhSach($filters)
            ->with('boSung')
            ->orderByDesc('MaKhoaHoc')
            ->orderBy('Id')
            ->paginate($perPage)
            ->withQueryString();

        $hocVien = self::thongTinHocVien($items->pluck('MaDK'));

        $themKh = trim((string) $request->input('them_kh', ''));
        $khoaHocs = KhoaHoc::query()->orderByDesc('TenKH')->get(['MaKH', 'TenKH']);
        $maTrongDanhSach = CapNhatGPLXHocVien::query()
            ->where('DaXuLy', $filters['tab'] === self::TAB_DA_XU_LY)
            ->whereNotNull('MaKhoaHoc')
            ->distinct()
            ->pluck('MaKhoaHoc')
            ->map(fn ($m) => (string) $m)->flip();

        return view('DaoTao.bao-cao.cap-nhat-gplx-hoc-vien', [
            'items' => $items,
            'hocVien' => $hocVien,
            'tongTrangThai' => $this->tongTrangThai($this->locDanhSach($filters)),
            'filters' => $filters,
            'tab' => $filters['tab'],
            'khoaHocs' => $khoaHocs,
            'tenKhoa' => $khoaHocs->mapWithKeys(fn ($kh) => [(string) $kh->MaKH => (string) $kh->TenKH]),
            'khoaTrongDanhSach' => $khoaHocs->filter(fn ($kh) => $maTrongDanhSach->has((string) $kh->MaKH))
                ->concat($maTrongDanhSach->keys()
                    ->diff($khoaHocs->pluck('MaKH')->map(fn ($m) => (string) $m))
                    ->map(fn (string $ma) => (object) ['MaKH' => $ma, 'TenKH' => null]))
                ->values(),
            'themKh' => $filters['tab'] === self::TAB_DANG_XU_LY ? $themKh : '',
            'ungVien' => ($filters['tab'] === self::TAB_DANG_XU_LY && $themKh !== '') ? self::hocVienCuaKhoa($themKh) : null,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = self::boLoc($request);
        $rows = $this->locDanhSach($filters)->with('boSung')->orderByDesc('MaKhoaHoc')->orderBy('Id')->get();
        $hocVienExport = self::thongTinHocVien($rows->pluck('MaDK'));

        $moTa = [];
        if ($filters['ma_kh'] !== '') {
            $moTa[] = 'Khoá '.$filters['ma_kh'];
        }
        if ($filters['tu_khoa'] !== '') {
            $moTa[] = 'Từ khoá "'.$filters['tu_khoa'].'"';
        }
        if (in_array($filters['cap_nhat'], ['0', '1'], true)) {
            $moTa[] = $filters['cap_nhat'] === '1' ? 'Đã cập nhật GPLX' : 'Chưa cập nhật GPLX';
        }

        $tenFile = 'cap-nhat-gplx-hoc-vien'
            .($filters['ma_kh'] !== '' ? '-'.(preg_replace('/[^A-Za-z0-9_-]+/', '-', $filters['ma_kh']) ?: 'khoa') : '')
            .'-'.now()->format('Ymd-His').'.xlsx';

        return CapNhatGPLXHocVienExcelExporter::download($rows, $hocVienExport, $moTa, $tenFile);
    }

    /** @return array{tab: string, tu_khoa: string, ma_kh: string, cap_nhat: string, per_page: int} */
    private static function boLoc(Request $request): array
    {
        $perPage = (int) $request->input('per_page', 50);
        $tab = (string) $request->input('tab', self::TAB_DANG_XU_LY);

        return [
            'tab' => $tab === self::TAB_DA_XU_LY ? self::TAB_DA_XU_LY : self::TAB_DANG_XU_LY,
            'tu_khoa' => trim((string) $request->input('tu_khoa', '')),
            'ma_kh' => trim((string) $request->input('ma_kh', '')),
            'cap_nhat' => (string) $request->input('cap_nhat', ''),
            'per_page' => in_array($perPage, self::SO_DONG_TRANG, true) ? $perPage : 50,
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ma_kh' => ['nullable', 'string', 'max:50'],
            'ma_dk' => ['required', 'array', 'min:1'],
            'ma_dk.*' => ['string', 'max:50'],
        ], [
            'ma_dk.required' => 'Chọn ít nhất một học viên để thêm.',
        ]);

        $maKhForm = trim((string) ($data['ma_kh'] ?? ''));
        $chon = array_values(array_unique(array_map('strval', $data['ma_dk'])));

        $hocViens = self::truyVanHocVien()
            ->whereIn('n.MaDK', $chon)
            ->get()
            ->keyBy(fn ($r) => (string) $r->MaDK);

        $them = [];
        foreach ($chon as $maDk) {
            $hv = $hocViens->get($maDk);
            if (! $hv) {
                continue;
            }
            $maKh = (string) ($hv->MaKhoaHoc ?? '');
            if ($maKhForm !== '' && $maKh !== $maKhForm) {
                continue;
            }
            if ($maKh === '') {
                continue;
            }
            $them[] = [
                'MaDK' => $maDk,
                'MaKhoaHoc' => $maKh,
                'HangGPLX' => trim((string) ($hv->HangGPLX ?? '')) ?: null,
            ];
        }

        $daCo = CapNhatGPLXHocVien::query()
            ->whereIn('MaDK', array_column($them, 'MaDK') ?: [''])
            ->pluck('MaDK')
            ->map(fn ($m) => (string) $m)
            ->flip();

        $moi = array_values(array_filter($them, fn (array $row): bool => ! $daCo->has($row['MaDK'])));

        $now = Carbon::now();
        foreach (array_chunk($moi, 200) as $chunk) {
            CapNhatGPLXHocVien::query()->insert(array_map(fn (array $row): array => [
                'MaDK' => $row['MaDK'],
                'MaKhoaHoc' => $row['MaKhoaHoc'],
                'HangGPLX' => $row['HangGPLX'],
                'DaCapNhatGPLX' => false,
                'DaXuLy' => false,
                'NguoiTaoId' => auth()->id(),
                'NgayTao' => $now,
                'NgayCapNhat' => $now,
            ], $chunk));
        }

        $redirectMaKh = $maKhForm !== '' ? $maKhForm : ($moi[0]['MaKhoaHoc'] ?? '');

        return redirect()
            ->route('daotao.pdt.bc.cap-nhat-gplx', array_filter([
                'ma_kh' => $redirectMaKh,
                'tab' => self::TAB_DANG_XU_LY,
            ]))
            ->with('success', 'Đã thêm '.count($moi).' học viên vào danh sách cập nhật GPLX'
                .($daCo->count() ? ' (bỏ qua '.$daCo->count().' học viên đã có)' : '').'.');
    }

    public function timHocVienOptions(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('q', ''));
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 30;

        if ($search === '') {
            return response()->json(['results' => [], 'pagination' => ['more' => false]]);
        }

        $like = '%'.$search.'%';
        $query = self::truyVanHocVien()
            ->where(function (Builder $w) use ($like): void {
                $w->where('n.HoVaTen', 'like', $like)
                    ->orWhere('n.TenNLX', 'like', $like)
                    ->orWhere('n.HoDemNLX', 'like', $like)
                    ->orWhere('n.SoCMT', 'like', $like)
                    ->orWhere('n.MaDK', 'like', $like);
            })
            ->orderByRaw('n.TenNLX COLLATE Vietnamese_CI_AS ASC')
            ->orderByRaw('n.HoDemNLX COLLATE Vietnamese_CI_AS ASC')
            ->orderBy('n.MaDK');

        $offset = ($page - 1) * $perPage;
        $rows = (clone $query)->skip($offset)->take($perPage + 1)->get();
        $more = $rows->count() > $perPage;

        $maDks = $rows->take($perPage)->pluck('MaDK')->map(fn ($m) => (string) $m)->all();
        $daCo = CapNhatGPLXHocVien::query()
            ->whereIn('MaDK', $maDks ?: [''])
            ->pluck('MaDK')
            ->map(fn ($m) => (string) $m)
            ->flip();
        $daXuLy = CapNhatGPLXHocVien::query()
            ->where('DaXuLy', true)
            ->whereIn('MaDK', $maDks ?: [''])
            ->pluck('MaDK')
            ->map(fn ($m) => (string) $m)
            ->flip();

        $results = $rows->take($perPage)->map(function ($row) use ($daCo, $daXuLy): ?array {
            $maDk = (string) $row->MaDK;
            if ($daXuLy->has($maDk)) {
                return null;
            }
            $ten = $row->HoVaTen ?: trim(($row->HoDemNLX ?? '').' '.($row->TenNLX ?? ''));
            $ns = NgayVn::format($row->NgaySinh, 'yyyymmdd');
            $cmt = trim((string) ($row->SoCMT ?? ''));
            $tenKh = trim((string) ($row->TenKhoaHoc ?? ''));
            $maKh = trim((string) ($row->MaKhoaHoc ?? ''));
            $khoa = $tenKh !== '' ? $tenKh.' ('.$maKh.')' : $maKh;
            $text = implode(' - ', array_filter([$ten, $ns, $cmt, $khoa], fn (string $p): bool => $p !== ''));

            return [
                'id' => $maDk,
                'text' => $text.($daCo->has($maDk) ? ' — đã có trong danh sách' : ''),
                'disabled' => $daCo->has($maDk),
            ];
        })->filter()->values()->all();

        return response()->json([
            'results' => $results,
            'pagination' => ['more' => $more],
        ]);
    }

    public function hangLoat(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'hanh_dong' => ['required', Rule::in(['cap_nhat', 'bo_cap_nhat', 'chuyen_da_xu_ly', 'chuyen_dang_xu_ly', 'xoa'])],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ], [
            'ids.required' => 'Tick chọn ít nhất một học viên.',
        ]);

        $q = CapNhatGPLXHocVien::query()->whereIn('Id', $data['ids']);
        $now = Carbon::now();
        $doiCapNhat = function (bool $daCap) use ($q): int {
            $ds = (clone $q)->where('DaCapNhatGPLX', ! $daCap)->get();
            foreach ($ds as $hv) {
                $hv->capNhatTrangThai(['DaCapNhatGPLX' => $daCap]);
            }

            return $ds->count();
        };
        [$n, $viec] = match ($data['hanh_dong']) {
            'cap_nhat' => [$doiCapNhat(true), 'đánh dấu đã cập nhật GPLX'],
            'bo_cap_nhat' => [$doiCapNhat(false), 'bỏ đã cập nhật GPLX'],
            'chuyen_da_xu_ly' => [(clone $q)->update(['DaXuLy' => true, 'NgayCapNhat' => $now]), 'chuyển sang tab đã xử lý'],
            'chuyen_dang_xu_ly' => [(clone $q)->update(['DaXuLy' => false, 'NgayCapNhat' => $now]), 'chuyển về danh sách cập nhật GPLX'],
            'xoa' => [(clone $q)->delete(), 'xoá khỏi danh sách'],
        };

        return back()->with('success', 'Đã '.$viec.' '.$n.' học viên.');
    }

    public function update(Request $request, CapNhatGPLXHocVien $gplx): RedirectResponse
    {
        $data = $request->validate([
            'da_cap_nhat_gplx' => ['boolean'],
            'da_xu_ly' => ['boolean'],
            'so_gplx' => ['nullable', 'string', 'max:30'],
            'hang_gplx' => ['nullable', 'string', 'max:20'],
            'ngay_cap_gplx' => ['nullable', 'date'],
            'ngay_hh_gplx' => ['nullable', 'date'],
            'noi_cap_gplx' => ['nullable', 'string', 'max:255'],
            'noi_dk_ho_khau' => ['nullable', 'string', 'max:500'],
            'da_co_gplx' => ['nullable', 'string', 'max:30'],
            'ngay_trung_tuyen' => ['nullable', 'date'],
        ]);

        CapNhatGPLXHienThi::luuBoSungManhlinh($gplx, $data);

        $gplx->capNhatTrangThai([
            'DaCapNhatGPLX' => (bool) ($data['da_cap_nhat_gplx'] ?? false),
            'DaXuLy' => (bool) ($data['da_xu_ly'] ?? false),
            'SoGPLX' => trim((string) ($data['so_gplx'] ?? '')) ?: null,
            'HangGPLX' => trim((string) ($data['hang_gplx'] ?? '')) ?: null,
            'NgayCapGPLX' => $data['ngay_cap_gplx'] ?? null,
            'NgayHHGPLX' => $data['ngay_hh_gplx'] ?? null,
            'NoiCapGPLX' => trim((string) ($data['noi_cap_gplx'] ?? '')) ?: null,
        ]);

        return back()->with('success', 'Đã cập nhật GPLX học viên '.$gplx->MaDK.'.');
    }

    /** @param  array<string, mixed>  $filters */
    private function locDanhSach(array $filters): Builder
    {
        $q = CapNhatGPLXHocVien::query()->where('DaXuLy', $filters['tab'] === self::TAB_DA_XU_LY);
        if ($filters['ma_kh'] !== '') {
            $q->where('MaKhoaHoc', $filters['ma_kh']);
        }
        if (in_array($filters['cap_nhat'], ['0', '1'], true)) {
            $q->where('DaCapNhatGPLX', $filters['cap_nhat'] === '1');
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
                    ->orWhere('h.SoHoSo', 'like', '%'.$tk.'%')
                    ->orWhere('h.SoGPLXDaCo', 'like', '%'.$tk.'%'))
                ->limit(2000)
                ->pluck('n.MaDK')
                ->map(fn ($m) => (string) $m)
                ->all();
            $q->where(fn ($w) => $w->where('MaDK', 'like', '%'.$tk.'%')
                ->orWhere('SoGPLX', 'like', '%'.$tk.'%')
                ->orWhereIn('MaDK', $maTheoTen ?: ['']));
        }

        return $q;
    }

    /**
     * @param  Builder<CapNhatGPLXHocVien>  $query
     * @return array{tong: int, da_cap_nhat: int, chua_cap_nhat: int}
     */
    private function tongTrangThai(Builder $query): array
    {
        $base = (clone $query)->reorder()->toBase();
        $base->columns = null;
        $row = $base->selectRaw('COUNT(*) AS tong, SUM(CASE WHEN DaCapNhatGPLX = 1 THEN 1 ELSE 0 END) AS da_cap_nhat')->first();
        $tong = (int) ($row->tong ?? 0);
        $da = (int) ($row->da_cap_nhat ?? 0);

        return ['tong' => $tong, 'da_cap_nhat' => $da, 'chua_cap_nhat' => $tong - $da];
    }

    /**
     * @param  Collection<int, mixed>  $maDks
     * @return Collection<string, object>
     */
    private static function thongTinHocVien(Collection $maDks): Collection
    {
        $ma = $maDks->map(fn ($m) => (string) $m)->filter()->unique()->values()->all();
        if ($ma === []) {
            return collect();
        }

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
        $maDks = $ds->pluck('MaDK')->map(fn ($m) => (string) $m)->all();
        $trongDs = CapNhatGPLXHocVien::query()
            ->whereIn('MaDK', $maDks ?: [''])
            ->get(['MaDK', 'DaXuLy'])
            ->keyBy(fn ($r) => (string) $r->MaDK);

        return $ds->filter(function ($r) use ($trongDs): bool {
            $tt = $trongDs->get((string) $r->MaDK);

            return ! $tt || ! $tt->DaXuLy;
        })->each(function ($r) use ($trongDs): void {
            $r->da_co = $trongDs->has((string) $r->MaDK);
        })->values();
    }

    private static function truyVanHocVien(): Builder
    {
        return NguoiLX::query()
            ->from('NguoiLX as n')
            ->leftJoin('NguoiLX_HoSo as h', 'h.MaDK', '=', 'n.MaDK')
            ->leftJoin('KhoaHoc as kh', 'kh.MaKH', '=', 'h.MaKhoaHoc')
            ->select([
                'n.MaDK', 'n.HoDemNLX', 'n.TenNLX', 'n.HoVaTen', 'n.NgaySinh', 'n.GioiTinh', 'n.SoCMT', 'n.TrangThai',
                'n.NoiTT', 'n.NoiCT',
                'h.SoHoSo', 'h.MaKhoaHoc', 'h.HangGPLX',
                'h.SoGPLXDaCo', 'h.HangGPLXDaCo', 'h.DonViCapGPLXDaCo', 'h.NoiCapGPLXDaCo',
                'h.NgayCapGPLXDaCo', 'h.NgayHHGPLXDaCo', 'h.NgayTTGPLXDaCo', 'h.SoGPLXTmp',
                'kh.TenKH as TenKhoaHoc',
            ]);
    }

}
