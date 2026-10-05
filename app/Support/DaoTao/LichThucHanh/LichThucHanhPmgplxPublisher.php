<?php

namespace App\Support\DaoTao\LichThucHanh;

use App\Models\PMGPLX\DmMonHoc;
use App\Models\PMGPLX\KhoaHoc;
use App\Models\PMGPLX\KhoaHocGiaoVien;
use App\Models\PMGPLX\KhoaHocXeTap;
use App\Support\DaoTao\PhanCongTuLichPmgplxQuery;
use App\Support\PMGPLX\LichCalendar;
use App\Support\PMGPLX\LichExcelBienSo;
use App\Support\PMGPLX\LichExcelDiaDiem;
use App\Support\PMGPLX\LichExcelNoiDungSkip;
use App\Support\PMGPLX\LichExcelTimeParser;
use App\Support\PMGPLX\LichGvMonHoc;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Ghi lịch TH (sinh từ Phòng đào tạo) vào PMGPLX — cùng 2 bảng với nhập lịch từ file.
 */
final class LichThucHanhPmgplxPublisher
{
    public const NGUON = 'DAT_LTH';

    private const TEN_MON_HOC = 'Thực hành lái xe';

    /**
     * @param  array<string, mixed>  $lich
     * @param  array<string, mixed>  $cauHinh
     * @return array{gv: int, xe: int, skipped: int}
     */
    public function publish(array $lich, array $cauHinh): array
    {
        $maKh = trim((string) ($cauHinh['ma_khoa'] ?? ''));
        if ($maKh === '') {
            throw new \InvalidArgumentException('Thiếu mã khóa (MaKH) để đẩy lịch PMGPLX.');
        }

        $tenKh = (string) (KhoaHoc::query()->where('MaKH', $maKh)->value('TenKH') ?? $maKh);
        $dbMon = $this->defaultMonHoc();
        $now = Carbon::now();
        $savedGv = 0;
        $savedXe = 0;
        $skipped = 0;

        DB::transaction(function () use (
            $lich,
            $maKh,
            $tenKh,
            $dbMon,
            $now,
            &$savedGv,
            &$savedXe,
            &$skipped
        ): void {
            KhoaHocGiaoVien::query()
                ->where('MaKH', $maKh)
                ->where('NguoiTao', self::NGUON)
                ->delete();

            KhoaHocXeTap::query()
                ->where('MaKH', $maKh)
                ->where('NguoiTao', self::NGUON)
                ->delete();

            foreach ($lich['cells'] ?? [] as $cell) {
                if (! is_array($cell)) {
                    continue;
                }

                if (($cell['nghi_ca_khoa'] ?? false) || ($cell['bai'] ?? '') === 'NGHỈ' || ($cell['mau'] ?? '') === 'NGHI') {
                    $skipped++;

                    continue;
                }

                $iso = substr((string) ($cell['thoi_gian'] ?? ''), 0, 10);
                if ($iso === '') {
                    $skipped++;

                    continue;
                }

                $noiDung = (string) ($cell['bai'] ?? '');
                $chiTiet = LichCellHienThi::noiDungPhu($cell);
                $batDau = (string) ($cell['bat_dau'] ?? '');
                $ketThuc = (string) ($cell['ket_thuc'] ?? '');
                $slots = LichExcelTimeParser::expandSlots($batDau, $ketThuc);
                if ($slots === []) {
                    $skipped++;

                    continue;
                }

                $maGv = (string) ($cell['ma_gv'] ?? '');
                $tenGv = (string) ($cell['ho_ten'] ?? $maGv);
                $bienSoMacDinh = LichExcelBienSo::normalize((string) ($cell['bien_so'] ?? ''));
                $bienSoTuDong = LichExcelBienSo::extractFromTuDong($noiDung, $chiTiet);
                $bienSo = $bienSoTuDong ?? $bienSoMacDinh;
                $diaDiem = LichExcelDiaDiem::resolve($noiDung, $chiTiet);
                $ghiChuXe = LichExcelNoiDungSkip::format($noiDung, $chiTiet);
                if ($ghiChuXe === '—') {
                    $ghiChuXe = '';
                }

                foreach ($slots as $slot) {
                    $ngayBD = LichCalendar::combineDateAndTime($iso, $slot['start']);
                    $ngayKT = LichCalendar::combineDateAndTime($iso, $slot['end']);
                    if ($ngayKT->lte($ngayBD)) {
                        $ngayKT = $ngayKT->copy()->addDay();
                    }

                    if (! LichExcelNoiDungSkip::isGvSkip($noiDung, $chiTiet)) {
                        KhoaHocGiaoVien::create([
                            'MaKH' => $maKh,
                            'MaGV' => $maGv,
                            'TenGV' => $tenGv,
                            'LoaiGV' => 'TH',
                            'GhiChu' => '',
                            'TrangThai' => 1,
                            'NguoiTao' => self::NGUON,
                            'NguoiSua' => self::NGUON,
                            'NgayTao' => $now,
                            'NgaySua' => $now,
                            'NgayBD' => $ngayBD,
                            'NgayKT' => $ngayKT,
                            'IsKhoaHocGiaoVien' => 0,
                            'MaMonHoc' => $dbMon['MaMonHoc'],
                            'TenMonHoc' => $dbMon['TenMonHoc'],
                        ]);
                        $savedGv++;
                    }

                    if ($bienSo === '') {
                        continue;
                    }

                    if (! PhanCongTuLichPmgplxQuery::shouldIncludeLichXeTapRow($tenKh, $bienSo)) {
                        continue;
                    }

                    if (LichExcelNoiDungSkip::isXeSkip($noiDung, $chiTiet)) {
                        continue;
                    }

                    KhoaHocXeTap::create([
                        'MaKH' => $maKh,
                        'BienSoXe' => $bienSo,
                        'MaGV' => $maGv,
                        'TenGV' => $tenGv,
                        'DiaDiem' => $diaDiem,
                        'GhiChu' => $ghiChuXe,
                        'TrangThai' => 1,
                        'NguoiTao' => self::NGUON,
                        'NguoiSua' => self::NGUON,
                        'NgayBD' => $ngayBD,
                        'NgayKT' => $ngayKT,
                        'NgayTao' => $now,
                        'NgaySua' => $now,
                        'IsKhoaHocXeTap' => 0,
                    ]);
                    $savedXe++;
                }
            }
        });

        return ['gv' => $savedGv, 'xe' => $savedXe, 'skipped' => $skipped];
    }

    /** @return array{MaMonHoc: ?int, TenMonHoc: string} */
    private function defaultMonHoc(): array
    {
        $defaultMon = DmMonHoc::active()
            ->where(function ($q) {
                $q->where('TenMH', self::TEN_MON_HOC)
                    ->orWhere('TenMH', 'like', '%Thực hành lái xe%');
            })
            ->orderBy('MaMH')
            ->first(['MaMH', 'TenMH']);

        $ma = LichGvMonHoc::normalizeMa($defaultMon->MaMH ?? null);

        return [
            'MaMonHoc' => $ma,
            'TenMonHoc' => (string) ($defaultMon->TenMH ?? self::TEN_MON_HOC),
        ];
    }
}
