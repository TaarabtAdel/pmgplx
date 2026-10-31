<?php

namespace App\Console\Commands;

use App\Support\QldtOto\ThongBaoKiemTraKetThucMonDocx;
use Illuminate\Console\Command;
use PhpOffice\PhpWord\TemplateProcessor;

class BuildThongBaoKiemTraKetThucMonTemplate extends Command
{
    protected $signature = 'qldtoto:build-thong-bao-kiem-tra-ket-thuc-mon-template';

    protected $description = 'Tạo thong-bao-kiem-tra-ket-thuc-mon.docx (placeholder PhpWord) từ mẫu gốc';

    public function handle(): int
    {
        try {
            $path = ThongBaoKiemTraKetThucMonDocx::buildTemplate();
            $vars = (new TemplateProcessor($path))->getVariables();
            $this->info('Đã ghi: '.$path);
            $this->line('Biến mẫu: '.implode(', ', array_unique($vars)));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
