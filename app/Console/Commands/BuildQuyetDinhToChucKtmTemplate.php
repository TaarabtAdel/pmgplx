<?php

namespace App\Console\Commands;

use App\Support\QldtOto\QuyetDinhToChucKiemTraKetThucMonDocx;
use Illuminate\Console\Command;
use PhpOffice\PhpWord\TemplateProcessor;

class BuildQuyetDinhToChucKtmTemplate extends Command
{
    protected $signature = 'qldtoto:build-quyet-dinh-to-chuc-ktm-template';

    protected $description = 'Tạo quyet-dinh-to-chuc-kiem-tra-ket-thuc-mon.docx từ mẫu gốc';

    public function handle(): int
    {
        try {
            $path = QuyetDinhToChucKiemTraKetThucMonDocx::buildTemplate();
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
