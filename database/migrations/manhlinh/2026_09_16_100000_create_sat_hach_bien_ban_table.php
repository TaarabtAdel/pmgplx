<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 *   php artisan migrate --database=sqlsrv_manhlinh --path=database/migrations/manhlinh
 */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        if (Schema::connection($this->connection)->hasTable('SatHachBienBan')) {
            return;
        }

        Schema::connection($this->connection)->create('SatHachBienBan', function (Blueprint $table) {
            $table->increments('Id');
            $table->string('MaKySH', 50)->nullable();
            $table->string('MaGiaoDich', 80)->nullable();
            $table->string('NgaySH', 20)->nullable();
            $table->string('SoQD', 80)->nullable();
            $table->unsignedInteger('SoTT')->nullable();
            $table->string('MaDK', 80);
            $table->string('HoVaTen', 255)->nullable();
            $table->string('NgaySinh', 20)->nullable();
            $table->string('SoCMT', 30)->nullable();
            $table->string('SoHoChieu', 30)->nullable();
            $table->string('NgayCapHC', 20)->nullable();
            $table->string('NoiCapHC', 150)->nullable();
            $table->string('SoBaoDanh', 30)->nullable();
            $table->string('HangGPLX', 20)->nullable();
            $table->string('DiemLtDat', 20)->nullable();
            $table->string('DiemHinhDat', 20)->nullable();
            $table->string('DiemDuongDat', 20)->nullable();
            $table->string('NhanXetLt', 500)->nullable();
            $table->string('NhanXetHinh', 500)->nullable();
            $table->string('NhanXetDuong', 500)->nullable();
            $table->string('KetQuaSH', 50)->nullable();
            $table->string('NgayKy', 10)->nullable();
            $table->string('ThangKy', 10)->nullable();
            $table->string('NamKy', 10)->nullable();
            $table->longText('AnhChanDung')->nullable();
            $table->string('FileNguon', 255)->nullable();
            $table->dateTime('NgayNhap')->nullable();

            $table->unique(['MaKySH', 'MaDK'], 'UQ_SatHachBienBan_Ky_MaDK');
            $table->index('MaKySH', 'IX_SatHachBienBan_MaKySH');
            $table->index('SoBaoDanh', 'IX_SatHachBienBan_SoBaoDanh');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('SatHachBienBan');
    }
};
