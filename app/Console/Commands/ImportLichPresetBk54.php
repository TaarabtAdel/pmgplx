<?php

namespace App\Console\Commands;

/** @deprecated Dùng {@see ImportLichThucHanhTuExcel} (`dat:import-lich-thuc-hanh-excel`). */
class ImportLichPresetBk54 extends ImportLichThucHanhTuExcel
{
    protected $signature = 'dat:import-lich-preset-bk54
                            {path? : Đường dẫn file Excel}
                            {--ma-khoa=44007K261005}
                            {--fixture=}
                            {--no-db}
                            {--write-nghi-rieng}';

    protected $description = '(Cũ) Alias import Excel — dùng dat:import-lich-thuc-hanh-excel';
}
