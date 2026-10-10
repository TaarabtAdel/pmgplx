<?php

namespace App\Http\Controllers\PMGPLX;

use App\Http\Controllers\Controller;
use App\Models\PMGPLX\GiaoVien;
use App\Models\PMGPLX\KhoaHoc;
use App\Support\PMGPLX\GiaoVienDoTrongCapXeTrongKhoa;
use App\Support\PMGPLX\GiaoVienLichTrongScanner;
use App\Support\PMGPLX\LichXeLoaiGhiChu;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DoTrongLichGiaoVienController extends Controller
{
    public function show(Request $request): View
    {
        $submitted = $request->boolean('run');
        $input = $this->inputFromRequest($request);
        $result = $submitted ? GiaoVienLichTrongScanner::scan($input) : null;

        $khoaHocs = KhoaHoc::query()->orderBy('TenKH')->get(['MaKH', 'TenKH']);

        return view('PMGPLX.lich.do-trong-lich-gv', [
            'khoaHocs' => $khoaHocs,
            'giaoViens' => GiaoVien::query()->orderBy('TenGV')->orderBy('MaGV')->get(['MaGV', 'HoTenDem', 'TenGV']),
            'gvCapChieuByKhoaSang' => GiaoVienDoTrongCapXeTrongKhoa::chieuPartnerMapByKhoa(
                $khoaHocs->pluck('MaKH')->all()
            ),
            'input' => $input,
            'result' => $result,
            'submitted' => $submitted,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function inputFromRequest(Request $request): array
    {
        $pairs = $this->pairsFromRequest($request);
        if ($pairs === [] && ! $request->boolean('run')) {
            $pairs = [['ma_kh' => '', 'ma_gv_sang' => '', 'ma_gv_chieu' => '']];
        }

        return [
            'pairs' => $pairs,
            'bo_qua_loai' => $this->boQuaLoaiFromRequest($request),
            'min_free_phut' => $request->input('min_free_phut', ''),
            'tu_ngay' => $request->input('tu_ngay', ''),
            'den_ngay' => $request->input('den_ngay', ''),
            'gio_bat_dau' => $request->input('gio_bat_dau', '06:00'),
            'gio_ket_thuc' => $request->input('gio_ket_thuc', '22:00'),
            'trang_thai' => $request->input('trang_thai', '1'),
        ];
    }

    /**
     * @return list<array{ma_kh: string, ma_gv_sang: string, ma_gv_chieu: string}>
     */
    private function pairsFromRequest(Request $request): array
    {
        $maKh = $request->input('pair_ma_kh', []);
        $maGvSang = $request->input('pair_ma_gv_sang', []);
        $maGvChieu = $request->input('pair_ma_gv_chieu', []);
        $legacyMaGv = $request->input('pair_ma_gv', []);

        if (! is_array($maKh)) {
            $maKh = $maKh !== '' && $maKh !== null ? [(string) $maKh] : [];
        }
        if (! is_array($maGvSang)) {
            $maGvSang = $maGvSang !== '' && $maGvSang !== null ? [(string) $maGvSang] : [];
        }
        if (! is_array($maGvChieu)) {
            $maGvChieu = $maGvChieu !== '' && $maGvChieu !== null ? [(string) $maGvChieu] : [];
        }
        if (! is_array($legacyMaGv)) {
            $legacyMaGv = $legacyMaGv !== '' && $legacyMaGv !== null ? [(string) $legacyMaGv] : [];
        }

        $count = max(count($maKh), count($maGvSang), count($maGvChieu), count($legacyMaGv));
        $pairs = [];
        for ($i = 0; $i < $count; $i++) {
            $sang = trim((string) ($maGvSang[$i] ?? ''));
            $chieu = trim((string) ($maGvChieu[$i] ?? ''));
            $legacy = trim((string) ($legacyMaGv[$i] ?? ''));
            if ($sang === '' && $legacy !== '') {
                $sang = $legacy;
            }
            if ($chieu === '' && $sang !== '') {
                $chieu = $sang;
            }
            if ($chieu === '' && $legacy !== '') {
                $chieu = $legacy;
            }

            $pairs[] = [
                'ma_kh' => trim((string) ($maKh[$i] ?? '')),
                'ma_gv_sang' => $sang,
                'ma_gv_chieu' => $chieu,
            ];
        }

        return $pairs;
    }

    /**
     * @return list<string>
     */
    private function boQuaLoaiFromRequest(Request $request): array
    {
        $raw = $request->input('bo_qua_loai', null);
        if (is_array($raw)) {
            return LichXeLoaiGhiChu::normalizeDoTrongHideDayFilters($raw);
        }

        $legacy = $request->input('pair_loai', []);
        if (! is_array($legacy)) {
            return LichXeLoaiGhiChu::normalizeDoTrongHideDayFilters(is_string($legacy) ? $legacy : '');
        }

        $flat = [];
        foreach ($legacy as $value) {
            if (is_string($value) && $value !== '') {
                $flat[] = $value;
            } elseif (is_array($value)) {
                foreach ($value as $item) {
                    if (is_string($item) && $item !== '') {
                        $flat[] = $item;
                    }
                }
            }
        }

        return LichXeLoaiGhiChu::normalizeDoTrongHideDayFilters($flat);
    }
}
