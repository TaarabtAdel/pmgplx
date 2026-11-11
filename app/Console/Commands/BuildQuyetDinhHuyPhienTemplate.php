<?php

namespace App\Console\Commands;

use App\Support\QldtOto\QuyetDinhHuyPhienKemDanhSachDocx;
use Illuminate\Console\Command;
use PhpOffice\PhpWord\TemplateProcessor;

class BuildQuyetDinhHuyPhienTemplate extends Command
{
    protected $signature = 'qldtoto:build-quyet-dinh-huy-phien-template';

    protected $description = 'Tạo quyet-dinh-huy-phien-tu-choi-phien.docx (placeholder PhpWord) từ mẫu .doc';

    public function handle(): int
    {
        try {
            $path = QuyetDinhHuyPhienKemDanhSachDocx::buildTemplate();
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
