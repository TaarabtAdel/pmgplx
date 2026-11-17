<?php

namespace App\Console\Commands;

use App\Support\QldtOto\QuyetDinhHuyPhienQuyetDinhDocx;
use Illuminate\Console\Command;

class BuildQuyetDinhHuyPhienTemplate extends Command
{
    protected $signature = 'qldtoto:build-quyet-dinh-huy-phien-template';

    protected $description = 'Tạo quyet-dinh-huy-phien-kem-danh-sach.docx mẫu (PhpWord)';

    public function handle(): int
    {
        try {
            $path = QuyetDinhHuyPhienQuyetDinhDocx::buildTemplate();
            $this->info('Đã ghi: '.$path);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
