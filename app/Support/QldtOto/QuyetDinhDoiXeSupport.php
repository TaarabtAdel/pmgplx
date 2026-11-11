<?php

namespace App\Support\QldtOto;

use App\Models\PMGPLX\KhoaHoc;

final class QuyetDinhDoiXeSupport
{
    /** @see QuyetDinhDoiGiaoVienSupport::tenKhoaNgan */
    public static function tenKhoaNgan(KhoaHoc $khoa): string
    {
        return QuyetDinhDoiGiaoVienSupport::tenKhoaNgan($khoa);
    }

    /**
     * @return list<array{gv: string, gv_ten: string, xe_cu: string, tu_cu: string, den_cu: string}>
     */
    public static function goiYDong(KhoaHoc $khoa): array
    {
        $rows = [];
        foreach (QuyetDinhDoiGiaoVienSupport::goiYDong($khoa) as $r) {
            $rows[] = [
                'gv' => (string) ($r['gv_cu'] ?? ''),
                'gv_ten' => (string) ($r['gv_cu_ten'] ?? ''),
                'xe_cu' => (string) ($r['xe'] ?? ''),
                'tu_cu' => (string) ($r['tu_cu'] ?? ''),
                'den_cu' => (string) ($r['den_cu'] ?? ''),
            ];
        }

        return $rows;
    }

    public static function hoTenGiaoVien(string $maGv): string
    {
        return QuyetDinhDoiGiaoVienSupport::hoTenGiaoVien($maGv);
    }

    public static function ngay(string $value): string
    {
        return QuyetDinhDoiGiaoVienSupport::ngay($value);
    }

    /**
     * @param  array{gv_ten: string, xe_cu: string, tu_cu: string, den_cu: string, xe_moi: string, tu_moi: string, den_moi: string}  $row
     */
    public static function cauDieuChinh(string $tenKhoa, array $row): string
    {
        $xeCu = QuyetDinhDieuDongXeSupport::bienSoHienThi($row['xe_cu']);
        $xeMoi = QuyetDinhDieuDongXeSupport::bienSoHienThi($row['xe_moi']);

        return sprintf(
            'Giáo viên %s giảng dạy khóa %s sử dụng xe %s từ ngày %s đến hết ngày %s; '
            .'Từ ngày %s đến hết ngày %s sử dụng xe %s giảng dạy.',
            $row['gv_ten'],
            $tenKhoa,
            $xeCu,
            self::ngay($row['tu_cu']),
            self::ngay($row['den_cu']),
            self::ngay($row['tu_moi']),
            self::ngay($row['den_moi']),
            $xeMoi
        );
    }
}
