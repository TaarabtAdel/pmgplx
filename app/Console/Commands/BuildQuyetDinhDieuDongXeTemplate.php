<?php

namespace App\Console\Commands;

use App\Support\QldtOto\QuyetDinhDieuDongXeDocxTemplateBuilder;
use Illuminate\Console\Command;
use PhpOffice\PhpWord\TemplateProcessor;

class BuildQuyetDinhDieuDongXeTemplate extends Command
{
    protected $signature = 'qldtoto:build-quyet-dinh-dieu-dong-xe-template';

    protected $description = 'Tạo quyet-dinh-dieu-dong-xe-gvth.docx (placeholder PhpWord, 1 trang/xe) từ file .doc mẫu';

    public function handle(QuyetDinhDieuDongXeDocxTemplateBuilder $builder): int
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
