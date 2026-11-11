<?php

namespace App\Console\Commands;

use App\Support\QldtOto\QuyetDinhThanhLapHoiDongXetHoanThanhDocx;
use Illuminate\Console\Command;
use PhpOffice\PhpWord\TemplateProcessor;

class BuildQuyetDinhThanhLapHoiDongXetHtTemplate extends Command
{
    protected $signature = 'qldtoto:build-qd-thanh-lap-hoi-dong-xet-ht-template';

    protected $description = 'Tạo quyet-dinh-thanh-lap-hoi-dong-xet-hoan-thanh-khoa.docx từ mẫu gốc';

    public function handle(): int
    {
        try {
            $path = QuyetDinhThanhLapHoiDongXetHoanThanhDocx::buildTemplate();
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
