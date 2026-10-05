<?php

namespace App\Support\DaoTao\LichThucHanh;

final class BaiGiang
{
    public const HINH = 'HINH';

    public const CABIN = 'CABIN';

    public const TU_DONG = 'TU_DONG';

    public const PHUC_TAP = 'PHUC_TAP';

    public const DOC_QC = 'DOC_QC';

    public const CAO_TOC = 'CAO_TOC';

    public const CO_TAI = 'CO_TAI';

    public const BO_SUNG = 'BO_SUNG';

    public const ON_STL = 'ON_STL';

    public const ON_TD = 'ON_TD';

    public const KIEM_TRA = 'KIEM_TRA';

    public const BAN_DEM = 'BAN_DEM';

    /** @return array<string, array{label: string, excel: string, bg: string}> */
    public static function mauHienThi(): array
    {
        return [
            self::PHUC_TAP => ['label' => 'PHỨC TẠP', 'excel' => 'FF1565C0', 'bg' => '#e3f2fd'],
            self::BAN_DEM => ['label' => 'BAN ĐÊM', 'excel' => 'FF7B1FA2', 'bg' => '#f3e5f5'],
            self::CO_TAI => ['label' => 'CÓ TẢI', 'excel' => 'FFC62828', 'bg' => '#ffebee'],
            self::DOC_QC => ['label' => 'DỐC/QC', 'excel' => 'FF2E7D32', 'bg' => '#e8f5e9'],
            self::CAO_TOC => ['label' => 'CAO TỐC', 'excel' => 'FFF9A825', 'bg' => '#fff8e1'],
            self::TU_DONG => ['label' => 'TỰ ĐỘNG', 'excel' => 'FF0D47A1', 'bg' => '#e8eaf6'],
            self::HINH => ['label' => 'HÌNH', 'excel' => 'FFEEEEEE', 'bg' => '#f5f5f5'],
            self::CABIN => ['label' => 'CABIN', 'excel' => 'FFE0E0E0', 'bg' => '#eceff1'],
            self::BO_SUNG => ['label' => 'BỔ SUNG', 'excel' => 'FF90A4AE', 'bg' => '#eceff1'],
            self::ON_STL => ['label' => 'ÔN LUYỆN STL', 'excel' => 'FFB0BEC5', 'bg' => '#e0f7fa'],
            self::ON_TD => ['label' => 'ÔN LUYỆN TĐ', 'excel' => 'FFB0BEC5', 'bg' => '#e0f7fa'],
            self::KIEM_TRA => ['label' => 'KIỂM TRA', 'excel' => 'FF455A64', 'bg' => '#eceff1'],
        ];
    }
}
