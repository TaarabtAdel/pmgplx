<?php

namespace App\Support\QldtOto\KtmBaoCao;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class KtmDocFileNhap
{
  public static function doc(string $path): KtmKyThiDuLieu
  {
    $spreadsheet = IOFactory::load($path);
    try {
    $dsSh = $spreadsheet->getSheetByName('DS SH');
    $sheet1 = $spreadsheet->getSheetByName('Sheet1');
    if ($dsSh === null || $sheet1 === null) {
      throw new \InvalidArgumentException('File nhập thiếu sheet «DS SH» hoặc «Sheet1».');
    }

    $tenKhoa = self::cellStr($dsSh, 'C7');
    $tongHoSo = (int) self::cellStr($dsSh, 'G7');
    $ngayThi = self::cellStr($dsSh, 'C8');

    $dsShList = self::docDsSh($dsSh);
    $sheet1Map = self::docSheet1($sheet1);

    $thiSinh = [];
    $canhBao = [];
    foreach ($dsShList as $row) {
      $cccd = $row['so_dinh_danh'];
      $s1 = $sheet1Map[$cccd] ?? null;
      if ($s1 === null) {
        $canhBao[] = 'Sheet1 không có dòng cho Số định danh «'.$cccd.'» ('.$row['ho_ten'].').';
        continue;
      }
      $thiSinh[] = self::ketHopThiSinh($row, $s1);
    }

    foreach (array_keys($sheet1Map) as $cccd) {
      $found = false;
      foreach ($dsShList as $r) {
        if ($r['so_dinh_danh'] === $cccd) {
          $found = true;
          break;
        }
      }
      if (! $found) {
        $canhBao[] = 'Sheet1 có Số định danh «'.$cccd.'» nhưng không có trong DS SH.';
      }
    }

    $tongHopB = KtmTinhToan::tongHopNhom('B TTML', $thiSinh);
    $tongHopKhac = KtmTinhToan::tongHopNhomRong('B01');

    if ($tongHoSo > 0 && $tongHoSo !== count($thiSinh)) {
      $canhBao[] = 'G7 ('.$tongHoSo.') khác số thí sinh trong DS SH ('.count($thiSinh).').';
    }

    return new KtmKyThiDuLieu(
      tenKhoa: $tenKhoa,
      ngayThi: $ngayThi,
      tongHoSo: count($thiSinh) ?: $tongHoSo,
      g7TuFile: $tongHoSo,
      thiSinh: $thiSinh,
      tongHopBTTml: $tongHopB,
      tongHopKhac: $tongHopKhac,
      canhBao: $canhBao,
    );
    } finally {
      $spreadsheet->disconnectWorksheets();
    }
  }

  /**
   * @return list<array{stt: int, sbd: string, ho_ten: string, so_dinh_danh: string, ngay_sinh: string, hang: string, ghi_chu: string}>
   */
  private static function docDsSh(Worksheet $sheet): array
  {
    $out = [];
    for ($r = 11; $r <= 500; $r++) {
      $hoTen = self::cellStr($sheet, 'C'.$r);
      if ($hoTen === '' || $hoTen === '0' || mb_stripos($hoTen, 'Ủy Viên') !== false) {
        if ($out !== []) {
          break;
        }

        continue;
      }
      if (preg_match('/^(Chủ tịch|Thư ký|Ủy)/iu', $hoTen)) {
        break;
      }
      $stt = (int) self::cellStr($sheet, 'A'.$r);
      if ($stt <= 0) {
        break;
      }
      $out[] = [
        'stt' => $stt,
        'sbd' => self::cellStr($sheet, 'B'.$r),
        'ho_ten' => $hoTen,
        'so_dinh_danh' => self::chuoiCccd(self::cellStr($sheet, 'D'.$r)),
        'ngay_sinh' => self::cellStr($sheet, 'E'.$r),
        'hang' => self::cellStr($sheet, 'G'.$r),
        'ghi_chu' => self::cellStr($sheet, 'H'.$r),
      ];
    }

    return $out;
  }

  /**
   * @return array<string, array<string, mixed>>
   */
  private static function docSheet1(Worksheet $sheet): array
  {
    $out = [];
    for ($r = 6; $r <= 1100; $r++) {
      $cccd = self::chuoiCccd(self::cellStr($sheet, 'D'.$r));
      if ($cccd === '') {
        continue;
      }
      $out[$cccd] = [
        'sbd' => self::cellStr($sheet, 'A'.$r),
        'ho_ten' => self::cellStr($sheet, 'B'.$r),
        'ngay_sinh' => self::cellStr($sheet, 'C'.$r),
        'co_mat' => (int) self::cellStr($sheet, 'G'.$r) === 1,
        'lt' => self::cellStr($sheet, 'W'.$r),
        'mp' => self::cellStr($sheet, 'X'.$r),
        'h' => self::cellStr($sheet, 'Y'.$r),
        'duong' => self::cellStr($sheet, 'Z'.$r),
        'tong_hop' => self::cellStr($sheet, 'AA'.$r),
        'ghi_chu' => self::cellStr($sheet, 'U'.$r),
      ];
    }

    return $out;
  }

  /**
   * @param  array<string, mixed>  $dsSh
   * @param  array<string, mixed>  $s1
   */
  private static function ketHopThiSinh(array $dsSh, array $s1): KtmThiSinh
  {
    $ghiChu = $dsSh['ghi_chu'] !== '' ? $dsSh['ghi_chu'] : (string) ($s1['ghi_chu'] ?? '');
    $phaiThi = GhiChuKtm::phaiThi($ghiChu);
    $coMat = (bool) $s1['co_mat'];

    $lt = self::ketQuaCot($phaiThi['lt'], $coMat, (string) $s1['lt'], 'LT');
    $mp = self::ketQuaCot($phaiThi['mp'], $coMat, (string) $s1['mp'], 'MP');
    $h = self::ketQuaCot($phaiThi['h'], $coMat, (string) $s1['h'], 'H');
    $duongRaw = (string) $s1['duong'];
    $rotH = self::laRot($h);
    if ($phaiThi['duong'] && $coMat && $rotH) {
      $duong = '';
    } else {
      $duong = self::ketQuaCot($phaiThi['duong'], $coMat, $duongRaw, 'Đ');
    }
    $cc = self::ketQuaCc($phaiThi['cc'], $coMat);

    $tongHop = (string) $s1['tong_hop'];
    if ($tongHop === '' && $coMat) {
      $tongHop = self::tinhTongHop($phaiThi, $lt, $mp, $h, $cc, $duong);
    }
    if (! $coMat) {
      $tongHop = $tongHop !== '' ? $tongHop : 'KĐ';
    }

    return new KtmThiSinh(
      stt: $dsSh['stt'],
      sbd: $dsSh['sbd'] !== '' ? $dsSh['sbd'] : (string) $s1['sbd'],
      hoTen: $dsSh['ho_ten'],
      soDinhDanh: $dsSh['so_dinh_danh'],
      ngaySinh: $dsSh['ngay_sinh'] !== '' ? $dsSh['ngay_sinh'] : (string) $s1['ngay_sinh'],
      hang: $dsSh['hang'],
      ghiChu: $ghiChu,
      phaiThi: $phaiThi,
      coMat: $coMat,
      lt: $lt,
      mp: $mp,
      h: $h,
      cc: $cc,
      duong: $duong,
      tongHop: $tongHop,
      lanShLt: self::lanSh($lt),
      lanShMp: self::lanSh($mp),
      lanShH: self::lanSh($h),
      lanShCc: self::lanSh($cc),
      lanShDuong: self::lanSh($duong),
    );
  }

  private static function ketQuaCc(bool $phaiThi, bool $coMat): string
  {
    if (! $phaiThi) {
      return '';
    }
    if (! $coMat) {
      return 'VCC';
    }

    return 'Đ';
  }

  private static function ketQuaCot(bool $phaiThi, bool $coMat, string $raw, string $maVang): string
  {
    if (! $phaiThi) {
      return '';
    }
    $raw = trim($raw);
    if ($raw === '0') {
      $raw = '';
    }
    if (! $coMat && $raw === '') {
      return 'V'.$maVang;
    }

    return $raw;
  }

  /**
   * @param  array{lt: bool, mp: bool, h: bool, duong: bool, cc: bool}  $phaiThi
   */
  private static function tinhTongHop(array $phaiThi, string $lt, string $mp, string $h, string $cc, string $duong): string
  {
    foreach ([
      $phaiThi['lt'] ? $lt : 'Đ',
      $phaiThi['mp'] ? $mp : 'Đ',
      $phaiThi['h'] ? $h : 'Đ',
      $phaiThi['cc'] ? $cc : 'Đ',
      $phaiThi['duong'] ? $duong : 'Đ',
    ] as $kq) {
      if ($kq === '' || str_starts_with($kq, 'V') || $kq === 'KĐ') {
        return 'KĐ';
      }
    }

    return 'Đ';
  }

  private static function lanSh(string $kq): int
  {
    if ($kq === 'Đ' || $kq === 'KĐ') {
      return 1;
    }

    return 0;
  }

  private static function laRot(string $kq): bool
  {
    return $kq === 'KĐ';
  }

  private static function cellStr(Worksheet $sheet, string $addr): string
  {
    $v = $sheet->getCell($addr)->getCalculatedValue();

    return trim((string) ($v ?? ''));
  }

  private static function chuoiCccd(string $v): string
  {
    $v = preg_replace('/\s+/u', '', $v) ?? '';

    return $v;
  }
}