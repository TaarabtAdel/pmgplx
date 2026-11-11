<?php

namespace App\Support\QldtOto;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;

/** Bảng xem trước lịch LT+TH giống file Excel + cột «Gợi ý · nguồn». */
final class LichHocLyThuyetThucHanhPreview
{
  /** @var array<string, string> */
    private const TEMPLATE = [
        LichHocLyThuyetThucHanhLichNgay::HANG_C => 'resources/templates/oto/lich-hoc-ly-thuyet-thuc-hanh-hang-c.xlsx',
        LichHocLyThuyetThucHanhLichNgay::HANG_B_SAN => 'resources/templates/oto/lich-hoc-ly-thuyet-thuc-hanh-hang-b-san.xlsx',
        LichHocLyThuyetThucHanhLichNgay::HANG_B_TU_DONG => 'resources/templates/oto/lich-hoc-ly-thuyet-thuc-hanh-hang-b01.xlsx',
    ];

    /** @var array<string, string> */
    private const SHEET_HINT = [
        LichHocLyThuyetThucHanhLichNgay::HANG_C => 'LỊCH',
        LichHocLyThuyetThucHanhLichNgay::HANG_B_SAN => 'SỐ SÀN',
        LichHocLyThuyetThucHanhLichNgay::HANG_B_TU_DONG => 'TỰU ĐỘNG',
    ];

    /**
     * Ánh xạ hàng Excel → khóa ngày (cột D / E).
     *
     * @var array<int, array{d: ?string, e: ?string}>
     */
    /** Hàng Excel chung C / B sàn / B01 (đến DAT). */
    private const O_NGAY = [
        6 => ['d' => 'ngay_khai_giang', 'e' => 'ngay_kg_plus_17'],
        10 => ['d' => 'ngay_bd_thuc_hanh_tru_1', 'e' => 'ngay_bd_thuc_hanh_tru_1'],
        12 => ['d' => 'ngay_bd_hinh_tren', 'e' => 'ngay_kt_hinh_tren'],
        13 => ['d' => 'ngay_bd_hinh_tren', 'e' => 'ngay_kt_hinh_tren'],
        18 => ['d' => 'ngay_bd_cabin', 'e' => 'ngay_kt_cabin'],
        19 => ['d' => 'ngay_bd_dat', 'e' => 'ngay_kt_dat'],
    ];

    /** Đuôi lịch B sàn / C (hàng 25–29). */
    private const O_NGAY_DUOI = [
        25 => ['d' => 'ngay_bo_sung_dat', 'e' => 'ngay_kt_bo_sung_dat'],
        26 => ['d' => 'ngay_bd_hinh_duoi', 'e' => 'ngay_bd_hinh_duoi'],
        27 => ['d' => 'ngay_bd_hinh_duoi_plus_1', 'e' => 'ngay_kt_hinh_duoi'],
        28 => ['d' => 'ngay_bd_on_luyen', 'e' => 'ngay_kt_on_luyen'],
        29 => ['d' => 'ngay_kiem_tra', 'e' => 'ngay_kiem_tra'],
    ];

    /** B01 thiếu 1 dòng so với B sàn — «dự phòng» ở hàng 24 / STT 19. */
    private const O_NGAY_DUOI_B01 = [
        24 => ['d' => 'ngay_bo_sung_dat', 'e' => 'ngay_bo_sung_dat'],
        25 => ['d' => 'ngay_bd_hinh_duoi', 'e' => 'ngay_bd_hinh_duoi'],
        26 => ['d' => 'ngay_bd_hinh_duoi_plus_1', 'e' => 'ngay_kt_hinh_duoi'],
        27 => ['d' => 'ngay_bd_on_luyen', 'e' => 'ngay_kt_on_luyen'],
        28 => ['d' => 'ngay_kiem_tra', 'e' => 'ngay_kiem_tra'],
    ];

    /**
     * @param  array<string, string|null>  $goiY
     * @param  array<string, string>  $formNgay
     * @param  array<string, string>  $goiYHienThi
     * @return list<array{
     *   row: int,
     *   stt: string,
     *   noi_dung: string,
     *   so_gio: string,
     *   tu_ngay: string,
     *   den_ngay: string,
     *   hinh_thuc: string,
     *   dia_diem: string,
     *   ghi_chu: string,
     *   goi_y_nguon: string,
     *   co_ngay: bool
     * }>
     */
    public static function dongXemTruoc(string $hang, array $goiY, array $formNgay, array $goiYHienThi): array
    {
        $hang = in_array($hang, array_keys(self::TEMPLATE), true)
            ? $hang
            : LichHocLyThuyetThucHanhLichNgay::HANG_C;

        $moc = LichHocLyThuyetThucHanhLichNgay::hopNhatMocNgay($goiY, $formNgay);
        $dongMau = self::docDongTuMau($hang);
        $out = [];
        foreach ($dongMau as $meta) {
            $excelRow = $meta['row'];
            $nd = mb_strtolower($meta['noi_dung']);
            if (str_contains($nd, 'kiểm tra') || str_contains($nd, 'kiem tra')) {
                $map = ['d' => 'ngay_kiem_tra', 'e' => 'ngay_kiem_tra'];
            } elseif (str_contains($nd, 'ôn luyện') || str_contains($nd, 'om luyen') || str_contains($nd, 'ôm luyện')) {
                $map = ['d' => 'ngay_bd_on_luyen', 'e' => 'ngay_kt_on_luyen'];
            } elseif (str_contains($nd, 'dự phòng') || str_contains($nd, 'du phong')) {
                $map = $hang === LichHocLyThuyetThucHanhLichNgay::HANG_B_TU_DONG
                    ? ['d' => 'ngay_bo_sung_dat', 'e' => 'ngay_bo_sung_dat']
                    : ['d' => 'ngay_bo_sung_dat', 'e' => 'ngay_kt_bo_sung_dat'];
            } else {
                $map = self::mapONgay($hang, $excelRow);
            }

            $tu = self::fmt(self::layNgay($moc, $map['d'] ?? null));
            $den = self::fmt(self::layNgay($moc, $map['e'] ?? null));
            $coNgay = ($map['d'] ?? null) !== null || ($map['e'] ?? null) !== null;

            $out[] = [
                'row' => $excelRow,
                'stt' => $meta['stt'],
                'noi_dung' => $meta['noi_dung'],
                'so_gio' => $meta['so_gio'],
                'tu_ngay' => $tu,
                'den_ngay' => $den,
                'hinh_thuc' => $meta['hinh_thuc'],
                'dia_diem' => $meta['dia_diem'],
                'ghi_chu' => $meta['ghi_chu'],
                'goi_y_nguon' => self::goiYNguonDong($map, $goiYHienThi, $moc),
                'co_ngay' => $coNgay,
            ];
        }

        return self::apGopTheoStt($out, $hang);
    }

    /** @var list<array{0: int, 1: int}> */
    private const GOP_STT = [
        [1, 4],
        [5, 6],
        [8, 12],
        [14, 19],
    ];

    /** B01: STT 19 = dự phòng — không gộp vào khối TH 14–18. */
    private const GOP_STT_B01 = [
        [1, 4],
        [5, 6],
        [8, 12],
        [14, 18],
    ];

    /**
     * @return array{d: ?string, e: ?string}
     */
    private static function mapONgay(string $hang, int $excelRow): array
    {
        if ($hang === LichHocLyThuyetThucHanhLichNgay::HANG_B_TU_DONG) {
            return self::O_NGAY[$excelRow]
                ?? self::O_NGAY_DUOI_B01[$excelRow]
                ?? ['d' => null, 'e' => null];
        }

        return self::O_NGAY[$excelRow]
            ?? self::O_NGAY_DUOI[$excelRow]
            ?? ['d' => null, 'e' => null];
    }

    /**
     * Gộp ô giống Excel (cột thời gian / hình thức / địa điểm / ghi chú / gợi ý).
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private static function apGopTheoStt(array $rows, string $hang): array
    {
        $gop = $hang === LichHocLyThuyetThucHanhLichNgay::HANG_B_TU_DONG
            ? self::GOP_STT_B01
            : self::GOP_STT;
        foreach ($gop as [$tu, $den]) {
            $rows = self::apGopKhoangStt($rows, $tu, $den);
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private static function apGopKhoangStt(array $rows, int $tu, int $den): array
    {
        $nhom = [];
        foreach ($rows as $i => $row) {
            $stt = self::sttSo((string) ($row['stt'] ?? ''));
            if ($stt !== null && $stt >= $tu && $stt <= $den) {
                $nhom[] = $i;
            }
        }

        if (count($nhom) < 2) {
            return $rows;
        }

        $dau = $nhom[0];
        $rowspan = count($nhom);
        $rows[$dau]['gop_rowspan'] = $rowspan;
        $rows[$dau]['gop_an_ot'] = false;

        foreach (array_slice($nhom, 1) as $i) {
            $rows[$i]['gop_an_ot'] = true;
            $rows[$i]['gop_rowspan'] = 0;
        }

        return $rows;
    }

    private static function sttSo(string $stt): ?int
    {
        if (preg_match('/^(\d+)/', trim($stt), $m)) {
            return (int) $m[1];
        }

        return null;
    }

    /**
     * @param  array{d: ?string, e: ?string}  $map
     * @param  array<string, Carbon|null>  $moc
     */
    private static function goiYNguonDong(array $map, array $goiYHienThi, array $moc): string
    {
        $phan = [];
        foreach (['d' => 'Từ', 'e' => 'Đến'] as $side => $nhan) {
            $oKey = $map[$side] ?? null;
            if ($oKey === null) {
                continue;
            }
            $gKey = self::oSangGoiYKey($oKey);
            if ($gKey !== null && ($goiYHienThi[$gKey] ?? '') !== '') {
                $phan[] = $nhan.': '.$goiYHienThi[$gKey];
            } elseif ($oKey === 'ngay_kg_plus_17' && ($moc['ngay_khai_giang'] ?? null)) {
                $d = $moc['ngay_khai_giang']->copy()->addDays(17);
                $phan[] = $nhan.': '.LichHocLyThuyetThucHanhLichNgay::dinhDangNgay($d).' · Ngày KG + 17 ngày';
            } elseif ($oKey === 'ngay_bd_thuc_hanh_tru_1' && ($moc['ngay_bd_thuc_hanh'] ?? null)) {
                $d = $moc['ngay_bd_thuc_hanh']->copy()->subDay();
                $phan[] = $nhan.': '.LichHocLyThuyetThucHanhLichNgay::dinhDangNgay($d).' · BD TH − 1 ngày';
            } elseif ($oKey === 'ngay_bd_hinh_duoi_plus_1' && ($moc['ngay_bd_hinh_duoi'] ?? null)) {
                $d = $moc['ngay_bd_hinh_duoi']->copy()->addDay();
                $phan[] = $nhan.': '.LichHocLyThuyetThucHanhLichNgay::dinhDangNgay($d).' · BD HÌNH dưới + 1 ngày';
            } elseif ($oKey === 'ngay_kt_bo_sung_dat' && ($goiYHienThi['ngay_bo_sung_dat'] ?? '') !== '') {
                $phan[] = $nhan.': KT dự phòng DAT · theo mốc bổ sung';
            }
        }

        if ($phan === []) {
            return '—';
        }

        $gop = [];
        foreach ($phan as $p) {
            if (! in_array($p, $gop, true)) {
                $gop[] = $p;
            }
        }

        return implode('<br>', $gop);
    }

    private static function oSangGoiYKey(string $oKey): ?string
    {
        return match ($oKey) {
            'ngay_khai_giang', 'ngay_bd_thuc_hanh', 'ngay_bd_hinh_tren', 'ngay_kt_hinh_tren',
            'ngay_bd_cabin', 'ngay_kt_cabin', 'ngay_bd_dat', 'ngay_kt_dat',
            'ngay_bo_sung_dat', 'ngay_bd_hinh_duoi', 'ngay_kt_hinh_duoi',
            'ngay_bd_on_luyen', 'ngay_kt_on_luyen', 'ngay_kiem_tra' => $oKey,
            'ngay_bd_thuc_hanh_tru_1' => 'ngay_bd_thuc_hanh',
            'ngay_bd_hinh_duoi_plus_1' => 'ngay_bd_hinh_duoi',
            default => null,
        };
    }

    /**
     * @param  array<string, Carbon|null>  $moc
     */
    private static function layNgay(array $moc, ?string $key): ?Carbon
    {
        if ($key === null) {
            return null;
        }

        return match ($key) {
            'ngay_kg_plus_17' => $moc['ngay_khai_giang']?->copy()->addDays(17),
            'ngay_bd_thuc_hanh_tru_1' => $moc['ngay_bd_thuc_hanh']?->copy()->subDay(),
            'ngay_bd_hinh_duoi_plus_1' => $moc['ngay_bd_hinh_duoi']?->copy()->addDay(),
            'ngay_kt_bo_sung_dat' => $moc['ngay_kt_bo_sung_dat'] ?? $moc['ngay_bo_sung_dat']?->copy()->addDay(),
            default => $moc[$key] ?? null,
        };
    }

    private static function fmt(?Carbon $d): string
    {
        return $d ? $d->format('d/m/Y') : '';
    }

    /**
     * @return list<array{row: int, stt: string, noi_dung: string, so_gio: string, hinh_thuc: string, dia_diem: string, ghi_chu: string}>
     */
    private static function docDongTuMau(string $hang): array
    {
        static $cache = [];

        if (isset($cache[$hang])) {
            return $cache[$hang];
        }

        $rel = self::TEMPLATE[$hang];
        $path = base_path($rel);
        if (! is_file($path)) {
            return $cache[$hang] = [];
        }

        $ss = IOFactory::load($path);
        $hint = self::SHEET_HINT[$hang] ?? '';
        $sheet = LichHocLyThuyetThucHanhSheet::chonSheet($ss, $hint);

        $max = $hang === LichHocLyThuyetThucHanhLichNgay::HANG_B_TU_DONG ? 28 : 29;
        $rows = [];
        $thuTu = 0;
        for ($r = 6; $r <= $max; $r++) {
            $noiDung = trim((string) $sheet->getCell('B'.$r)->getValue());
            if ($noiDung === '') {
                continue;
            }
            $thuTu++;
            $stt = self::sttTuCotA($sheet, $r, $thuTu);
            $soGio = $sheet->getCell('C'.$r)->getValue();
            $soGioStr = $soGio !== null && $soGio !== '' ? rtrim(rtrim((string) $soGio, '0'), '.') : '';

            $rows[] = [
                'row' => $r,
                'stt' => $stt,
                'noi_dung' => $noiDung,
                'so_gio' => $soGioStr,
                'hinh_thuc' => trim((string) $sheet->getCell('F'.$r)->getValue()),
                'dia_diem' => trim((string) $sheet->getCell('G'.$r)->getValue()),
                'ghi_chu' => trim((string) $sheet->getCell('H'.$r)->getValue()),
            ];
        }

        $ss->disconnectWorksheets();

        return $cache[$hang] = $rows;
    }

    /** Mẫu B01: cột A là công thức Excel — dùng STT tuần tự khi không đọc được số. */
    private static function sttTuCotA(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $row, int $fallback): string
    {
        $cell = $sheet->getCell('A'.$row);
        $raw = $cell->getValue();
        if (is_string($raw) && str_starts_with(trim($raw), '=')) {
            try {
                $calc = $cell->getCalculatedValue();
                if ($calc !== null && $calc !== '') {
                    $raw = $calc;
                }
            } catch (\Throwable) {
                $raw = null;
            }
        }
        if ($raw === null || (is_string($raw) && str_starts_with(trim((string) $raw), '='))) {
            return (string) $fallback;
        }

        $n = self::sttSo((string) $raw);

        return $n !== null ? (string) $n : (string) $fallback;
    }
}
