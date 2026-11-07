<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cấu hình theo GV: SoGiaoVienMoiXe = 1; SoHocVienMoiXe = HV mỗi GV (giá trị cũ ÷ GV/xe cũ).
 * TienDoDuKienTangXe.SoXe = ±GV (từ ±xe × GV/xe cũ).
 */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);
        if (! $schema->hasTable('TienDoDuKienLoaiKhoa')) {
            return;
        }

        $conn = DB::connection($this->connection);

        if ($schema->hasTable('TienDoDuKienTangXe')) {
            $tang = $conn->table('TienDoDuKienTangXe as t')
                ->join('TienDoDuKienLoaiKhoa as l', 'l.Id', '=', 't.LoaiKhoaId')
                ->where('l.SoGiaoVienMoiXe', '>', 1)
                ->select(['t.Id', 't.SoXe', 'l.SoGiaoVienMoiXe'])
                ->get();
            foreach ($tang as $row) {
                $heSo = max(1, (int) $row->SoGiaoVienMoiXe);
                $conn->table('TienDoDuKienTangXe')->where('Id', $row->Id)->update([
                    'SoXe' => (int) $row->SoXe * $heSo,
                ]);
            }
        }

        $loai = $conn->table('TienDoDuKienLoaiKhoa')->where('SoGiaoVienMoiXe', '>', 1)->get();
        foreach ($loai as $row) {
            $gvCu = max(1, (int) $row->SoGiaoVienMoiXe);
            $hvMoi = (int) max(1, (int) round((int) $row->SoHocVienMoiXe / $gvCu));
            $conn->table('TienDoDuKienLoaiKhoa')->where('Id', $row->Id)->update([
                'SoHocVienMoiXe' => $hvMoi,
                'SoGiaoVienMoiXe' => 1,
            ]);
        }
    }

    public function down(): void
    {
        //
    }
};
