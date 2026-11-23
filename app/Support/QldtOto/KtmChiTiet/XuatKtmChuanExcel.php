<?php

namespace App\Support\QldtOto\KtmChiTiet;

use App\Support\QldtOto\BaoCaoDangKyKhoaHoc;
use App\Support\QldtOto\KtmBaoCao\KtmBaoCaoThamSo;
use App\Support\QldtOto\KtmBaoCao\KtmCotBaSo;
use App\Support\QldtOto\KtmBaoCao\KtmKyThiDuLieu;
use App\Support\QldtOto\KtmBaoCao\KtmTongHop;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Xuất theo mẫu đã tách từ `file-chuan-ktm-mau.xls` (lệnh `php artisan qldt-oto:tach-mau-chuan`).
 * Mỗi loại một file .xls — giữ format sheet gốc, không load workbook 28 sheet.
 */
final class XuatKtmChuanExcel
{
    /** Bản gốc nhiều sheet — chỉ để tách mẫu, không dùng khi xuất. */
    public const MAU_DAY_DU = 'templates/oto/ktm-bao-cao/file-chuan-ktm-mau.xls';

    public static function xuatBcChung(KtmKyThiDuLieu $dl, KtmBaoCaoThamSo $ts, string $outputPath): void
    {
        $sheet = self::sheet('BC CHUNG');
        try {
            $ngay = Carbon::parse($ts->bcChung['ngay_lap'] ?? $dl->ngayThi);
            $sheet->setCellValue('M4', 'Quảng Trị, '.BaoCaoDangKyKhoaHoc::dongNgayThangNam($ngay));
            $sheet->setCellValue('A8', '          1.	Trung tâm sát hạch: '.($ts->bcChung['sat_hach'] ?? ''));
            $sheet->setCellValue('A9', '          2.	Ngày sát hạch: '.($ts->bcChung['ngay_thi'] ?? $dl->ngayThi));

            $hang = $dl->tongHopTheoHang ?? [];
            $rows = [
                13 => KtmChiTietGopDuLieu::tongHopHang($hang, 'b01'),
                14 => KtmChiTietGopDuLieu::tongHopHang($hang, 'b'),
                15 => KtmChiTietGopDuLieu::tongHopHang($hang, 'c1'),
                16 => KtmChiTietGopDuLieu::tongHopHang($hang, 'c1_tcn'),
                17 => KtmChiTietGopDuLieu::tongHopHang($hang, 'qua_han'),
                18 => KtmChiTietGopDuLieu::tongHopHang($hang, 'cong'),
            ];
            foreach ($rows as $row => $g) {
                self::dienBcChung($sheet, $row, $g);
            }

            $sheet->setCellValue('K26', $ts->bcChung['ky_giam_doc'] ?? '');

            self::luu($sheet, $outputPath);
        } finally {
            $sheet->getParent()?->disconnectWorksheets();
        }
    }

    public static function xuatBbth(KtmKyThiDuLieu $dl, KtmBaoCaoThamSo $ts, string $outputPath): void
    {
        $sheet = self::sheet('BBTH');
        try {
            self::dienBbHopBbthChung($sheet, $dl, $ts, false);
            self::luu($sheet, $outputPath);
        } finally {
            $sheet->getParent()?->disconnectWorksheets();
        }
    }

    public static function xuatBbHop(KtmKyThiDuLieu $dl, KtmBaoCaoThamSo $ts, string $outputPath): void
    {
        $sheet = self::sheet('bb họp');
        try {
            $tieuDe = trim((string) ($ts->bbth['tieu_de_hop'] ?? ''));
            if ($tieuDe !== '') {
                $sheet->setCellValue('A6', $tieuDe);
            }
            self::dienBbHopBbthChung($sheet, $dl, $ts, true);
            self::luu($sheet, $outputPath);
        } finally {
            $sheet->getParent()?->disconnectWorksheets();
        }
    }

    private static function dienBbHopBbthChung(Worksheet $sheet, KtmKyThiDuLieu $dl, KtmBaoCaoThamSo $ts, bool $bbHop): void
    {
        $doan = $bbHop
            ? ($ts->bbth['doan_mo_dau_hop'] ?? '')
            : ($ts->bbth['doan_mo_dau_bbth'] ?? '');
        if ($doan === '') {
            $doan = $ts->doanVanBbthA8(count($dl->thiSinh));
        }
        $sheet->setCellValue('A8', $doan);

        self::dienHoiDong($sheet, $ts);

        $hang = $dl->tongHopTheoHang ?? [];
        $cong = KtmChiTietGopDuLieu::tongHopHang($hang, 'cong');
        $sheet->setCellValue('I16', $cong->hoSo);
        $sheet->setCellValue('O16', max(0, $cong->hoSo - $cong->duThi));
        $sheet->setCellValue('R17', $cong->duThi);

        $map = [
            22 => 'b01',
            23 => 'b',
            24 => 'c1',
            25 => 'c1_tcn',
            26 => 'qua_han',
            27 => 'b01_thi_lai',
            38 => 'cong',
        ];
        foreach ($map as $row => $key) {
            self::dienBbthHang($sheet, $row, KtmChiTietGopDuLieu::tongHopHang($hang, $key));
        }

        $tyLe = $cong->tyLeDat($ts->tyLeMauSo());
        $sheet->setCellValue('B40', '- Như vậy tổng số thí sinh đạt các nội dung kỳ kiểm tra nêu trên là: '
            .$cong->ketQuaDat.' đạt tỷ lệ: '.number_format($tyLe, 1, ',', '').'%');

        $nhanXet = $ts->bbth['nhan_xet'] ?? [];
        foreach (range(41, 45) as $i => $row) {
            if (isset($nhanXet[$i]) && $nhanXet[$i] !== '') {
                $sheet->setCellValue('B'.$row, $nhanXet[$i]);
            }
        }
    }

    private static function dienHoiDong(Worksheet $sheet, KtmBaoCaoThamSo $ts): void
    {
        $lines = $ts->bbth['thanh_vien'] ?? [];
        for ($i = 0; $i < 5; $i++) {
            $line = trim((string) ($lines[$i] ?? ''));
            if ($line === '') {
                continue;
            }
            $sheet->setCellValue('B'.(9 + $i), $line);
        }
    }

    private static function dienBcChung(Worksheet $sheet, int $row, KtmTongHop $g): void
    {
        $sheet->setCellValue('F'.$row, $g->hoSo);
        $sheet->setCellValue('G'.$row, $g->duThi);
        self::baSo($sheet, 'H', $row, $g->lt);
        self::baSo($sheet, 'K', $row, $g->h);
        self::baSo($sheet, 'N', $row, $g->duong);
        $sheet->setCellValue('Q'.$row, $g->ketQuaDat);
        $mau = $g->duThi > 0 ? round($g->ketQuaDat / $g->duThi, 4) : 0;
        $sheet->setCellValue('R'.$row, $mau);
    }

    private static function dienBbthHang(Worksheet $sheet, int $row, KtmTongHop $g): void
    {
        $sheet->setCellValue('E'.$row, $g->hoSo);
        $sheet->setCellValue('F'.$row, $g->duThi);
        self::bonCotMon($sheet, $row, 'G', $g->lt, $g->hoSo);
        self::bonCotMon($sheet, $row, 'K', $g->mp, $g->hoSo);
        self::bonCotMon($sheet, $row, 'O', $g->h, $g->hoSo);
        self::bonCotMon($sheet, $row, 'S', $g->cc, $g->hoSo);
    }

    /** Đăng ký (cả nhóm) / tổng / đạt / rớt (cột G–J …). */
    private static function bonCotMon(Worksheet $sheet, int $row, string $colDk, KtmCotBaSo $c, int $hoSoNhom): void
    {
        $o = ord($colDk);
        $sheet->setCellValue(chr($o).$row, $hoSoNhom > 0 ? $hoSoNhom : '');
        $sheet->setCellValue(chr($o + 1).$row, $c->tong > 0 ? $c->tong : ($hoSoNhom > 0 ? 0 : ''));
        $sheet->setCellValue(chr($o + 2).$row, $c->dat);
        $sheet->setCellValue(chr($o + 3).$row, $c->rot);
    }

    private static function baSo(Worksheet $sheet, string $colTong, int $row, KtmCotBaSo $c): void
    {
        $o = ord($colTong);
        $sheet->setCellValue(chr($o).$row, $c->tong);
        $sheet->setCellValue(chr($o + 1).$row, $c->dat);
        $sheet->setCellValue(chr($o + 2).$row, $c->rot);
    }

    private static function sheet(string $tenSheet): Worksheet
    {
        $tenFile = KtmTachMauChuan::SHEET_RA_FILE[$tenSheet] ?? null;
        if ($tenFile === null) {
            throw new \RuntimeException('Chưa cấu hình mẫu cho sheet «'.$tenSheet.'».');
        }
        $path = resource_path('templates/oto/ktm-bao-cao/'.$tenFile);
        if (! is_file($path)) {
            throw new \RuntimeException(
                'Chưa có mẫu «'.$tenFile.'». Chạy: php artisan qldt-oto:tach-mau-chuan (từ file-chuan-ktm-mau.xls).'
            );
        }

        $reader = IOFactory::createReader('Xls');
        $reader->setReadDataOnly(false);
        $reader->setIncludeCharts(false);

        $book = $reader->load($path);
        $sheet = $book->getSheetByName($tenSheet) ?? $book->getActiveSheet();
        if ($sheet->getTitle() !== $tenSheet && ! self::tenSheetKhop($sheet->getTitle(), $tenSheet)) {
            throw new \RuntimeException('Mẫu thiếu sheet «'.$tenSheet.'».');
        }

        return $sheet;
    }

    private static function tenSheetKhop(string $co, string $can): bool
    {
        return mb_strtolower(trim($co)) === mb_strtolower(trim($can));
    }

    private static function luu(Worksheet $sheet, string $outputPath): void
    {
        @mkdir(dirname($outputPath), 0755, true);
        $book = $sheet->getParent();
        $writer = IOFactory::createWriter($book, 'Xls');
        $writer->save($outputPath);
    }
}