<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        if (Schema::connection($this->connection)->hasTable('DatPhanCongHocVien')) {
            return;
        }

        Schema::connection($this->connection)->create('DatPhanCongHocVien', function (Blueprint $table) {
            $table->increments('Id');
            $table->string('MaKhoaHoc', 50);
            $table->string('MaHocVien', 50);
            $table->string('HoTenHocVien', 255)->nullable();
            $table->string('MaGiaoVien', 50);
            $table->string('BienSoXe', 50)->nullable();
            $table->string('FileNguon', 255)->nullable();
            $table->dateTime('NgayNhap')->nullable();

            $table->unique(['MaKhoaHoc', 'MaHocVien'], 'UQ_DatPhanCongHocVien_Khoa_MaHV');
            $table->index('MaKhoaHoc', 'IX_DatPhanCongHocVien_MaKhoaHoc');
            $table->index('MaHocVien', 'IX_DatPhanCongHocVien_MaHocVien');
            $table->index('MaGiaoVien', 'IX_DatPhanCongHocVien_MaGiaoVien');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('DatPhanCongHocVien');
    }
};
