<?php

namespace App\Console\Commands;

use App\Support\QldtOto\SoTayGiaoVienThucHanhDocxTemplateBuilder;
use Illuminate\Console\Command;
use PhpOffice\PhpWord\TemplateProcessor;

class BuildSoTayGiaoVienThucHanhTemplate extends Command
{
    protected $signature = 'qldtoto:build-so-tay-gv-thuc-hanh-template';

    protected $description = 'Gắn placeholder PhpWord vào so-tay-giao-vien-thuc-hanh.docx';

    public function handle(SoTayGiaoVienThucHanhDocxTemplateBuilder $builder): int
    {
        try {
            $path = $builder->build();
            $vars = (new TemplateProcessor($path))->getVariables();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Đã ghi: '.$path);
        $this->line('Biến: '.implode(', ', $vars));

        return self::SUCCESS;
    }
}
