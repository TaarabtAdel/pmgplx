<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('DatLichThucHanhDuAn')) {
            Schema::connection($this->connection)->create('DatLichThucHanhDuAn', function (Blueprint $table) {
                $table->increments('Id');
                $table->string('MaKhoaHoc', 50);
                $table->string('HangDaoTao', 20);
                $table->date('NgayKhaiGiang');
                $table->date('NgayKetThucDuKien')->nullable();
                $table->text('CauHinhJson');
                $table->dateTime('NgayTao');
                $table->dateTime('NgayCapNhat');

                $table->index('MaKhoaHoc', 'IX_DatLichThucHanhDuAn_MaKhoaHoc');
            });
        }

        if (! Schema::connection($this->connection)->hasTable('DatLichThucHanhPhienBan')) {
            Schema::connection($this->connection)->create('DatLichThucHanhPhienBan', function (Blueprint $table) {
                $table->increments('Id');
                $table->unsignedInteger('DuAnId');
                $table->string('Ten', 200);
                $table->text('LichJson');
                $table->text('TomTatJson')->nullable();
                $table->text('KiemTraJson')->nullable();
                $table->text('OChinhTayJson')->nullable();
                $table->boolean('LaNhap')->default(true);
                $table->dateTime('NgayTao');

                $table->index('DuAnId', 'IX_DatLichThucHanhPhienBan_DuAnId');
            });
        }
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('DatLichThucHanhPhienBan');
        Schema::connection($this->connection)->dropIfExists('DatLichThucHanhDuAn');
    }
};
