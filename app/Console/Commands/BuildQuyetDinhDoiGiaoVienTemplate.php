<?php

namespace App\Console\Commands;

use App\Support\QldtOto\QuyetDinhDoiGiaoVienDocxTemplateBuilder;
use Illuminate\Console\Command;
use PhpOffice\PhpWord\TemplateProcessor;

class BuildQuyetDinhDoiGiaoVienTemplate extends Command
{
    protected $signature = 'qldtoto:build-quyet-dinh-doi-giao-vien-template';

    protected $description = 'Tạo quyet-dinh-doi-giao-vien.docx (placeholder PhpWord) từ file .doc mẫu';

    public function handle(QuyetDinhDoiGiaoVienDocxTemplateBuilder $builder): int
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
