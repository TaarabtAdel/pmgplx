<?php

namespace App\Support\QldtOto\KtmBaoCao;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class KtmXuatExcel
{
  private const TPL = 'templates/oto/ktm-bao-cao/';

  public static function xuatBbth(KtmKyThiDuLieu $dl, KtmBaoCaoThamSo $ts, string $outputPath): void
  {
    $spreadsheet = IOFactory::load(resource_path(self::TPL.'MAU_BBTH_bien_ban_tong_hop.xlsx'));
    $sheet = $spreadsheet->getSheetByName('BBTH') ?? $spreadsheet->getActiveSheet();

    $sheet->setCellValue('I4', $ts->dongNgayBbth());
    $sheet->setCellValue('A8', $ts->doanVanBbthA8(count($dl->thiSinh)));
    self::dienHoiDongBbth($sheet, $ts);

    $tongHs = $dl->tongHopBTTml->hoSo + $dl->tongHopKhac->hoSo;
    $tongDt = $dl->tongHopBTTml->duThi + $dl->tongHopKhac->duThi;
    $sheet->setCellValue('H16', $tongHs);
    $sheet->setCellValue('H17', $tongDt);
    $sheet->setCellValue('O16', max(0, $tongHs - $tongDt));

    $congA = KtmTinhToan::tongCong($dl->tongHopBTTml, $dl->tongHopKhac);
    self::dienHangTongHop($sheet, 24, $dl->tongHopBTTml);
    self::dienHangTongHop($sheet, 25, $dl->tongHopKhac);
    self::dienHangTongHop($sheet, 26, $congA);

    foreach ([30, 31, 32, 33, 34] as $row) {
      self::dienHangTongHop($sheet, $row, KtmTinhToan::tongHopNhomRong(''));
    }
    self::dienHangTongHop($sheet, 36, KtmTinhToan::tongHopNhomRong('Cộng'));

    $tongDat = $congA->ketQuaDat;
    $tyLe = $congA->tyLeDat($ts->tyLeMauSo());
    $sheet->setCellValue('B39', '- Như vậy tổng số thí sinh đạt các nội dung kỳ kiểm tra nêu trên là: '
      .$tongDat.' đạt tỷ lệ: '.number_format($tyLe, 1, ',', '').'%');

    $nhanXet = $ts->bbth['nhan_xet'] ?? [];
    foreach (range(40, 44) as $i => $row) {
      if (isset($nhanXet[$i]) && $nhanXet[$i] !== '') {
        $sheet->setCellValue('B'.$row, $nhanXet[$i]);
      }
    }

    $sheet->setCellValue('B51', $ts->bbth['ky_uy_vien'] ?? '');
    $sheet->setCellValue('F51', $ts->bbth['ky_pho_chu_tich'] ?? '');
    $sheet->setCellValue('M51', $ts->bbth['ky_chu_tich'] ?? '');
    $sheet->setCellValue('G58', $ts->bbth['ky_thu_ky'] ?? '');

    $sheet->setCellValue('X38', null);
    $sheet->setCellValue('X39', null);

    self::luu($spreadsheet, $outputPath);
  }

  public static function xuatBcChung(KtmKyThiDuLieu $dl, KtmBaoCaoThamSo $ts, string $outputPath): void
  {
    $spreadsheet = IOFactory::load(resource_path(self::TPL.'MAU_BC_CHUNG_bao_cao_chung.xlsx'));
    $sheet = $spreadsheet->getSheetByName('BC CHUNG') ?? $spreadsheet->getActiveSheet();

    $sheet->setCellValue('O4', $ts->dongNgayBcChung());
    $sheet->setCellValue('A8', '1. Trung tâm sát hạch: '.($ts->bcChung['sat_hach'] ?? ''));
    $sheet->setCellValue('A9', '2. Ngày Kiểm Tra: '.($ts->bcChung['ngay_thi'] ?? ''));

    $congA = KtmTinhToan::tongCong($dl->tongHopBTTml, $dl->tongHopKhac);
    $groups = [
      13 => $dl->tongHopBTTml,
      14 => $dl->tongHopKhac,
      15 => KtmTinhToan::tongHopNhomRong('C1 THI MỚI - TCN'),
      16 => KtmTinhToan::tongHopNhomRong('C1 THI LẠI - TCN'),
      17 => KtmTinhToan::tongHopNhomRong('QUÁ HẠN GPLX HẠNG B & C1'),
      18 => $congA,
    ];
    foreach ($groups as $row => $g) {
      self::dienHangTongHop($sheet, $row, $g, hoSoCol: 'F', duThiCol: 'G', ltCol: 'H');
    }

    $sheet->setCellValue('P27', $ts->bcChung['ky_giam_doc'] ?? '');

    self::luu($spreadsheet, $outputPath);
  }

  public static function xuatBcChiTiet(KtmKyThiDuLieu $dl, KtmBaoCaoThamSo $ts, string $outputPath): void
  {
    $spreadsheet = IOFactory::load(resource_path(self::TPL.'MAU_BC_CHI_TIET_bao_cao_chi_tiet.xlsx'));
    $sheet = $spreadsheet->getSheetByName('BC CHI TIẾT') ?? $spreadsheet->getActiveSheet();

    $sheet->setCellValue('O4', $ts->dongNgayChiTiet());
    $sheet->setCellValue('A8', '1. Trung tâm sát hạch: '.($ts->chiTiet['sat_hach'] ?? ''));
    $sheet->setCellValue('A9', '2. Ngày Kiểm Tra: '.($ts->chiTiet['ngay_thi'] ?? ''));

    $start = 13;
    $templateRows = 34;
    $count = count($dl->thiSinh);
    $delta = $count - $templateRows;

    if ($delta > 0) {
      $sheet->insertNewRowBefore($start + $templateRows, $delta);
      for ($i = 0; $i < $delta; $i++) {
        self::saoChepDinhDangHang($sheet, $start, $start + $templateRows + $i);
      }
    }

    for ($i = 0; $i < $count; $i++) {
      $row = $start + $i;
      $tsinh = $dl->thiSinh[$i];
      $sheet->setCellValue('B'.$row, $tsinh->stt);
      $sheet->setCellValue('C'.$row, $tsinh->hoTen);
      $sheet->setCellValue('D'.$row, $tsinh->ngaySinh);
      $sheet->setCellValue('G'.$row, $tsinh->soDinhDanh);
      $sheet->setCellValue('J'.$row, $tsinh->hang);
      self::dienChiTietMon($sheet, $row, 'M', 'N', $tsinh->lt, $tsinh->lanShLt);
      self::dienChiTietMon($sheet, $row, 'O', 'P', $tsinh->mp, $tsinh->lanShMp);
      self::dienChiTietMon($sheet, $row, 'Q', 'R', $tsinh->h, $tsinh->lanShH);
      self::dienChiTietMon($sheet, $row, 'S', 'T', $tsinh->cc, $tsinh->lanShCc);
      self::dienChiTietMon($sheet, $row, 'U', 'V', $tsinh->duong, $tsinh->lanShDuong);
      $sheet->setCellValue('W'.$row, $tsinh->tongHop);
      foreach (range(24, 34) as $idx) {
        $col = Coordinate::stringFromColumnIndex($idx);
        $sheet->setCellValue($col.$row, null);
      }
    }

    for ($row = $start + $count; $row < $start + $templateRows + max(0, $delta); $row++) {
      foreach (['B', 'C', 'D', 'G', 'J', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W'] as $col) {
        $sheet->setCellValue($col.$row, null);
      }
    }

    $lastData = $start + $count - 1;
    $sheet->setCellValue('O'.($lastData + 1), '        KT. GIÁM ĐỐC');
    $sheet->setCellValue('O'.($lastData + 2), '        P. GIÁM ĐỐC');
    $sheet->setCellValue('P'.($lastData + 6), $ts->chiTiet['ky_giam_doc'] ?? '');

    self::luu($spreadsheet, $outputPath);
  }

  private static function dienChiTietMon(Worksheet $sheet, int $row, string $colKq, string $colLan, string $kq, int $lan): void
  {
    $sheet->setCellValue($colKq.$row, $kq === '' ? 0 : $kq);
    $sheet->setCellValue($colLan.$row, $lan > 0 ? $lan : 0);
  }

  private static function dienHoiDongBbth(Worksheet $sheet, KtmBaoCaoThamSo $ts): void
  {
    $lines = $ts->bbth['thanh_vien'] ?? [];
    for ($i = 0; $i < 4; $i++) {
      $line = trim((string) ($lines[$i] ?? ''));
      if ($line === '') {
        continue;
      }
      $sheet->setCellValue('B'.(9 + $i), $line);
    }
  }

  private static function dienHangTongHop(
    Worksheet $sheet,
    int $row,
    KtmTongHop $g,
    string $hoSoCol = 'E',
    string $duThiCol = 'F',
    string $ltCol = 'G',
  ): void {
    $sheet->setCellValue($hoSoCol.$row, $g->hoSo);
    $sheet->setCellValue($duThiCol.$row, $g->duThi);
    $o = ord($ltCol);
    self::dienBaSo($sheet, chr($o), $row, $g->lt);
    self::dienBaSo($sheet, chr($o + 3), $row, $g->mp);
    self::dienBaSo($sheet, chr($o + 6), $row, $g->h);
    self::dienBaSo($sheet, chr($o + 9), $row, $g->cc);
    self::dienBaSo($sheet, chr($o + 12), $row, $g->duong);
    $sheet->setCellValue('V'.$row, $g->ketQuaDat);
  }

  private static function dienBaSo(Worksheet $sheet, string $colTong, int $row, KtmCotBaSo $c): void
  {
    $sheet->setCellValue($colTong.$row, $c->tong);
    $sheet->setCellValue(chr(ord($colTong) + 1).$row, $c->dat);
    $sheet->setCellValue(chr(ord($colTong) + 2).$row, $c->rot);
  }

  private static function saoChepDinhDangHang(Worksheet $sheet, int $mau, int $dich): void
  {
    foreach (['B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W'] as $col) {
      $sheet->duplicateStyle($sheet->getStyle($col.$mau), $col.$dich);
    }
  }

  private static function luu(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, string $outputPath): void
  {
    @mkdir(dirname($outputPath), 0755, true);
    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save($outputPath);
    $spreadsheet->disconnectWorksheets();
  }
}