<?php

namespace App\Console\Commands;

use App\Support\QldtOto\KtmChiTiet\KtmTachMauChuan;
use Illuminate\Console\Command;

class TachMauKtmChuanCommand extends Command
{
    protected $signature = 'qldt-oto:tach-mau-chuan
        {--nguon= : Đường dẫn file chuẩn (mặc định resources/.../file-chuan-ktm-mau.xls)}';

    protected $description = 'Tách BC CHUNG, BBTH, Biên bản họp từ workbook chuẩn → 3 file mẫu .xls (giữ format)';

    public function handle(): int
    {
        $nguon = $this->option('nguon');
        try {
            if (is_string($nguon) && $nguon !== '') {
                KtmTachMauChuan::tachCaBa($nguon);
            } else {
                KtmTachMauChuan::tachCaBa();
            }
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        foreach (KtmTachMauChuan::SHEET_RA_FILE as $sheet => $file) {
            $path = resource_path('templates/oto/ktm-bao-cao/'.$file);
            $kb = is_file($path) ? round(filesize($path) / 1024) : 0;
            $this->line('  • '.$sheet.' → '.$file.' ('.$kb.' KB)');
        }
        $this->info('Đã tách mẫu. Xuất báo cáo dùng các file trên (không đọc cả workbook 11MB).');

        return self::SUCCESS;
    }
}