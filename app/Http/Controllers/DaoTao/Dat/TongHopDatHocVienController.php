<?php

namespace App\Http\Controllers\DaoTao\Dat;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\DatDieuKienDat;
use App\Models\DaoTao\DatDSPhien;
use App\Support\DaoTao\DatDSPhienBoLoc;
use App\Support\DaoTao\DatHocVienTongHop;
use App\Support\DaoTao\DatXeSoTuDong;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TongHopDatHocVienController extends Controller
{
    public function index(Request $request): View
    {
        $filters = DatDSPhienBoLoc::parseFilters($request);
        $canTongHop = ($filters['ma_khoa_hoc'] ?? '') !== '';

        $items = null;
        if ($canTongHop) {
            $validSessionIds = DatHocVienTongHop::validSessionIdsFromFilters($filters);
            $tongXeSoTuDongSql = DatXeSoTuDong::sqlSumGioTuDong();
            $tongBanDemSql = DatXeSoTuDong::sqlSumGioBanDem();

            $query = DatDSPhienBoLoc::filteredQuery($filters, orderBy: null);
            DatHocVienTongHop::restrictToSessionIds($query, $validSessionIds);
            $query
                ->selectRaw("
                    MaHocVien,
                    MAX(HoTenHocVien) as HoTenHocVien,
                    MaKhoaHoc,
                    MAX(TenKhoaHoc) as TenKhoaHoc,
                    LoaiKhoaHoc,
                    COUNT(*) as SoPhien,
                    SUM(COALESCE(ThoiGianThucHanhGio, 0)) as TongGioHoc,
                    SUM(COALESCE(QuangDuongThucHanhKm, 0)) as TongQuangDuongKm,
                    {$tongBanDemSql} as TongBanDemGio,
                    {$tongXeSoTuDongSql} as TongXeSoTuDongGio
                ")
                ->whereNotNull('MaHocVien')
                ->where('MaHocVien', '!=', '')
                ->groupBy('MaHocVien', 'MaKhoaHoc', 'LoaiKhoaHoc');

            DatHocVienTongHop::applyDatCtFilter($query, $filters['dat_ct']);

            $items = $query
                ->orderBy('MaHocVien')
                ->orderBy('MaKhoaHoc')
                ->paginate(50)
                ->withQueryString();
        }

        $khoaHocOptions = DatDSPhien::query()
            ->selectRaw('MaKhoaHoc, MAX(TenKhoaHoc) as TenKhoaHoc')
            ->whereNotNull('MaKhoaHoc')
            ->where('MaKhoaHoc', '!=', '')
            ->groupBy('MaKhoaHoc')
            ->orderBy('MaKhoaHoc')
            ->get();

        $loaiKhoaHocOptions = DatDSPhien::query()
            ->when($canTongHop, fn ($q) => $q->where('MaKhoaHoc', $filters['ma_khoa_hoc']))
            ->whereNotNull('LoaiKhoaHoc')
            ->where('LoaiKhoaHoc', '!=', '')
            ->distinct()
            ->orderBy('LoaiKhoaHoc')
            ->pluck('LoaiKhoaHoc');

        $selectedHocVienOption = null;
        if ($filters['ma_hoc_vien'] !== '') {
            [$maHv, $maKh] = DatDSPhienBoLoc::parseMaHocVienFilter($filters['ma_hoc_vien']);
            $selectedQuery = DatDSPhien::query()
                ->selectRaw('MaHocVien, MaKhoaHoc, MAX(HoTenHocVien) as HoTenHocVien, MAX(TenKhoaHoc) as TenKhoaHoc')
                ->where('MaHocVien', $maHv)
                ->groupBy('MaHocVien', 'MaKhoaHoc');

            if ($maKh !== '') {
                $selectedQuery->where('MaKhoaHoc', $maKh);
            }

            $selectedRow = $selectedQuery->first();
            if ($selectedRow) {
                $selectedHocVienOption = [
                    'id' => DatDSPhienBoLoc::composeMaHocVienValue(
                        (string) $selectedRow->MaHocVien,
                        (string) ($selectedRow->MaKhoaHoc ?? '')
                    ),
                    'text' => self::formatHocVienOptionText($selectedRow),
                ];
            }
        }

        $dieuKienByHang = DatDieuKienDat::query()
            ->orderBy('ThuTu')
            ->orderBy('Hang')
            ->get()
            ->keyBy('Hang');

        return view('DaoTao.dat.tong-hop-hoc-vien', [
            'items' => $items,
            'canTongHop' => $canTongHop,
            'filters' => $filters,
            'khoaHocOptions' => $khoaHocOptions,
            'loaiKhoaHocOptions' => $loaiKhoaHocOptions,
            'selectedHocVienOption' => $selectedHocVienOption,
            'dieuKienByHang' => $dieuKienByHang,
            'apDungTuNgay' => DatDieuKienDat::AP_DUNG_TU_NGAY,
        ]);
    }

    private static function formatHocVienOptionText(object $row): string
    {
        $maHv = (string) ($row->MaHocVien ?? '');
        $ten = trim((string) ($row->HoTenHocVien ?? ''));
        $tenKh = trim((string) ($row->TenKhoaHoc ?? ''));
        $maKh = trim((string) ($row->MaKhoaHoc ?? ''));

        $label = $ten !== '' ? $ten.' ('.$maHv.')' : $maHv;
        $khoa = $tenKh !== '' ? $tenKh : $maKh;

        if ($khoa !== '') {
            $label .= ' — '.$khoa;
        }

        return $label;
    }
}
