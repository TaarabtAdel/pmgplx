<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quản lý cập nhật GPLX học viên (Phòng Đào Tạo → Báo cáo).
 * Lưu trên MANHLINH — không ghi vào DB PMGPLX (GPLX_BAN_MOI).
 * Mỗi học viên (MaDK bên PMGPLX) một dòng; thông tin định danh đọc từ NguoiLX / NguoiLX_HoSo.
 *
 *   php artisan migrate --database=sqlsrv_manhlinh --path=database/migrations/manhlinh
 */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);
        if ($schema->hasTable('CapNhatGPLXHocVien')) {
            return;
        }

        $schema->create('CapNhatGPLXHocVien', function (Blueprint $table) {
            $table->increments('Id');
            $table->string('MaDK', 50);
            $table->string('MaKhoaHoc', 50)->nullable();
            $table->boolean('DaTotNghiep')->default(false);
            $table->boolean('DaCapNhatGPLX')->default(false);
            $table->dateTime('NgayCapNhatGPLX')->nullable();
            $table->boolean('DaXuLy')->default(false);
            $table->string('SoGPLX', 30)->nullable();
            $table->string('HangGPLX', 20)->nullable();
            $table->date('NgayCapGPLX')->nullable();
            $table->date('NgayHHGPLX')->nullable();
            $table->string('NoiCapGPLX', 255)->nullable();
            $table->string('GhiChu', 500)->nullable();
            $table->unsignedInteger('NguoiTaoId')->nullable();
            $table->dateTime('NgayTao')->nullable();
            $table->dateTime('NgayCapNhat')->nullable();

            $table->unique('MaDK', 'UX_CapNhatGPLXHocVien_MaDK');
            $table->index('MaKhoaHoc', 'IX_CapNhatGPLXHocVien_MaKhoaHoc');
            $table->index('DaXuLy', 'IX_CapNhatGPLXHocVien_DaXuLy');
            $table->index('DaCapNhatGPLX', 'IX_CapNhatGPLXHocVien_DaCapNhat');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('CapNhatGPLXHocVien');
    }
};
