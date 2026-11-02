<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kết quả «Dò thông tin» CCCD / GPLX của học viên (danh mục học viên PMGPLX).
 * Lưu trên MANHLINH — không ghi vào DB PMGPLX (GPLX_BAN_MOI). Mỗi học viên (MaDK) một dòng;
 * cột nào NULL là chưa lưu trường đó (chỉ ghi các trường người dùng tích chọn).
 *
 *   php artisan migrate --database=sqlsrv_manhlinh --path=database/migrations/manhlinh
 */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);
        if ($schema->hasTable('HocVienDoThongTin')) {
            return;
        }

        $schema->create('HocVienDoThongTin', function (Blueprint $table) {
            $table->increments('Id');
            $table->string('MaDK', 50);
            $table->string('MaKhoaHoc', 50)->nullable();
            $table->string('HoTen', 150)->nullable();
            $table->string('SoCCCD', 20)->nullable();
            $table->date('NgaySinh')->nullable();
            $table->string('NoiThuongTru', 300)->nullable();
            $table->date('NgayCapCCCD')->nullable();
            $table->string('SoGPLX', 30)->nullable();
            $table->date('NgayCapGPLX')->nullable();
            $table->date('NgayTrungTuyen')->nullable();
            $table->unsignedInteger('NguoiCapNhatId')->nullable();
            $table->dateTime('NgayTao')->nullable();
            $table->dateTime('NgayCapNhat')->nullable();

            $table->unique('MaDK', 'UX_HocVienDoThongTin_MaDK');
            $table->index('SoCCCD', 'IX_HocVienDoThongTin_SoCCCD');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('HocVienDoThongTin');
    }
};
