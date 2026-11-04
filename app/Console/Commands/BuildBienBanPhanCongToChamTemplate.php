<?php

namespace App\Console\Commands;

use App\Support\QldtOto\BienBanPhanCongToChamDocx;
use Illuminate\Console\Command;
use PhpOffice\PhpWord\TemplateProcessor;

class BuildBienBanPhanCongToChamTemplate extends Command
{
    protected $signature = 'qldtoto:build-bien-ban-phan-cong-to-cham-template';

    protected $description = 'Tạo bien-ban-phan-cong-to-cham-kiem-tra.docx (biên bản phân công nhiệm vụ tổ chấm kiểm tra) từ mẫu gốc';

    public function handle(): int
    {
        try {
            $path = BienBanPhanCongToChamDocx::buildTemplate();
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
