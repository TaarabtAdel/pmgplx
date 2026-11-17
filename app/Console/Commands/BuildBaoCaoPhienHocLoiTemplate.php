<?php

namespace App\Console\Commands;

use App\Support\QldtOto\BaoCaoPhienHocLoiDocx;
use Illuminate\Console\Command;
use PhpOffice\PhpWord\TemplateProcessor;

class BuildBaoCaoPhienHocLoiTemplate extends Command
{
    protected $signature = 'qldtoto:build-bao-cao-phien-hoc-loi-template';

    protected $description = 'Tạo bao-cao-phien-hoc-loi.docx (placeholder PhpWord) từ mẫu .doc';

    public function handle(): int
    {
        try {
            $path = BaoCaoPhienHocLoiDocx::buildTemplate();
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