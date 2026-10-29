<?php

namespace App\Console\Commands;

use App\Support\QldtOto\BaoCaoDeNghiSatHachDocxTemplateBuilder;
use Illuminate\Console\Command;
use PhpOffice\PhpWord\TemplateProcessor;

class BuildBaoCaoDeNghiSatHachTemplate extends Command
{
    protected $signature = 'qldtoto:build-bao-cao-de-nghi-sat-hach-template';

    protected $description = 'Tạo bao-cao-de-nghi-to-chuc-sat-hach.docx (placeholder PhpWord) từ mẫu gốc';

    public function handle(BaoCaoDeNghiSatHachDocxTemplateBuilder $builder): int
    {
        try {
            $path = $builder->build();
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
