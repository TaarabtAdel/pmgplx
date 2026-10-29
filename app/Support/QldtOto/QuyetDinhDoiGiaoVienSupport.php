<?php

namespace App\Support\QldtOto;

use App\Models\PMGPLX\GiaoVien;
use App\Models\PMGPLX\KhoaHoc;
use App\Support\DaoTao\PhanCongTuLichPmgplxQuery;
use Carbon\Carbon;

final class QuyetDinhDoiGiaoVienSupport
{
    /** vd. 44007K26BK56 → BK56, K26B01K39 → B01K39, K26C1K05 → C1K05. */
    public static function tenKhoaNgan(KhoaHoc $khoa): string
    {
        $ten = trim((string) ($khoa->TenKH ?? ''));
        if (preg_match('/((?:B01|B11|C1|B1|B|C)K\d+)\s*$/i', $ten, $m)) {
            return mb_strtoupper($m[1]);
        }

        return $ten !== '' ? $ten : (string) $khoa->MaKH;
    }

    /**
     * Gợi ý dòng đổi GV cho một khoá: GV đang dùng xe theo phân công đào tạo (MANHLINH),
     * không có thì lấy từ lịch xe tập PMGPLX.
     *
     * @return list<array{gv_cu: string, gv_cu_ten: string, xe: string, tu_cu: string, den_cu: string}>
     */
    public static function goiYDong(KhoaHoc $khoa): array
    {
        $rows = [];

        $khoaDt = QuyetDinhDieuDongXeSupport::resolveKhoaDaoTao($khoa);
        if ($khoaDt !== null) {
            foreach (QuyetDinhDieuDongXeSupport::loadXeBlocksFromPhanCong((int) $khoaDt->Id) as $block) {
                foreach ($block['giao_vien'] as $gv) {
                    $rows[] = [
                        'gv_cu' => (string) ($gv['ma_gv'] ?? ''),
                        'gv_cu_ten' => (string) ($gv['ho_ten'] ?? ''),
                        'xe' => (string) $block['bien_so'],
                        'tu_cu' => (string) ($gv['tu_ngay'] ?? ''),
                        'den_cu' => (string) ($gv['den_ngay'] ?? ''),
                    ];
                }
            }
        }

        if ($rows === []) {
            $agg = (new PhanCongTuLichPmgplxQuery())->aggregateByKhoaXe(['ma_kh' => [(string) $khoa->MaKH]]);
            foreach ($agg as $xe) {
                foreach ($xe['giao_viens'] as $gv) {
                    $rows[] = [
                        'gv_cu' => (string) ($gv['ma_gv'] ?? ''),
                        'gv_cu_ten' => (string) ($gv['ho_ten'] ?? ''),
                        'xe' => (string) $xe['bien_so'],
                        'tu_cu' => $xe['tu_ngay']?->format('Y-m-d') ?? '',
                        'den_cu' => $xe['den_ngay']?->format('Y-m-d') ?? '',
                    ];
                }
            }
        }

        return $rows;
    }

    /** Họ tên GV từ danh mục PMGPLX; tên viết hoa toàn bộ → viết hoa chữ đầu. */
    public static function hoTenGiaoVien(string $maGv): string
    {
        $maGv = trim($maGv);
        if ($maGv === '') {
            return '';
        }
        $gv = GiaoVien::query()->find($maGv);
        $ten = $gv instanceof GiaoVien ? trim(($gv->HoTenDem ?? '').' '.($gv->TenGV ?? '')) : '';

        return self::chuanHoaHoTen($ten);
    }

    public static function chuanHoaHoTen(string $ten): string
    {
        $ten = trim(preg_replace('/\s+/u', ' ', $ten) ?? $ten);
        if ($ten !== '' && $ten === mb_strtoupper($ten)) {
            return mb_convert_case(mb_strtolower($ten), MB_CASE_TITLE, 'UTF-8');
        }

        return $ten;
    }

    public static function ngay(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable) {
            return $value;
        }
    }

    /**
     * @param  array{gv_cu_ten: string, gv_moi_ten: string, xe: string, tu_cu: string, den_cu: string, tu_moi: string, den_moi: string}  $row
     */
    public static function cauDieuChinh(string $tenKhoa, array $row): string
    {
        $xe = QuyetDinhDieuDongXeSupport::bienSoHienThi($row['xe']);

        return sprintf(
            'Giáo viên %s sử dụng xe %s giảng dạy khóa %s từ ngày %s đến hết %s. '
            .'Giáo viên %s thay giáo viên %s tiếp tục sử dụng xe %s giảng dạy khóa %s từ ngày %s đến hết %s.',
            $row['gv_cu_ten'], $xe, $tenKhoa, self::ngay($row['tu_cu']), self::ngay($row['den_cu']),
            $row['gv_moi_ten'], $row['gv_cu_ten'], $xe, $tenKhoa, self::ngay($row['tu_moi']), self::ngay($row['den_moi'])
        );
    }
}
