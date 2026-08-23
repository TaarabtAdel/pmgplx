<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PhanCongDaoTao.LoaiGiangDay: ly_thuyet | thuc_hanh (suy từ cột nội dung file Excel).
 *
 *   php artisan migrate --database=sqlsrv_manhlinh --path=database/migrations/manhlinh
 */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        Schema::table('PhanCongDaoTao', function (Blueprint $table) {
            $table->string('LoaiGiangDay', 20)->nullable()->after('DenNgay');
        });
    }

    public function down(): void
    {
        Schema::table('PhanCongDaoTao', function (Blueprint $table) {
            $table->dropColumn('LoaiGiangDay');
        });
    }
};
