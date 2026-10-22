<?php

namespace App\Support\QldtOto;

use App\Models\QldtOto\BaiDay;
use App\Models\QldtOto\LichDuKienThucHanh;
use App\Models\QldtOto\LichDuKienThucHanhBuoi;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Sinh lịch dự kiến thực hành từ cấu hình bài dạy của hạng.
 *
 * Mỗi cặp GV: GV sáng nhận lần lượt các bài của "Hạng X (Sáng)", GV chiều nhận các bài của "Hạng X (Chiều)".
 * Các bài (theo cột Thứ tự) được điền lần lượt vào các slot ngày kể từ Ngày bắt đầu thực hành, mỗi bài một ngày,
 * ngày nghỉ bị bỏ qua: bài A B C, ngày 5 6 7 8, nghỉ ngày 7 → 5 A, 6 B, 8 C.
 *
 * Khối dùng chung tài nguyên xếp nối tiếp giữa các cặp (KHOI): mỗi cặp sau bắt đầu khối sau ngày cuối khối của cặp
 * liền trước ít nhất 1 ngày (rơi vào ngày nghỉ thì sang ngày làm việc kế tiếp).
 *   - Cabin (tên chứa "cabin"): cặp đầu tiên sau "Cabin của cặp trước" ít nhất 1 ngày.
 *   - Tự động (tên bắt đầu "tự động", dùng xe tự động): cặp đầu tiên giữ vị trí tự nhiên.
 * Khối của một cặp chung ngày cho cả sáng và chiều. Khi khối phải lùi: rút khối ra, các bài sau dồn lên, rồi chèn
 * khối vào ngày đã tính, các bài từ ngày đó trở đi lùi theo. Cabin được chen vào giữa các ngày Tự động nếu tới mốc.
 *
 * Sinh lại là xoá các buổi cũ rồi ghi mới.
 */
final class SinhLichDuKienThucHanh
{
    /** Chặn vòng lặp khi số ngày nghỉ bất thường. */
    private const SO_NGAY_TOI_DA = 1000;

    /** Mã khối => nhãn hiển thị. */
    private const KHOI = ['cabin' => 'Cabin', 'tu_dong' => 'Tự động'];

    /** @var list<string> */
    private array $ngayLam = [];

    /** @var array<string, true> */
    private array $nghi = [];

    private CarbonImmutable $batDau;

    /**
     * @return array{so_buoi: int, ngay_ket_thuc: ?string, canh_bao: list<string>, khoi: array<string, array<int, array{0: string, 1: string}>>}
     */
    public function sinh(LichDuKienThucHanh $lich): array
    {
        $lich->loadMissing(['capGvs', 'ngayNghis']);
        $canhBao = [];

        $baiTheoCa = [];
        foreach (array_keys(BaiDay::CA) as $ca) {
            $baiTheoCa[$ca] = BaiDay::query()
                ->with('baiHoc')
                ->where('Hang', $lich->Hang)
                ->where('Ca', $ca)
                ->orderBy('ThuTu')
                ->orderBy('Id')
                ->get()
                ->values();
        }

        $capGvs = $lich->capGvs->sortBy('ThuTu')->values();
        if ($capGvs->isEmpty()) {
            $canhBao[] = 'Chưa có cặp giáo viên nào — chưa sinh được buổi.';
        }
        foreach ($baiTheoCa as $ca => $bais) {
            $coGv = $capGvs->contains(fn ($c) => self::maGv($c, $ca) !== null);
            if ($bais->isEmpty() && $coGv) {
                $canhBao[] = 'Hạng '.$lich->Hang.' ('.BaiDay::CA[$ca].') chưa cấu hình bài dạy — bỏ qua giáo viên '.mb_strtolower(BaiDay::CA[$ca]).'.';
            }
        }

        $this->batDau = CarbonImmutable::parse($lich->NgayBatDauThucHanh)->startOfDay();
        $this->nghi = $lich->ngayNghis->mapWithKeys(fn ($n) => [$n->Ngay->format('Y-m-d') => true])->all();
        $this->ngayLam = [];

        $buois = [];
        $khoiTheoCap = array_fill_keys(array_keys(self::KHOI), []);
        // Ngày cuối khối của cặp liền trước; Cabin của cặp đầu tiên so với "Cabin của cặp trước".
        $cuoiKhoiTruoc = [
            'cabin' => $lich->CabinCapTruocNgay ? CarbonImmutable::parse($lich->CabinCapTruocNgay)->startOfDay() : null,
            'tu_dong' => null,
        ];
        foreach ($capGvs as $cap) {
            $caCoDay = array_values(array_filter(
                array_keys($baiTheoCa),
                fn (string $ca): bool => self::maGv($cap, $ca) !== null && $baiTheoCa[$ca]->isNotEmpty()
            ));

            $min = [];
            foreach (self::KHOI as $khoi => $_) {
                $min[$khoi] = $cuoiKhoiTruoc[$khoi] ? $this->slotTuNgay($cuoiKhoiTruoc[$khoi]->addDay()) : 0;
            }

            // Xếp từng ca; khối lệch ngày giữa sáng/chiều thì nâng mốc lên ngày muộn hơn rồi xếp lại cho khớp.
            $xep = [];
            for ($lan = 0; $lan < 5; $lan++) {
                $xep = [];
                $batDauKhoi = [];
                foreach ($caCoDay as $ca) {
                    $xep[$ca] = $this->xepSlot($baiTheoCa[$ca], $min);
                    foreach ($xep[$ca]['khoi'] as $khoi => [$dau]) {
                        $batDauKhoi[$khoi][] = $dau;
                    }
                }
                $doi = false;
                foreach ($batDauKhoi as $khoi => $ds) {
                    if (count(array_unique($ds)) > 1) {
                        $min[$khoi] = max($ds);
                        $doi = true;
                    }
                }
                if (! $doi) {
                    break;
                }
            }

            foreach (self::KHOI as $khoi => $_) {
                $dau = null;
                $cuoi = null;
                foreach ($xep as $kq) {
                    if (isset($kq['khoi'][$khoi])) {
                        $dau = min($dau ?? PHP_INT_MAX, $kq['khoi'][$khoi][0]);
                        $cuoi = max($cuoi ?? 0, $kq['khoi'][$khoi][1]);
                    }
                }
                if ($dau !== null) {
                    $khoiTheoCap[$khoi][$cap->ThuTu] = [$this->ngayCuaSlot($dau), $this->ngayCuaSlot($cuoi)];
                    $cuoiKhoiTruoc[$khoi] = CarbonImmutable::parse($this->ngayCuaSlot($cuoi));
                }
            }

            foreach ($xep as $ca => $kq) {
                foreach ($kq['slot'] as $slot => [$thuTuBai, $bai]) {
                    $buois[] = [
                        'LichDuKienId' => $lich->Id,
                        'CapThuTu' => $cap->ThuTu,
                        'Ca' => $ca,
                        'MaGv' => self::maGv($cap, $ca),
                        'BienSoXe' => $cap->BienSoXe,
                        'Ngay' => $this->ngayCuaSlot($slot),
                        'NgaySo' => $thuTuBai,
                        'NoiDung' => (string) $bai->baiHoc?->Ten,
                        'GioBatDau' => $bai->baiHoc?->GioBatDau,
                        'GioKetThuc' => $bai->baiHoc?->GioKetThuc,
                        'SoGio' => $bai->SoGio,
                    ];
                }
            }
        }

        $now = Carbon::now();
        DB::connection('sqlsrv_manhlinh')->transaction(function () use ($lich, $buois, $now): void {
            LichDuKienThucHanhBuoi::query()->where('LichDuKienId', $lich->Id)->delete();
            foreach (array_chunk($buois, 150) as $chunk) {
                LichDuKienThucHanhBuoi::query()->insert($chunk);
            }
            $lich->forceFill(['NgaySinhLich' => $now])->save();
        });

        return [
            'so_buoi' => count($buois),
            'ngay_ket_thuc' => $buois === [] ? null : max(array_column($buois, 'Ngay')),
            'canh_bao' => $canhBao,
            'khoi' => $khoiTheoCap,
        ];
    }

    /**
     * Điền bài vào slot theo thứ tự. Khối chưa tới mốc $min thì tạm hoãn, các bài sau dồn lên, tới mốc thì chèn khối
     * vào. Khối Cabin xếp liền; khối Tự động có thể bị Cabin (đã tới mốc) chen vào giữa. Hết bài mà khối vẫn chưa tới
     * mốc thì để trống các slot ở giữa.
     *
     * @param  Collection<int, BaiDay>  $bais
     * @param  array<string, int>  $min
     * @return array{slot: array<int, array{0: int, 1: BaiDay}>, khoi: array<string, array{0: int, 1: int}>}
     */
    private function xepSlot(Collection $bais, array $min): array
    {
        // Gom thành đơn vị: bài thường đứng riêng, các bài cùng khối gộp vào đơn vị của bài khối đầu tiên.
        $donVi = [];
        $viTriKhoi = [];
        foreach ($bais as $i => $bai) {
            $khoi = self::khoiCua($bai);
            if ($khoi === null) {
                $donVi[] = ['khoi' => null, 'bai' => [[$i + 1, $bai]]];
            } elseif (isset($viTriKhoi[$khoi])) {
                $donVi[$viTriKhoi[$khoi]]['bai'][] = [$i + 1, $bai];
            } else {
                $viTriKhoi[$khoi] = count($donVi);
                $donVi[] = ['khoi' => $khoi, 'bai' => [[$i + 1, $bai]]];
            }
        }

        $slot = [];
        $khoiDaXep = [];
        $hoan = [];
        $dangChay = null;
        $s = 0;
        $datMot = function (array $muc, ?string $khoi) use (&$slot, &$khoiDaXep, &$s): void {
            if ($khoi !== null) {
                $khoiDaXep[$khoi] = [$khoiDaXep[$khoi][0] ?? $s, $s];
            }
            $slot[$s++] = $muc;
        };
        $mocCua = fn (array $dv): int => $min[$dv['khoi']] ?? 0;

        // Mỗi vòng điền một slot: khối hoãn đã tới mốc (Cabin) được chen trước, kể cả giữa khối đang chạy (Tự động).
        while ($donVi !== [] || $hoan !== [] || $dangChay !== null) {
            usort($hoan, fn ($a, $b) => $mocCua($a) <=> $mocCua($b));
            if ($hoan !== [] && $mocCua($hoan[0]) <= $s) {
                $dv = array_shift($hoan);
                if ($dv['khoi'] === 'tu_dong' && count($dv['bai']) > 1) {
                    $dangChay = $dv;

                    continue;
                }
                foreach ($dv['bai'] as $muc) {
                    $datMot($muc, $dv['khoi']);
                }

                continue;
            }
            if ($dangChay !== null) {
                $datMot(array_shift($dangChay['bai']), $dangChay['khoi']);
                if ($dangChay['bai'] === []) {
                    $dangChay = null;
                }

                continue;
            }
            if ($donVi === []) {
                $s = $mocCua($hoan[0]);

                continue;
            }
            $dv = array_shift($donVi);
            if ($dv['khoi'] !== null && $mocCua($dv) > $s) {
                $hoan[] = $dv;

                continue;
            }
            if ($dv['khoi'] === 'tu_dong' && count($dv['bai']) > 1) {
                $dangChay = $dv;

                continue;
            }
            foreach ($dv['bai'] as $muc) {
                $datMot($muc, $dv['khoi']);
            }
        }

        return ['slot' => $slot, 'khoi' => $khoiDaXep];
    }

    private static function khoiCua(BaiDay $bai): ?string
    {
        $ten = mb_strtolower(trim((string) $bai->baiHoc?->Ten));
        if (str_contains($ten, 'cabin')) {
            return 'cabin';
        }

        return str_starts_with($ten, 'tự động') ? 'tu_dong' : null;
    }

    private static function maGv(object $cap, string $ca): ?string
    {
        return $ca === 'sang' ? $cap->MaGvSang : $cap->MaGvChieu;
    }

    /** Ngày (Y-m-d) của slot thứ $slot (0-based) trong các ngày làm việc kể từ ngày bắt đầu. */
    private function ngayCuaSlot(int $slot): string
    {
        $this->moRongNgayLam(fn () => count($this->ngayLam) > $slot);

        return $this->ngayLam[$slot];
    }

    /** Slot của ngày làm việc đầu tiên >= $ngay (trước ngày bắt đầu thì là slot 0). */
    private function slotTuNgay(CarbonImmutable $ngay): int
    {
        $ymd = $ngay->format('Y-m-d');
        $this->moRongNgayLam(fn () => $this->ngayLam !== [] && end($this->ngayLam) >= $ymd);
        foreach ($this->ngayLam as $i => $d) {
            if ($d >= $ymd) {
                return $i;
            }
        }

        return count($this->ngayLam) - 1;
    }

    private function moRongNgayLam(callable $du): void
    {
        $ngay = $this->ngayLam === []
            ? $this->batDau
            : CarbonImmutable::parse(end($this->ngayLam))->addDay();
        for ($i = 0; ! $du() && $i < self::SO_NGAY_TOI_DA; $i++, $ngay = $ngay->addDay()) {
            if (! isset($this->nghi[$ngay->format('Y-m-d')])) {
                $this->ngayLam[] = $ngay->format('Y-m-d');
            }
        }
    }
}
