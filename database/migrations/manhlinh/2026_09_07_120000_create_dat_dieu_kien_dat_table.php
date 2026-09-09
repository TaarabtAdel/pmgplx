<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Điều kiện đạt DAT theo hạng GPLX — cấu hình tại màn Điều kiện đạt.
 *
 *   php artisan migrate --database=sqlsrv_manhlinh --path=database/migrations/manhlinh
 */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('DatDieuKienDat')) {
            Schema::connection($this->connection)->create('DatDieuKienDat', function (Blueprint $table) {
                $table->increments('Id');
                $table->string('Hang', 20);
                $table->date('ApDungTuNgay')->nullable();
                $table->decimal('TapLaiBanDemGio', 8, 2)->default(0);
                $table->decimal('XeSoTuDongGio', 8, 2)->default(0);
                $table->decimal('SoGioHoc', 8, 2)->default(0);
                $table->decimal('TongQuangDuongKm', 10, 2)->default(0);
                $table->unsignedInteger('ThuTu')->default(0);
                $table->dateTime('NgayTao')->nullable();
                $table->dateTime('NgayCapNhat')->nullable();

                $table->index(['ApDungTuNgay', 'ThuTu'], 'IX_DatDieuKienDat_Ngay_ThuTu');
            });

            $now = now()->format('Y-m-d H:i:s');
            $rows = [
                ['Hang' => 'B.01', 'ApDungTuNgay' => '2025-09-01', 'TapLaiBanDemGio' => '2', 'XeSoTuDongGio' => '0', 'SoGioHoc' => '12', 'TongQuangDuongKm' => '710', 'ThuTu' => 1],
                ['Hang' => 'B', 'ApDungTuNgay' => '2025-09-01', 'TapLaiBanDemGio' => '2', 'XeSoTuDongGio' => '1.6', 'SoGioHoc' => '20', 'TongQuangDuongKm' => '810', 'ThuTu' => 2],
                ['Hang' => 'C1', 'ApDungTuNgay' => '2025-09-01', 'TapLaiBanDemGio' => '2', 'XeSoTuDongGio' => '1.6', 'SoGioHoc' => '24', 'TongQuangDuongKm' => '825', 'ThuTu' => 3],
            ];

            foreach ($rows as $row) {
                DB::connection($this->connection)->table('DatDieuKienDat')->insert([
                    ...$row,
                    'NgayTao' => $now,
                    'NgayCapNhat' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('DatDieuKienDat');
    }
};
