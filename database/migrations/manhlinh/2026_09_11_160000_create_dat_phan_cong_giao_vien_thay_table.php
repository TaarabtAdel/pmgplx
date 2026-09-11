<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        if (Schema::connection($this->connection)->hasTable('DatPhanCongGiaoVienThay')) {
            return;
        }

        Schema::connection($this->connection)->create('DatPhanCongGiaoVienThay', function (Blueprint $table) {
            $table->increments('Id');
            $table->unsignedInteger('PhanCongId');
            $table->string('MaGiaoVien', 50);
            $table->date('TuNgay');
            $table->date('DenNgay')->nullable();
            $table->dateTime('NgayNhap')->nullable();

            $table->index('PhanCongId', 'IX_DatPhanCongGiaoVienThay_PhanCongId');
            $table->index(['PhanCongId', 'TuNgay'], 'IX_DatPhanCongGiaoVienThay_PhanCong_TuNgay');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('DatPhanCongGiaoVienThay');
    }
};
