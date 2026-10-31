<?php

namespace App\Console\Commands;

use App\Support\QldtOto\ThongBaoTapTrungThucHanhDocx;
use Illuminate\Console\Command;
use PhpOffice\PhpWord\TemplateProcessor;

class BuildThongBaoTapTrungThucHanhTemplate extends Command
{
    protected $signature = 'qldtoto:build-thong-bao-tap-trung-thuc-hanh-template';

    protected $description = 'Tạo thong-bao-tap-trung-thuc-hanh.docx (placeholder PhpWord) từ mẫu gốc';

    public function handle(): int
    {
        try {
            $path = ThongBaoTapTrungThucHanhDocx::buildTemplate();
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
