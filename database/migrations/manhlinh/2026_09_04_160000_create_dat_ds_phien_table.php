<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng DAT — danh sách phiên học trên SQL Server MANHLINH.
 *
 * Chạy riêng:
 *   php artisan migrate --database=sqlsrv_manhlinh --path=database/migrations/manhlinh
 */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        if (Schema::connection($this->connection)->hasTable('DatDSPhien')) {
            return;
        }

        Schema::create('DatDSPhien', function (Blueprint $table) {
            $table->increments('Id');
            $table->string('MaPhienHoc', 100);
            $table->decimal('TiLeNhanDien', 8, 4)->nullable();
            $table->dateTime('ThoiGianBatDauPhienHoc')->nullable();
            $table->dateTime('ThoiGianKetThucPhienHoc')->nullable();
            $table->decimal('ThoiGianThucHanhGio', 10, 4)->nullable();
            $table->decimal('QuangDuongThucHanhKm', 10, 4)->nullable();
            $table->decimal('ThoiGianLaiBanDemGio', 10, 4)->nullable();
            $table->decimal('ThoiGianLaiXeSoTuDong', 10, 4)->nullable();
            $table->dateTime('ThoiGianMayChuNhanPhienHoc')->nullable();
            $table->string('MaHocVien', 50)->nullable();
            $table->string('HoTenHocVien', 255)->nullable();
            $table->string('MaKhoaHoc', 50)->nullable();
            $table->string('TenKhoaHoc', 255)->nullable();
            $table->string('LoaiKhoaHoc', 100)->nullable();
            $table->string('HoTenGiaoVien', 255)->nullable();
            $table->string('MaGiaoVien', 50)->nullable();
            $table->string('BienSoXe', 50)->nullable();
            $table->string('MaThietBi', 100)->nullable();
            $table->string('FileNguon', 255)->nullable();
            $table->dateTime('NgayNhap')->nullable();

            $table->unique('MaPhienHoc', 'UQ_DatDSPhien_MaPhienHoc');
            $table->index(['MaHocVien', 'MaKhoaHoc'], 'IX_DatDSPhien_HV_KH');
            $table->index('ThoiGianBatDauPhienHoc', 'IX_DatDSPhien_BatDau');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('DatDSPhien');
    }
};
