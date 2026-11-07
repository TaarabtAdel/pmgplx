<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Báo cáo tiến độ đào tạo (thực tế + dự kiến trong năm):
 * - TienDoDuKienLoaiKhoa: mỗi dòng là một chuỗi khoá nối tiếp nhau (khoá sau thực hành sau khi khoá trước tốt nghiệp).
 *   NgayKhaiGiang = NULL → tự động nối tiếp khoá thực tế (TienDoDaoTao) tốt nghiệp muộn nhất cùng hạng.
 * - TienDoDuKienTangXe: từ ngày X, chuỗi khoá tăng/giảm N xe → khoá dự kiến khai giảng từ ngày đó đổi số học viên.
 * - TienDoDuKienNgayNghi: ngày lễ, Tết không tính vào số ngày lý thuyết / thực hành của khoá dự kiến.
 *
 *   php artisan migrate --database=sqlsrv_manhlinh --path=database/migrations/manhlinh
 */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);
        $conn = DB::connection($this->connection);

        if (! $schema->hasTable('TienDoDuKienLoaiKhoa')) {
            $schema->create('TienDoDuKienLoaiKhoa', function (Blueprint $table) {
                $table->increments('Id');
                $table->string('TenLoai', 100);
                $table->string('Hang', 5);
                $table->unsignedSmallInteger('SoHocVienToiDa');
                $table->unsignedSmallInteger('SoNgayLyThuyet');
                $table->unsignedSmallInteger('SoNgayThucHanh');
                $table->unsignedSmallInteger('SoHocVienMoiXe');
                $table->unsignedSmallInteger('SoGiaoVienMoiXe')->default(2);
                $table->unsignedSmallInteger('SoNgayCach')->default(2);
                $table->date('NgayKhaiGiang')->nullable();
                $table->boolean('DangSuDung')->default(true);
                $table->unsignedSmallInteger('ThuTu')->default(0);
                $table->string('GhiChu', 500)->nullable();
                $table->dateTime('NgayTao');
                $table->dateTime('NgayCapNhat')->nullable();
            });

            $now = now();
            $conn->table('TienDoDuKienLoaiKhoa')->insert([
                ['TenLoai' => 'B01 (tự động)', 'Hang' => 'B01', 'SoHocVienToiDa' => 100, 'SoNgayLyThuyet' => 17, 'SoNgayThucHanh' => 45, 'SoHocVienMoiXe' => 5, 'SoGiaoVienMoiXe' => 1, 'SoNgayCach' => 2, 'NgayKhaiGiang' => null, 'DangSuDung' => true, 'ThuTu' => 1, 'GhiChu' => null, 'NgayTao' => $now, 'NgayCapNhat' => null],
                ['TenLoai' => 'C1 (hạng C)', 'Hang' => 'C', 'SoHocVienToiDa' => 48, 'SoNgayLyThuyet' => 19, 'SoNgayThucHanh' => 45, 'SoHocVienMoiXe' => 8, 'SoGiaoVienMoiXe' => 1, 'SoNgayCach' => 2, 'NgayKhaiGiang' => null, 'DangSuDung' => true, 'ThuTu' => 2, 'GhiChu' => null, 'NgayTao' => $now, 'NgayCapNhat' => null],
                ['TenLoai' => 'B (số sàn)', 'Hang' => 'B', 'SoHocVienToiDa' => 100, 'SoNgayLyThuyet' => 19, 'SoNgayThucHanh' => 47, 'SoHocVienMoiXe' => 5, 'SoGiaoVienMoiXe' => 1, 'SoNgayCach' => 2, 'NgayKhaiGiang' => null, 'DangSuDung' => true, 'ThuTu' => 3, 'GhiChu' => null, 'NgayTao' => $now, 'NgayCapNhat' => null],
            ]);
        }

        if (! $schema->hasTable('TienDoDuKienTangXe')) {
            $schema->create('TienDoDuKienTangXe', function (Blueprint $table) {
                $table->increments('Id');
                $table->unsignedInteger('LoaiKhoaId');
                $table->date('TuNgay');
                $table->smallInteger('SoXe');
                $table->string('GhiChu', 500)->nullable();
                $table->dateTime('NgayTao');

                $table->index(['LoaiKhoaId', 'TuNgay'], 'IX_TienDoDuKienTangXe_Loai_Ngay');
                $table->foreign('LoaiKhoaId', 'FK_TienDoDuKienTangXe_LoaiKhoa')
                    ->references('Id')->on('TienDoDuKienLoaiKhoa')
                    ->cascadeOnDelete();
            });
        }

        if (! $schema->hasTable('TienDoDuKienNgayNghi')) {
            $schema->create('TienDoDuKienNgayNghi', function (Blueprint $table) {
                $table->increments('Id');
                $table->string('TenNgayNghi', 150);
                $table->date('TuNgay');
                $table->date('DenNgay');
                $table->dateTime('NgayTao');

                $table->index('TuNgay', 'IX_TienDoDuKienNgayNghi_TuNgay');
            });

            $now = now();
            $conn->table('TienDoDuKienNgayNghi')->insert([
                ['TenNgayNghi' => 'Nghỉ Tết Nguyên Đán', 'TuNgay' => '2026-02-13', 'DenNgay' => '2026-02-21', 'NgayTao' => $now],
                ['TenNgayNghi' => 'Giỗ Tổ Hùng Vương (10/3 âm lịch)', 'TuNgay' => '2026-04-26', 'DenNgay' => '2026-04-26', 'NgayTao' => $now],
            ]);
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);
        $schema->dropIfExists('TienDoDuKienTangXe');
        $schema->dropIfExists('TienDoDuKienNgayNghi');
        $schema->dropIfExists('TienDoDuKienLoaiKhoa');
    }
};
