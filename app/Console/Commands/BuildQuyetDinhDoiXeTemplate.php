<?php

namespace App\Console\Commands;

use App\Support\QldtOto\QuyetDinhDoiXeDocxTemplateBuilder;
use Illuminate\Console\Command;
use PhpOffice\PhpWord\TemplateProcessor;

class BuildQuyetDinhDoiXeTemplate extends Command
{
    protected $signature = 'qldtoto:build-quyet-dinh-doi-xe-template';

    protected $description = 'Tạo quyet-dinh-doi-xe.docx từ quyet-dinh-doi-xe.doc';

    public function handle(QuyetDinhDoiXeDocxTemplateBuilder $builder): int
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
