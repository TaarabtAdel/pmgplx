<?php

namespace App\Console\Commands;

use App\Support\QldtOto\QuyetDinhHoanThanhKhoaHocDocx;
use Illuminate\Console\Command;
use PhpOffice\PhpWord\TemplateProcessor;

class BuildQuyetDinhHoanThanhKhoaHocTemplate extends Command
{
    protected $signature = 'qldtoto:build-quyet-dinh-hoan-thanh-khoa-hoc-template';

    protected $description = 'Tạo quyet-dinh-hoan-thanh-khoa-hoc.docx (quyết định + trang danh sách kèm theo) từ mẫu .doc';

    public function handle(): int
    {
        try {
            $path = QuyetDinhHoanThanhKhoaHocDocx::buildTemplate();
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
