<?php

namespace App\Console\Commands;

use App\Support\QldtOto\SoTheoDoiKetQuaHocTapDocxTemplateBuilder;
use Illuminate\Console\Command;
use PhpOffice\PhpWord\TemplateProcessor;

class BuildSoTheoDoiKetQuaHocTapTemplate extends Command
{
    protected $signature = 'qldtoto:build-so-theo-doi-kq-hoc-tap-template';

    protected $description = 'Gắn placeholder PhpWord vào so-theo-doi-ket-qua-hoc-tap.docx';

    public function handle(SoTheoDoiKetQuaHocTapDocxTemplateBuilder $builder): int
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
