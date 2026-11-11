<?php

namespace App\Console\Commands;

use App\Support\QldtOto\SoTayGiaoVienLyThuyetDocxTemplateBuilder;
use Illuminate\Console\Command;
use PhpOffice\PhpWord\TemplateProcessor;

class BuildSoTayGiaoVienLyThuyetTemplate extends Command
{
    protected $signature = 'qldtoto:build-so-tay-gv-ly-thuyet-template';

    protected $description = 'Gắn placeholder PhpWord vào so-tay-giao-vien-ly-thuyet.docx';

    public function handle(SoTayGiaoVienLyThuyetDocxTemplateBuilder $builder): int
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
