<?php

namespace App\Console\Commands;

use App\Support\QldtOto\PhanCongGvXeTapLaiDocxTemplateBuilder;
use Illuminate\Console\Command;
use PhpOffice\PhpWord\TemplateProcessor;

class BuildSoPhanCongGvXeTemplate extends Command
{
    protected $signature = 'qldtoto:build-so-phan-cong-gv-xe-template';

    protected $description = 'Gắn placeholder PhpWord vào phan-cong-giao-vien-xe-tap-lai.docx';

    public function handle(PhanCongGvXeTapLaiDocxTemplateBuilder $builder): int
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
