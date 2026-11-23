<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Báo cáo chi tiết KTM (ghép file, lưu DB).
 *
 *   php artisan migrate --database=sqlsrv_manhlinh --path=database/migrations/manhlinh
 */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('KtmBaoCaoChiTiet')) {
            return;
        }

        $schema->create('KtmBaoCaoChiTiet', function (Blueprint $table) {
            $table->id();
            $table->date('NgayKyThi');
            $table->string('TenKhoaThi', 255);
            $table->string('TenKhoaTuFile', 255)->nullable();
            $table->string('DiaDiemSatHach', 255)->nullable();
            $table->text('DuLieu');
            $table->text('CanhBao')->nullable();
            $table->timestamps();
            $table->unique(['NgayKyThi', 'TenKhoaThi'], 'UQ_KtmBcChiTiet_Ngay_TenKhoa');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('KtmBaoCaoChiTiet');
    }
};