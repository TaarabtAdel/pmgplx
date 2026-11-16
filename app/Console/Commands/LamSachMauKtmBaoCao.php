<?php

namespace App\Console\Commands;

use App\Support\QldtOto\KtmBaoCao\KtmLamSachFileMau;
use Illuminate\Console\Command;

class LamSachMauKtmBaoCao extends Command
{
    protected $signature = 'qldt-oto:lam-sach-mau-ktm';

    protected $description = 'Xóa dữ liệu mẫu cũ trong 3 file Excel template báo cáo KTM';

    public function handle(): int
    {
        KtmLamSachFileMau::lamSachCaBa();
        $this->info('Đã làm sạch 3 mẫu tại resources/templates/oto/ktm-bao-cao/');

        return self::SUCCESS;
    }
}