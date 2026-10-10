<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('DatPhanCongXeThay')) {
            return;
        }

        $schema->create('DatPhanCongXeThay', function (Blueprint $table) {
            $table->increments('Id');
            $table->string('MaKhoaHoc', 50);
            $table->string('MaGiaoVienGoc', 50);
            $table->string('BienSoXeGoc', 50);
            $table->string('BienSoXe', 50);
            $table->date('TuNgay');
            $table->date('DenNgay')->nullable();
            $table->dateTime('NgayNhap')->nullable();

            $table->index(['MaKhoaHoc', 'MaGiaoVienGoc', 'BienSoXeGoc'], 'IX_DatPhanCongXeThay_Khoa_Gv_XeGoc');
            $table->index(['MaKhoaHoc', 'MaGiaoVienGoc', 'BienSoXeGoc', 'TuNgay'], 'IX_DatPhanCongXeThay_Khoa_Gv_XeGoc_TuNgay');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('DatPhanCongXeThay');
    }
};
