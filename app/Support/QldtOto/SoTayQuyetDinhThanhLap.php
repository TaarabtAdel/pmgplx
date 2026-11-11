<?php

namespace App\Support\QldtOto;

use Carbon\Carbon;

/** Ghép dòng «Quyết định thành lập lớp» trên sổ tay GV — {số}/QĐ-TTML ngày dd tháng mm năm yyyy. */
final class SoTayQuyetDinhThanhLap
{
    public static function soVaHauTo(?string $soQuyetDinh): string
    {
        $so = trim((string) $soQuyetDinh);

        return $so === '' ? '' : $so.QuyetDinhDieuDongXeSupport::HAU_TO_SO_QD;
    }

    public static function chuoiMucI(?string $soQuyetDinh, ?string $ngayYmd): string
    {
        $out = self::soVaHauTo($soQuyetDinh);
        if ($out === '') {
            return '';
        }
        $ngay = trim((string) $ngayYmd);
        if ($ngay !== '') {
            try {
                $out .= ' ngày '.BaoCaoDangKyKhoaHoc::dongNgayThangNam(Carbon::parse($ngay));
            } catch (\Throwable) {
                // bỏ phần ngày nếu không parse được
            }
        }

        return $out;
    }
}
