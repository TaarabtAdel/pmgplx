<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lịch sử xuất file (Word / Excel / ZIP…) trong module QLĐT Ô TÔ (/qldt-oto).
 */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('QldtOtoLichSuXuatFile')) {
            $schema->create('QldtOtoLichSuXuatFile', function (Blueprint $table) {
                $table->increments('Id');
                $table->unsignedInteger('BieuMauId')->nullable();
                $table->string('TenRoute', 160);
                $table->string('TenChucNang', 255)->nullable();
                $table->string('DuongDan', 500);
                $table->string('PhuongThuc', 10);
                $table->string('TenFile', 255)->nullable();
                $table->string('LoaiFile', 20)->nullable();
                $table->string('SoCongVan', 80)->nullable();
                $table->date('NgayVanBan')->nullable();
                $table->string('MaKh', 50)->nullable();
                $table->text('ThamSoJson')->nullable();
                $table->unsignedInteger('NguoiXuatId')->nullable();
                $table->dateTime('ThoiGianXuat');
                $table->string('DiaChiIp', 45)->nullable();

                $table->index(['ThoiGianXuat'], 'IX_QldtOtoLichSuXuatFile_ThoiGian');
                $table->index(['TenRoute', 'ThoiGianXuat'], 'IX_QldtOtoLichSuXuatFile_Route_ThoiGian');
                $table->index(['NguoiXuatId', 'ThoiGianXuat'], 'IX_QldtOtoLichSuXuatFile_Nguoi_ThoiGian');
                $table->index('BieuMauId', 'IX_QldtOtoLichSuXuatFile_BieuMau');
                $table->index('SoCongVan', 'IX_QldtOtoLichSuXuatFile_SoCongVan');
                $table->index('NgayVanBan', 'IX_QldtOtoLichSuXuatFile_NgayVanBan');
                $table->index('MaKh', 'IX_QldtOtoLichSuXuatFile_MaKh');
            });
        }
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('QldtOtoLichSuXuatFile');
    }
};