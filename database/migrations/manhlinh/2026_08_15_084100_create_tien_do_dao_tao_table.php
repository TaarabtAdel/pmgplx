<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng trên SQL Server MANHLINH (connection sqlsrv_manhlinh / DB_DATABASE_3).
 *
 * Chạy riêng:
 *   php artisan migrate --database=sqlsrv_manhlinh --path=database/migrations/manhlinh
 */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::connection($this->connection)->hasTable('TienDoDaoTao')) {
            return;
        }

        Schema::create('TienDoDaoTao', function (Blueprint $table) {
            $table->increments('Id');
            $table->string('MaKhoaLop', 50);
            $table->string('GiaoVienDay', 100)->nullable();
            $table->integer('SoLuongHocVien')->nullable();
            $table->integer('SoHocVienTotNghiep')->nullable();
            $table->smallInteger('NamHoc')->nullable();
            $table->string('ThangNam', 20)->nullable();
            $table->unsignedTinyInteger('TuanThu')->nullable();
            $table->date('TuNgay')->nullable();
            $table->date('DenNgay')->nullable();
            $table->string('KyHieu', 10)->nullable();
            $table->string('GhiChu', 500)->nullable();

            $table->index(['MaKhoaLop', 'NamHoc'], 'IX_TienDoDaoTao_Lop');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('TienDoDaoTao');
    }
};
