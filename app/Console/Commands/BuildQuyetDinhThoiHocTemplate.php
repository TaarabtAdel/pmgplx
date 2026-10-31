<?php

namespace App\Console\Commands;

use App\Support\QldtOto\QuyetDinhThoiHocDocx;
use Illuminate\Console\Command;
use PhpOffice\PhpWord\TemplateProcessor;

class BuildQuyetDinhThoiHocTemplate extends Command
{
    protected $signature = 'qldtoto:build-quyet-dinh-cho-thoi-hoc-template';

    protected $description = 'Tạo quyet-dinh-cho-thoi-hoc.docx (placeholder PhpWord) từ quyet-dinh-cho-thoi-hoc.doc';

    public function handle(): int
    {
        try {
            $path = QuyetDinhThoiHocDocx::buildTemplate();
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
