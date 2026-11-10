<?php

namespace App\Console\Commands;

use App\Support\QldtOto\BienBanXacNhanPhoBienNoiQuyDocx;
use Illuminate\Console\Command;
use PhpOffice\PhpWord\TemplateProcessor;

class BuildBienBanPhoBienNoiQuyTemplate extends Command
{
    protected $signature = 'qldtoto:build-bien-ban-pho-bien-noi-quy-template';

    protected $description = 'Tạo bien-ban-xac-nhan-pho-bien-noi-quy-hoc-tap.docx (placeholder PhpWord) từ mẫu gốc';

    public function handle(): int
    {
        try {
            $path = BienBanXacNhanPhoBienNoiQuyDocx::buildTemplate();
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
