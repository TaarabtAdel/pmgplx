<?php

namespace App\Support\QldtOto;

use PhpOffice\PhpWord\TemplateProcessor;

/** Điền bảng học viên phần II — luôn 100 dòng trên sổ tay GV. */
final class SoTayGiaoVienBangDocx
{
    /**
     * @param  list<array{stt: int|string, ho_ten: string}>  $hocVien
     */
    public static function dienBang(
        TemplateProcessor $processor,
        array $hocVien,
        bool $sttHaiChuSo = false,
        ?callable $esc = null,
    ): void {
        $esc ??= static fn (string $v): string => htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $rows = SoTayGiaoVienHocVien::dayBangMotTram($hocVien);
        $processor->cloneRow('stt', SoTayGiaoVienHocVien::SO_DONG_BANG);

        foreach ($rows as $i => $row) {
            $n = $i + 1;
            $stt = (string) ($row['stt'] ?? '');
            if ($stt !== '' && $sttHaiChuSo && is_numeric($stt)) {
                $stt = str_pad((string) (int) $stt, 2, '0', STR_PAD_LEFT);
            }
            $processor->setValue('stt#'.$n, $esc($stt));
            $processor->setValue('ho_ten#'.$n, $esc((string) ($row['ho_ten'] ?? '')));
        }
    }
}
