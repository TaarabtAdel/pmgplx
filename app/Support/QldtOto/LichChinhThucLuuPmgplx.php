<?php

namespace App\Support\QldtOto;

use App\Models\PMGPLX\DmMonHoc;
use App\Models\PMGPLX\GiaoVien;
use App\Models\PMGPLX\KhoaHoc;
use App\Models\PMGPLX\KhoaHocGiaoVien;
use App\Models\PMGPLX\KhoaHocXeTap;
use App\Models\QldtOto\LichChinhThuc;
use App\Models\QldtOto\LichChinhThucBuoi;
use App\Support\PMGPLX\GiaoVienLichCrossKhoaChecker;
use App\Support\PMGPLX\LichCalendar;
use App\Support\PMGPLX\LichExcelTimeParser;
use App\Support\PMGPLX\LichGvMonHoc;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Ghi lịch chính thức vào KhoaHoc_GiaoVien / KhoaHoc_XeTap (cùng kiểu nhập file PMGPLX). */
final class LichChinhThucLuuPmgplx
{
    /**
     * @return array{saved_gv: int, saved_xe: int, wiped_gv: int, wiped_xe: int, ma_kh: string}
     */
    public static function luu(LichChinhThuc $lich, ?string $maKhOverride = null): array
    {
        $maKh = trim((string) ($maKhOverride ?? $lich->MaKH ?? ''));
        if ($maKh === '') {
            $maKh = (string) (LichChinhThucTuDuKien::goiYMaKh($lich->TenKhoa) ?? '');
        }
        if ($maKh === '' || ! KhoaHoc::query()->where('MaKH', $maKh)->exists()) {
            throw ValidationException::withMessages([
                'ma_kh' => 'Chọn mã khoá PMGPLX hợp lệ trước khi lưu vào phần mềm.',
            ]);
        }

        $buois = $lich->buois()->orderBy('Ngay')->orderBy('CapThuTu')->orderBy('Ca', 'desc')->get();
        if ($buois->isEmpty()) {
            throw ValidationException::withMessages([
                'lich' => 'Lịch chưa có buổi — không lưu được vào phần mềm.',
            ]);
        }

        $tenGv = GiaoVien::query()
            ->whereIn('MaGV', $buois->pluck('MaGv')->filter()->unique()->values()->all())
            ->get(['MaGV', 'HoTenDem', 'TenGV'])
            ->mapWithKeys(fn (GiaoVien $g): array => [trim((string) $g->MaGV) => $g->HoTen])
            ->all();

        $defaultMon = DmMonHoc::active()
            ->where(function ($q): void {
                $q->where('TenMH', 'Thực hành lái xe')
                    ->orWhere('TenMH', 'like', '%Thực hành lái xe%');
            })
            ->orderBy('MaMH')
            ->first(['MaMH']);
        $dbMon = LichGvMonHoc::dbFields($defaultMon->MaMH ?? null);

        $gvRows = [];
        $xeRows = [];
        foreach ($buois as $buoi) {
            foreach (self::buildRows($buoi, $maKh, $tenGv, $dbMon) as $built) {
                $gvRows[] = $built['gv'];
                if ($built['xe'] !== null) {
                    $xeRows[] = $built['xe'];
                }
            }
        }

        if ($gvRows === []) {
            throw ValidationException::withMessages([
                'lich' => 'Không có buổi nào đủ giáo viên để lưu.',
            ]);
        }

        $cross = GiaoVienLichCrossKhoaChecker::validateImportGvRowsForCrossKhoa($gvRows);
        if ($cross !== []) {
            throw ValidationException::withMessages([
                'lich' => "Không lưu được: giáo viên trùng khung giờ giữa các khóa.\n"
                    .implode("\n", array_slice($cross, 0, 8)),
            ]);
        }

        $now = Carbon::now();
        $savedGv = 0;
        $savedXe = 0;
        $wipedGv = 0;
        $wipedXe = 0;

        DB::connection('sqlsrv')->transaction(function () use (
            $maKh, $gvRows, $xeRows, $now, &$savedGv, &$savedXe, &$wipedGv, &$wipedXe
        ): void {
            $wipedGv = KhoaHocGiaoVien::query()
                ->where('MaKH', $maKh)
                ->where('IsKhoaHocGiaoVien', 0)
                ->delete();
            $wipedXe = KhoaHocXeTap::query()
                ->where('MaKH', $maKh)
                ->where('IsKhoaHocXeTap', 0)
                ->delete();

            foreach ($gvRows as $row) {
                KhoaHocGiaoVien::query()->create($row + [
                    'LoaiGV' => 'TH',
                    'TrangThai' => 1,
                    'NgayTao' => $now,
                    'NgaySua' => $now,
                    'IsKhoaHocGiaoVien' => 0,
                ]);
                $savedGv++;
            }

            foreach ($xeRows as $row) {
                KhoaHocXeTap::query()->create($row + [
                    'TrangThai' => 1,
                    'NgayTao' => $now,
                    'NgaySua' => $now,
                    'IsKhoaHocXeTap' => 0,
                ]);
                $savedXe++;
            }
        });

        $lich->forceFill([
            'MaKH' => $maKh,
            'NgayLuuPm' => $now,
            'SoBuoiLuuPm' => $savedGv,
            'NgayCapNhat' => $now,
        ])->save();

        return [
            'saved_gv' => $savedGv,
            'saved_xe' => $savedXe,
            'wiped_gv' => $wipedGv,
            'wiped_xe' => $wipedXe,
            'ma_kh' => $maKh,
        ];
    }

    /**
     * @param  array<string, string>  $tenGv
     * @param  array{MaMonHoc: null, TenMonHoc: string}  $dbMon
     * @return list<array{gv: array<string, mixed>, xe: ?array<string, mixed>}>
     */
    private static function buildRows(LichChinhThucBuoi $buoi, string $maKh, array $tenGv, array $dbMon): array
    {
        $maGv = trim((string) $buoi->MaGv);
        if ($maGv === '') {
            return [];
        }

        $ymd = Carbon::parse($buoi->Ngay)->format('Y-m-d');
        $slots = LichExcelTimeParser::expandSlots(
            trim((string) $buoi->GioBatDau),
            trim((string) $buoi->GioKetThuc)
        );
        if ($slots === []) {
            $slots = [['start' => '07:00', 'end' => '11:00']];
        }

        $ghiChu = trim($buoi->NgaySo.'. '.$buoi->NoiDung, '. ');
        $ten = $tenGv[$maGv] ?? $maGv;
        $bienSo = trim((string) $buoi->BienSoXe);
        $out = [];

        foreach ($slots as $slot) {
            $bd = LichCalendar::combineDateAndTime($ymd, $slot['start']);
            $kt = LichCalendar::combineDateAndTime($ymd, $slot['end']);
            if ($kt->lte($bd)) {
                // Ca đêm / qua ngày: kết thúc ngày hôm sau (cùng quy ước nhập file).
                $kt = $kt->copy()->addDay();
            }

            $gv = [
                'MaKH' => $maKh,
                'MaGV' => $maGv,
                'TenGV' => $ten,
                'GhiChu' => $ghiChu,
                'NgayBD' => $bd->format('Y-m-d H:i:s'),
                'NgayKT' => $kt->format('Y-m-d H:i:s'),
                'MaMonHoc' => $dbMon['MaMonHoc'],
                'TenMonHoc' => $dbMon['TenMonHoc'],
            ];

            $xe = null;
            if ($bienSo !== '') {
                $xe = [
                    'MaKH' => $maKh,
                    'BienSoXe' => $bienSo,
                    'MaGV' => $maGv,
                    'TenGV' => $ten,
                    'GhiChu' => $ghiChu,
                    'NgayBD' => $bd->format('Y-m-d H:i:s'),
                    'NgayKT' => $kt->format('Y-m-d H:i:s'),
                ];
            }

            $out[] = ['gv' => $gv, 'xe' => $xe];
        }

        return $out;
    }
}
