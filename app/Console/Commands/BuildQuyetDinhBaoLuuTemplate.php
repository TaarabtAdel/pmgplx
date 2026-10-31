<?php

namespace App\Console\Commands;

use App\Support\QldtOto\QuyetDinhBaoLuuDocx;
use Illuminate\Console\Command;
use PhpOffice\PhpWord\TemplateProcessor;

class BuildQuyetDinhBaoLuuTemplate extends Command
{
    protected $signature = 'qldtoto:build-quyet-dinh-bao-luu-template';

    protected $description = 'Tạo quyet-dinh-bao-luu.docx (placeholder PhpWord) từ quyet-dinh-bao-luu.doc';

    public function handle(): int
    {
        try {
            $path = QuyetDinhBaoLuuDocx::buildTemplate();
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
