<?php

namespace App\Support\PMGPLX;

use Illuminate\Support\Facades\Schema;

/** Cột xuất Excel danh mục học viên — modal chọn cột. */
final class DanhSachHocVienExportColumns
{
  /** @var list<string> */
  public const MAC_DINH = [
    'stt',
    'ma_dk',
    'ho_ten',
    'ngay_sinh',
    'gioi_tinh',
    'cccd',
    'ngay_cap',
    'noi_ct',
    'sdt',
    'sale',
    'ma_khoa_hoc',
  ];

  /**
   * @return list<array{key: string, label: string, uu_tien: bool, mac_dinh: bool}>
   */
  public static function forModal(string $connection = 'sqlsrv'): array
  {
    $uuTien = [
      ['key' => 'stt', 'label' => 'STT', 'uu_tien' => true, 'mac_dinh' => true],
      ['key' => 'ma_dk', 'label' => 'Mã ĐK', 'uu_tien' => true, 'mac_dinh' => true],
      ['key' => 'ho_ten', 'label' => 'Họ và tên', 'uu_tien' => true, 'mac_dinh' => true],
      ['key' => 'ngay_sinh', 'label' => 'Ngày sinh', 'uu_tien' => true, 'mac_dinh' => true],
      ['key' => 'gioi_tinh', 'label' => 'Giới tính', 'uu_tien' => true, 'mac_dinh' => true],
      ['key' => 'cccd', 'label' => 'CCCD', 'uu_tien' => true, 'mac_dinh' => true],
      ['key' => 'ngay_cap', 'label' => 'Ngày cấp', 'uu_tien' => true, 'mac_dinh' => true],
      ['key' => 'noi_ct', 'label' => 'Nơi CT', 'uu_tien' => true, 'mac_dinh' => true],
      ['key' => 'sdt', 'label' => 'SDT', 'uu_tien' => true, 'mac_dinh' => true],
      ['key' => 'sale', 'label' => 'Sale', 'uu_tien' => true, 'mac_dinh' => true],
      ['key' => 'ma_khoa_hoc', 'label' => 'Mã khóa học', 'uu_tien' => true, 'mac_dinh' => true],
      ['key' => 'so_ho_so', 'label' => 'Số hồ sơ', 'uu_tien' => true, 'mac_dinh' => false],
      ['key' => 'noi_tt', 'label' => 'Nơi thường trú', 'uu_tien' => true, 'mac_dinh' => false],
      ['key' => 'hang_gplx', 'label' => 'Hạng GPLX', 'uu_tien' => true, 'mac_dinh' => false],
      ['key' => 'hang_dao_tao', 'label' => 'Hạng đào tạo', 'uu_tien' => true, 'mac_dinh' => false],
      ['key' => 'trang_thai', 'label' => 'Trạng thái', 'uu_tien' => true, 'mac_dinh' => false],
    ];

    $skipN = ['MaDK', 'HoVaTen', 'HoDemNLX', 'TenNLX', 'NgaySinh', 'GioiTinh', 'SoCMT', 'NgayCapCMT', 'NoiCT', 'NoiCT_MaDVHC', 'NoiTT', 'NoiTT_MaDVHC', 'TrangThai'];
    $skipH = ['MaDK', 'MaKhoaHoc', 'SoHoSo', 'HangGPLX', 'HangDaoTao'];

    $extras = [];
    foreach (Schema::connection($connection)->getColumnListing('NguoiLX') as $col) {
      if (in_array($col, $skipN, true)) {
        continue;
      }
      $key = 'NguoiLX.'.$col;
      $extras[$key] = ['key' => $key, 'label' => $key, 'uu_tien' => false, 'mac_dinh' => false];
    }
    foreach (Schema::connection($connection)->getColumnListing('NguoiLX_HoSo') as $col) {
      if (in_array($col, $skipH, true)) {
        continue;
      }
      $key = 'NguoiLX_HoSo.'.$col;
      $extras[$key] = ['key' => $key, 'label' => $key, 'uu_tien' => false, 'mac_dinh' => false];
    }
    ksort($extras);

    return array_merge($uuTien, array_values($extras));
  }

  /**
   * @param  list<string>|null  $keys
   * @return list<string>
   */
  public static function sanitize(?array $keys, string $connection = 'sqlsrv'): array
  {
    if ($keys === null || $keys === []) {
      return self::MAC_DINH;
    }

    $allowed = [];
    foreach (self::forModal($connection) as $c) {
      $allowed[$c['key']] = true;
    }

    $out = [];
    foreach ($keys as $k) {
      $k = (string) $k;
      if (isset($allowed[$k]) && ! in_array($k, $out, true)) {
        $out[] = $k;
      }
    }

    return $out !== [] ? $out : self::MAC_DINH;
  }

  /**
   * @param  list<string>  $keys
   * @return list<string> nhãn cột theo thứ tự keys
   */
  public static function labelsFor(array $keys, string $connection = 'sqlsrv'): array
  {
    $map = [];
    foreach (self::forModal($connection) as $c) {
      $map[$c['key']] = $c['label'];
    }

    $labels = [];
    foreach ($keys as $k) {
      $labels[] = $map[$k] ?? $k;
    }

    return $labels;
  }
}
