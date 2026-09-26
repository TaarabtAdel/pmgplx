<?php

namespace App\Support\SatHach;

use RuntimeException;

/**
 * Gộp file tung-file/*.docx thành 1 Word tổng.
 * Ưu tiên Clippit (giữ định dạng); tránh gộp XML PHP trừ khi bật rõ ràng.
 */
class BienBanTongDocxCombiner
{
    /**
     * @param  list<string>  $pageDocxPaths
     */
    public function merge(array $pageDocxPaths, string $outputPath): void
    {
        $driver = strtolower(trim((string) config('services.bien_ban_tong.merge_driver', 'clippit')));

        if ($driver === 'none') {
            throw new RuntimeException('Gộp file tổng đang tắt (BIEN_BAN_TONG_MERGE_DRIVER=none).');
        }

        if ($driver === 'xml') {
            (new BienBanDocxMerger())->mergeXmlOnly($pageDocxPaths, $outputPath);

            return;
        }

        if ($driver === 'word') {
            (new BienBanWordCommandMerger())->merge($pageDocxPaths, $outputPath);

            return;
        }

        if ($driver === 'clippit') {
            (new BienBanClippitMerger())->merge($pageDocxPaths, $outputPath);

            return;
        }

        // auto: clippit → word → báo lỗi (không dùng gộp XML dễ hỏng layout)
        if (BienBanClippitMerger::isAvailable()) {
            (new BienBanClippitMerger())->merge($pageDocxPaths, $outputPath);

            return;
        }

        $word = new BienBanWordCommandMerger();
        if ($word->isAvailable()) {
            $word->merge($pageDocxPaths, $outputPath);

            return;
        }

        if (filter_var(config('services.bien_ban_tong.allow_xml_merge', false), FILTER_VALIDATE_BOOL)) {
            (new BienBanDocxMerger())->mergeXmlOnly($pageDocxPaths, $outputPath);

            return;
        }

        throw new RuntimeException(
            'Không gộp được file tổng: cần Clippit (CLIPPIT_BIN) hoặc tắt gộp (BIEN_BAN_TONG_GOP_FILE=false, chỉ dùng ZIP từng file).'
        );
    }
}
