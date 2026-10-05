<?php

namespace App\Console\Commands;

use App\Models\DaoTao\DatLichThucHanhDuAn;
use App\Support\DaoTao\LichThucHanh\LichCauHinhTuExcel;
use App\Support\DaoTao\LichThucHanh\LichKeHoachExcelImporter;
use App\Support\DaoTao\LichThucHanh\LichThucHanhExcelReader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportLichThucHanhTuExcel extends Command
{
    protected $signature = 'dat:import-lich-thuc-hanh-excel
                            {path? : File Excel lịch TH (7 cột/GV)}
                            {--ma-khoa=44007K261005 : Mã khóa gán vào cấu hình}
                            {--fixture= : Ghi JSON fixture (vd. tests/fixtures/...)}
                            {--no-db : Chỉ ghi fixture, không ghi DB}
                            {--write-nghi-rieng : Ghi config/lich_thuc_hanh_nghi_rieng_mau.json nếu chưa có}';

    protected $description = 'Import cấu hình + kế hoạch GV từ Excel lịch TH (mọi khóa — truyền --ma-khoa)';

    public function handle(): int
    {
        $path = $this->argument('path')
            ?? base_path('tests/fixtures/LI_CH_NHA__P_BK54.xlsx');

        if (! is_readable($path)) {
            $this->error("Không tìm thấy file: {$path}");

            return self::FAILURE;
        }

        $reader = new LichThucHanhExcelReader;
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $blocks = $reader->parseTeacherBlocks($sheet);
        $capXe = $reader->buildCapXe($blocks);
        $dates = $reader->scanDates($sheet, $blocks);
        $nghiRieng = LichCauHinhTuExcel::inferNghiRiengFromSheet($sheet, $reader, $blocks, $dates, $capXe);

        if ($this->option('write-nghi-rieng') && $nghiRieng !== []) {
            $cfgPath = config_path('lich_thuc_hanh_nghi_rieng_mau.json');
            if (! is_readable($cfgPath)) {
                File::put($cfgPath, json_encode($nghiRieng, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                $this->info("Đã ghi {$cfgPath} — vui lòng duyệt.");
            }
        }

        $maKhoa = (string) $this->option('ma-khoa');
        $cauHinh = LichCauHinhTuExcel::buildCauHinh($capXe, $nghiRieng, $maKhoa);
        $cauHinh['ke_hoach_theo_gv'] = (new LichKeHoachExcelImporter)->fromWorksheet($sheet, $blocks, $dates);

        $fixturePath = $this->option('fixture') ?? base_path('tests/fixtures/bk54_cau_hinh.json');
        File::ensureDirectoryExists(dirname($fixturePath));
        File::put($fixturePath, json_encode($cauHinh, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->info("Fixture: {$fixturePath}");

        if (! $this->option('no-db')) {
            $duAn = DatLichThucHanhDuAn::query()
                ->where('MaKhoaHoc', $maKhoa)
                ->where('HangDaoTao', 'B')
                ->first();

            if ($duAn === null) {
                $duAn = new DatLichThucHanhDuAn;
                $duAn->MaKhoaHoc = $maKhoa;
                $duAn->HangDaoTao = 'B';
                $duAn->NgayTao = now();
            }
            $duAn->NgayKhaiGiang = LichCauHinhTuExcel::NGAY_KG;
            $duAn->NgayKetThucDuKien = LichCauHinhTuExcel::NGAY_KT;
            $duAn->setCauHinhArray($cauHinh);
            $duAn->NgayCapNhat = now();
            $duAn->save();
            $this->info("DB: DatLichThucHanhDuAn Id={$duAn->Id}");
        }

        $this->info('Import xong: '.count($capXe).' cặp xe, '.count($nghiRieng).' nghỉ riêng.');

        return self::SUCCESS;
    }
}
