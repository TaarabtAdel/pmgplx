<?php

namespace App\Support\PMGPLX;

use App\Models\DaoTao\HocVienThongTinPhu;
use App\Support\OCR\DoThongTinGiayTo;
use Illuminate\Support\Collection;

final class DanhSachHocVienExcelRows
{
  /**
   * @param  list<string>  $keys
   * @param  Collection<int, object>  $rows
   * @return list<array<string, mixed>>
   */
  public static function build(array $keys, Collection $rows): array
  {
    $maDks = $rows->pluck('MaDK')->map(fn ($m) => (string) $m)->all();
    $phu = HocVienThongTinPhu::query()
      ->whereIn('MaDK', $maDks ?: [''])
      ->get()
      ->keyBy(fn (HocVienThongTinPhu $r) => (string) $r->MaDK);

    $dvhcMap = DmDvhcTenDayDu::mapForHocVien($rows);

    $out = [];
    $stt = 0;
    foreach ($rows as $row) {
      $stt++;
      $maDk = (string) $row->MaDK;
      $hoTen = $row->HoVaTen ?: trim(($row->HoDemNLX ?? '').' '.($row->TenNLX ?? ''));
      $diaChi = DmDvhcTenDayDu::diaChiTuNguoiLx($row, $dvhcMap);
      $p = $phu[$maDk] ?? null;

      $values = [
        'stt' => $stt,
        'ma_dk' => $maDk,
        'ho_ten' => $hoTen,
        'ngay_sinh' => NgayVn::format($row->NgaySinh, 'yyyymmdd'),
        'gioi_tinh' => self::gioiTinh($row->GioiTinh),
        'cccd' => (string) ($row->SoCMT ?? ''),
        'ngay_cap' => DoThongTinGiayTo::ngayDatetime($row->NgayCapCMT),
        'noi_ct' => $diaChi['cu_tru'],
        'noi_tt' => $diaChi['thuong_tru'],
        'sdt' => $p?->SoDienThoai ?? '',
        'sale' => $p?->Sale ?? '',
        'ma_khoa_hoc' => (string) ($row->MaKhoaHoc ?? ''),
        'so_ho_so' => (string) ($row->SoHoSo ?? ''),
        'hang_gplx' => (string) ($row->HangGPLX ?? ''),
        'hang_dao_tao' => (string) ($row->HangDaoTao ?? ''),
        'trang_thai' => (string) $row->TrangThai === '1' ? 'Hiệu lực' : 'Không hiệu lực',
      ];

      $line = [];
      foreach ($keys as $key) {
        if (isset($values[$key])) {
          $line[$key] = $values[$key];
        } elseif (str_starts_with($key, 'NguoiLX.')) {
          $col = substr($key, 8);
          $alias = 'NguoiLX.'.$col;
          $line[$key] = $row->{$alias} ?? $row->{$col} ?? null;
        } elseif (str_starts_with($key, 'NguoiLX_HoSo.')) {
          $col = substr($key, 13);
          $alias = 'NguoiLX_HoSo.'.$col;
          $line[$key] = $row->{$alias} ?? $row->{$col} ?? null;
        } else {
          $line[$key] = '';
        }
      }
      $out[] = $line;
    }

    return $out;
  }

  private static function gioiTinh(mixed $gt): string
  {
    if ($gt === '1' || strtoupper((string) $gt) === 'M') {
      return 'Nam';
    }
    if ($gt === '0' || strtoupper((string) $gt) === 'F') {
      return 'Nữ';
    }

    return (string) $gt;
  }
}
