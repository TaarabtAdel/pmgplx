<?php

namespace App\Console\Commands;

use App\Models\DaoTao\DatLichThucHanhDuAn;
use App\Models\DaoTao\DatLichThucHanhPhienBan;
use App\Models\PMGPLX\KhoaHoc;
use App\Models\PMGPLX\KhoaHocXeTap;
use App\Support\DaoTao\LichThucHanh\BaiGiang;
use App\Support\DaoTao\LichThucHanh\CauHinhDefaults;
use App\Support\DaoTao\LichThucHanh\LichGenerator;
use App\Support\DaoTao\LichThucHanh\LichThucHanhPmgplxPublisher;
use App\Support\DaoTao\LichThucHanh\LichValidator;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Clone khóa nguồn (B sàn / B.01 tự động) → khóa test, sinh lịch và kiểm tra.
 */
class DatLichThucHanhCloneTestCommand extends Command
{
    protected $signature = 'dat:lich-th-clone-test
        {--nguon=44007K261012 : Mã khóa nguồn}
        {--ma-khoa= : Mã khóa test mới (mặc định suy từ nguồn)}
        {--no-publish : Chỉ sinh + validate, không ghi PMGPLX}';

    protected $description = 'Clone khóa (ngày KG/KT/nghỉ + cặp xe) sang khóa test, sinh lịch và kiểm tra';

    public function handle(): int
    {
        $maNguon = strtoupper(trim((string) $this->option('nguon')));
        $src = KhoaHoc::query()->where('MaKH', $maNguon)->first();
        if ($src === null) {
            $this->error("Không thấy khóa nguồn {$maNguon}.");

            return self::FAILURE;
        }

        $hang = $this->normalizeHang((string) ($src->HangDT ?? 'B'));
        $maTest = strtoupper(trim((string) ($this->option('ma-khoa') ?: $this->defaultMaTest($maNguon))));
        $ngayKg = Carbon::parse($src->NgayKG)->toDateString();
        $ngayKt = Carbon::parse($src->NgayBG)->toDateString();
        $this->info("Nguồn {$maNguon}: KG={$ngayKg} BG={$ngayKt} HangDT={$hang} → test {$maTest}");

        $caps = $this->inferCapXe($maNguon);
        if ($caps === []) {
            $this->error('Không suy được cặp xe/GV từ lịch nguồn.');

            return self::FAILURE;
        }
        $this->info('Cặp xe: '.count($caps));
        foreach ($caps as $cap) {
            $this->line(sprintf(
                '  #%d %s | sáng %s | chiều %s',
                $cap['stt'],
                $cap['bien_so'],
                $cap['gv_sang'],
                $cap['gv_chieu']
            ));
        }

        $xeMin = KhoaHocXeTap::query()
            ->where('MaKH', $maNguon)
            ->where('IsKhoaHocXeTap', 0)
            ->min('NgayBD');
        $xeMax = KhoaHocXeTap::query()
            ->where('MaKH', $maNguon)
            ->where('IsKhoaHocXeTap', 0)
            ->max('NgayKT');
        $thStart = $xeMin ? Carbon::parse($xeMin)->toDateString() : $ngayKg;
        $thEnd = $xeMax ? Carbon::parse($xeMax)->toDateString() : $ngayKt;

        // Lịch TH bắt đầu khi có xe — không nhét cả KG→TH thành NGHỈ trên lưới.
        $nghiCoDinh = $this->nghiTrongKhoangXe($maNguon, $thStart, $thEnd);
        sort($nghiCoDinh);
        $this->info("TH: {$thStart} → {$thEnd}; nghỉ cố định: ".count($nghiCoDinh).' ngày');
        $this->line('  '.implode(', ', $nghiCoDinh));

        $this->cloneKhoaHoc($src, $maTest, $hang, $ngayKg, $ngayKt);

        $capXe = [];
        foreach ($caps as $cap) {
            $capXe[] = array_merge($cap, [
                'so_hoc_vien_gv_sang' => 5,
                'so_hoc_vien_gv_chieu' => 5,
            ]);
        }

        $cauHinh = CauHinhDefaults::merge([
            'ma_khoa' => $maTest,
            'ngay_khai_giang' => $thStart,
            'ngay_ket_thuc_du_kien' => $ngayKt,
            'lich_den_ngay' => $ngayKt,
            'cap_xe' => $capXe,
            'nghi_co_dinh' => $nghiCoDinh,
            'nghi_dinh_ky' => [
                'thu_trong_tuan' => '',
                'tu_ngay' => '',
                'den_ngay' => '',
                'ngoai_le_van_day' => [],
                'nghi_bu' => [],
            ],
        ], $maTest, $hang);

        $duAn = DatLichThucHanhDuAn::query()->where('MaKhoaHoc', $maTest)->first();
        if ($duAn === null) {
            $duAn = new DatLichThucHanhDuAn;
            $duAn->MaKhoaHoc = $maTest;
            $duAn->NgayTao = now();
        }
        $duAn->HangDaoTao = $hang;
        $duAn->NgayKhaiGiang = $ngayKg;
        $duAn->NgayKetThucDuKien = $ngayKt;
        $duAn->setCauHinhArray($cauHinh);
        $duAn->NgayCapNhat = now();
        $duAn->save();
        $this->info("DuAn Id={$duAn->Id} MaKhoaHoc={$maTest} Hang={$hang}");

        $genCauHinh = $cauHinh;
        unset($genCauHinh['ke_hoach_theo_gv'], $genCauHinh['moc_lich']);
        $genCauHinh['dung_ke_hoach_import'] = false;
        $genCauHinh['mau_ke_hoach_sang'] = false;
        $genCauHinh['mau_ke_hoach_chieu'] = false;

        try {
            $result = (new LichGenerator)->generate($genCauHinh);
        } catch (\Throwable $e) {
            $this->error('Sinh lịch thất bại: '.$e->getMessage());

            return self::FAILURE;
        }

        $kiemTra = LichValidator::validate($genCauHinh, $result['lich'], $result['tom_tat']);
        $this->line('Cells: '.count($result['lich']['cells'] ?? []));
        $this->line('GVs: '.count($result['lich']['giao_viens'] ?? []));
        $this->line('KT tính: '.($result['lich']['meta']['ngay_ket_thuc_tinh'] ?? '—'));
        $this->line('Errors: '.json_encode($kiemTra['errors'] ?? [], JSON_UNESCAPED_UNICODE));
        $warnCount = count($kiemTra['warnings'] ?? []);
        $this->line("Warnings: {$warnCount}");
        if ($warnCount > 0 && $warnCount <= 12) {
            $this->line(json_encode($kiemTra['warnings'], JSON_UNESCAPED_UNICODE));
        }

        $this->printMauStats($result['lich']);
        $this->printCabinByGv($result['lich']);
        if ($hang === 'B') {
            $this->printTuDongByGv($result['lich']);
        }

        $payload = [
            'LichJson' => json_encode($result['lich'], JSON_UNESCAPED_UNICODE),
            'TomTatJson' => json_encode($result['tom_tat'], JSON_UNESCAPED_UNICODE),
            'KiemTraJson' => json_encode($kiemTra, JSON_UNESCAPED_UNICODE),
            'OChinhTayJson' => '[]',
            'LaNhap' => false,
            'Ten' => 'Lịch test clone '.$maNguon,
        ];
        $pb = DatLichThucHanhPhienBan::query()->where('DuAnId', $duAn->Id)->orderByDesc('Id')->first();
        if ($pb) {
            $pb->update($payload);
        } else {
            $pb = DatLichThucHanhPhienBan::query()->create(array_merge($payload, [
                'DuAnId' => $duAn->Id,
                'NgayTao' => now(),
            ]));
        }
        DatLichThucHanhPhienBan::query()->where('DuAnId', $duAn->Id)->where('Id', '!=', $pb->Id)->delete();
        $this->info("Đã lưu phiên bản Id={$pb->Id}");

        if (! $this->option('no-publish')) {
            try {
                $pub = (new LichThucHanhPmgplxPublisher)->publish($result['lich'], $genCauHinh);
                $this->info(sprintf('Đã ghi PMGPLX: %d buổi GV, %d buổi xe (skip %d).', $pub['gv'], $pub['xe'], $pub['skipped']));
            } catch (\Throwable $e) {
                $this->warn('Publish PMGPLX lỗi: '.$e->getMessage());
            }
        }

        $ok = ($kiemTra['errors'] ?? []) === [];
        $this->info($ok ? 'TEST PASS (không lỗi validator).' : 'TEST FAIL (có lỗi validator).');
        $this->line('Xem trước: /daotao/phong-dao-tao/dat/lich-thuc-hanh/'.$duAn->Id.'/xem');

        return $ok ? self::SUCCESS : self::FAILURE;
    }

    private function normalizeHang(string $hang): string
    {
        $h = strtoupper(str_replace([' ', '.'], '', trim($hang)));
        if (str_contains($h, 'B01')) {
            return 'B.01';
        }

        return 'B';
    }

    private function defaultMaTest(string $maNguon): string
    {
        if (preg_match('/^(.+)(\d{3})$/', $maNguon, $m)) {
            return $m[1].'T'.$m[2];
        }

        return mb_substr($maNguon, 0, 9).'T01';
    }

    /** @return list<array<string, mixed>> */
    private function inferCapXe(string $maKhoa): array
    {
        $xeMin = KhoaHocXeTap::query()
            ->where('MaKH', $maKhoa)
            ->where('IsKhoaHocXeTap', 0)
            ->min('NgayBD');
        if (! $xeMin) {
            return [];
        }

        $day = Carbon::parse($xeMin)->toDateString();
        $rows = KhoaHocXeTap::query()
            ->where('MaKH', $maKhoa)
            ->where('IsKhoaHocXeTap', 0)
            ->whereDate('NgayBD', $day)
            ->orderBy('BienSoXe')
            ->orderBy('NgayBD')
            ->get(['MaGV', 'TenGV', 'BienSoXe', 'NgayBD']);

        $byBien = [];
        foreach ($rows as $r) {
            $bien = trim((string) $r->BienSoXe);
            $h = (int) Carbon::parse($r->NgayBD)->format('H');
            $byBien[$bien] ??= ['bien_so' => $bien];
            if ($h < 12) {
                $byBien[$bien]['gv_sang'] = trim((string) $r->MaGV);
                $byBien[$bien]['ten_gv_sang'] = trim((string) ($r->TenGV ?? ''));
            } else {
                $byBien[$bien]['gv_chieu'] = trim((string) $r->MaGV);
                $byBien[$bien]['ten_gv_chieu'] = trim((string) ($r->TenGV ?? ''));
            }
        }

        $out = [];
        $stt = 1;
        foreach ($byBien as $cap) {
            if (($cap['gv_sang'] ?? '') === '' || ($cap['gv_chieu'] ?? '') === '') {
                continue;
            }
            $cap['stt'] = $stt++;
            $out[] = $cap;
        }

        return $out;
    }

    /** Ngày không có lịch xe trong khoảng TH nguồn. */
    /** @return list<string> */
    private function nghiTrongKhoangXe(string $maKhoa, string $thStart, string $thEnd): array
    {
        $work = KhoaHocXeTap::query()
            ->where('MaKH', $maKhoa)
            ->where('IsKhoaHocXeTap', 0)
            ->selectRaw('CONVERT(date, NgayBD) as d')
            ->distinct()
            ->pluck('d')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->all();
        $workSet = array_flip($work);
        $out = [];
        $cur = Carbon::parse($thStart);
        $end = Carbon::parse($thEnd);
        while ($cur->lte($end)) {
            $d = $cur->toDateString();
            if (! isset($workSet[$d])) {
                $out[] = $d;
            }
            $cur->addDay();
        }

        return $out;
    }

    private function cloneKhoaHoc(KhoaHoc $src, string $maTest, string $hang, string $ngayKg, string $ngayKt): void
    {
        $attrs = $src->getAttributes();
        unset($attrs[$src->getKeyName()]);
        $attrs['MaKH'] = $maTest;
        // B.01: TenKH chứa B01 để publisher ghi xe tự động; B sàn: không gắn B01.
        $attrs['TenKH'] = $hang === 'B.01'
            ? mb_substr('44007K26B01T'.substr($maTest, -2), 0, 50)
            : mb_substr('44007K26BT'.substr($maTest, -3), 0, 50);
        $attrs['HangDT'] = $hang === 'B.01' ? 'B.01' : 'B';
        $attrs['NgayKG'] = $ngayKg;
        $attrs['NgayBG'] = $ngayKt;
        $attrs['GhiChu'] = mb_substr('Clone '.$src->MaKH, 0, 50);
        $attrs['NguoiSua'] = 'ADMIN';
        $attrs['NgaySua'] = now();

        $existing = KhoaHoc::query()->where('MaKH', $maTest)->first();
        if ($existing) {
            $existing->forceFill($attrs)->save();
            $this->info("Đã cập nhật KhoaHoc {$maTest} (TenKH={$attrs['TenKH']})");
        } else {
            $row = new KhoaHoc;
            $row->forceFill($attrs)->save();
            $this->info("Đã tạo KhoaHoc {$maTest} (TenKH={$attrs['TenKH']})");
        }
    }

    /** @param array<string, mixed> $lich */
    private function printMauStats(array $lich): void
    {
        $counts = [];
        foreach ($lich['cells'] ?? [] as $cell) {
            if (! is_array($cell) || ($cell['nghi_ca_khoa'] ?? false)) {
                continue;
            }
            $mau = (string) ($cell['mau'] ?? '');
            $counts[$mau] = ($counts[$mau] ?? 0) + 1;
        }
        ksort($counts);
        $this->info('Thống kê màu bài: '.json_encode($counts, JSON_UNESCAPED_UNICODE));
    }

    /** @param array<string, mixed> $lich */
    private function printCabinByGv(array $lich): void
    {
        $this->info('Cabin theo GV:');
        foreach ($lich['giao_viens'] ?? [] as $gv) {
            $days = [];
            foreach ($lich['cells'] ?? [] as $cell) {
                if (! is_array($cell) || ($cell['ma_gv'] ?? '') !== ($gv['ma_gv'] ?? '')) {
                    continue;
                }
                if (($cell['mau'] ?? '') !== BaiGiang::CABIN) {
                    continue;
                }
                $days[] = substr((string) ($cell['thoi_gian'] ?? ''), 0, 10);
            }
            $this->line(sprintf(
                '  %s (%s) cabin=%s',
                $gv['ma_gv'] ?? '',
                $gv['ca'] ?? '',
                $days === [] ? '—' : implode(',', $days)
            ));
        }
    }

    /** @param array<string, mixed> $lich */
    private function printTuDongByGv(array $lich): void
    {
        $this->info('Tự động theo GV:');
        foreach ($lich['giao_viens'] ?? [] as $gv) {
            $days = [];
            foreach ($lich['cells'] ?? [] as $cell) {
                if (! is_array($cell) || ($cell['ma_gv'] ?? '') !== ($gv['ma_gv'] ?? '')) {
                    continue;
                }
                if (($cell['mau'] ?? '') !== BaiGiang::TU_DONG) {
                    continue;
                }
                $days[] = substr((string) ($cell['thoi_gian'] ?? ''), 0, 10).' '.($cell['bat_dau'] ?? '');
            }
            $this->line(sprintf(
                '  %s (%s) tu_dong=%s',
                $gv['ma_gv'] ?? '',
                $gv['ca'] ?? '',
                $days === [] ? '—' : implode(' | ', $days)
            ));
        }
    }
}
