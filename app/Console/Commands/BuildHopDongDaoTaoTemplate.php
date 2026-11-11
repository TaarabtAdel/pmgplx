<?php

namespace App\Console\Commands;

use App\Support\QldtOto\HopDongDaoTaoLaiXeDocx;
use Illuminate\Console\Command;
use PhpOffice\PhpWord\TemplateProcessor;

class BuildHopDongDaoTaoTemplate extends Command
{
    protected $signature = 'qldtoto:build-hop-dong-dao-tao-template';

    protected $description = 'Tạo mẫu hop-dong-dao-tao-*.docx (B1/B2/C1 TT17)';

    public function handle(): int
    {
        $ok = true;
        foreach (array_keys(HopDongDaoTaoLaiXeDocx::LOAI) as $loai) {
            try {
                $path = HopDongDaoTaoLaiXeDocx::buildTemplate($loai);
                $vars = (new TemplateProcessor($path))->getVariables();
                $this->info($loai.': '.$path);
                $this->line('  '.implode(', ', array_unique($vars)));
            } catch (\Throwable $e) {
                $ok = false;
                $this->error($loai.': '.$e->getMessage());
            }
        }

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
