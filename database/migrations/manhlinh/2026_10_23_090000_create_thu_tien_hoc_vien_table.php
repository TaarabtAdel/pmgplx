<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quản lý thu tiền học viên (Phòng Đào Tạo → Báo cáo). Mỗi học viên (MaDK bên PMGPLX) một dòng:
 * đã tốt nghiệp, đã thu tiền, kết quả thi từng môn. Số tiền từng cột tính từ các cờ này (xem ThuTienHocVien::PHI).
 */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);
        if ($schema->hasTable('ThuTienHocVien')) {
            return;
        }

        $schema->create('ThuTienHocVien', function (Blueprint $table) {
            $table->increments('Id');
            $table->string('MaDK', 50);
            $table->string('MaKhoaHoc', 50)->nullable();
            $table->boolean('DaTotNghiep')->default(false);
            $table->boolean('DaThuTien')->default(false);
            $table->dateTime('NgayThuTien')->nullable();
            $table->string('KetQuaLyThuyet', 10)->nullable();
            $table->string('KetQuaSaHinh', 10)->nullable();
            $table->string('KetQuaDuong', 10)->nullable();
            $table->string('GhiChu', 500)->nullable();
            $table->unsignedInteger('NguoiTaoId')->nullable();
            $table->dateTime('NgayTao')->nullable();
            $table->dateTime('NgayCapNhat')->nullable();

            $table->unique('MaDK', 'UX_ThuTienHocVien_MaDK');
            $table->index('MaKhoaHoc', 'IX_ThuTienHocVien_MaKhoaHoc');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('ThuTienHocVien');
    }
};
