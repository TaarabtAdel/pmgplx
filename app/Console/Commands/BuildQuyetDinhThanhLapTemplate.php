<?php

namespace App\Console\Commands;

use App\Support\QldtOto\QuyetDinhThanhLapLopHocDocxTemplateBuilder;
use Illuminate\Console\Command;

class BuildQuyetDinhThanhLapTemplate extends Command
{
    protected $signature = 'qldtoto:build-quyet-dinh-thanh-lap-template';

    protected $description = 'Chuyển quyet-dinh-thanh-lap-lop-hoc.doc sang .docx có placeholder PhpWord';

    public function handle(QuyetDinhThanhLapLopHocDocxTemplateBuilder $builder): int
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
