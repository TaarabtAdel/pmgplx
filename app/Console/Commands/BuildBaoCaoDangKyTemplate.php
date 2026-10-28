<?php

namespace App\Console\Commands;

use App\Support\QldtOto\BaoCaoDangKyDocxTemplateBuilder;
use Illuminate\Console\Command;

class BuildBaoCaoDangKyTemplate extends Command
{
    protected $signature = 'qldtoto:build-bao-cao-dang-ky-template';

    protected $description = 'Chuyển resources/templates/oto/bao-cao-dang-ky.doc sang .docx có placeholder PhpWord';

    public function handle(BaoCaoDangKyDocxTemplateBuilder $builder): int
    {
        try {
            $path = $builder->build();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Đã ghi: '.$path);

        return self::SUCCESS;
    }
}
