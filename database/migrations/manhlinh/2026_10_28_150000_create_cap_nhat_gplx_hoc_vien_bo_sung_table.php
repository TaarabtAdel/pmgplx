<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Giá trị bổ sung khi PMGPLX thiếu (Nơi HKTT, Đã có GPLX, Ngày trúng tuyển…).
 *
 *   php artisan migrate --database=sqlsrv_manhlinh --path=database/migrations/manhlinh
 */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);
        if ($schema->hasTable('CapNhatGPLXHocVienBoSung')) {
            return;
        }

        $schema->create('CapNhatGPLXHocVienBoSung', function (Blueprint $table) {
            $table->increments('Id');
            $table->unsignedInteger('CapNhatGPLXHocVienId');
            $table->string('MaDK', 50);
            $table->string('NoiDKHoKhauThuongTru', 500)->nullable();
            $table->string('DaCoGPLX', 30)->nullable();
            $table->date('NgayTrungTuyen')->nullable();
            $table->dateTime('NgayCapNhat')->nullable();

            $table->unique('CapNhatGPLXHocVienId', 'UX_CapNhatGPLXBoSung_HV');
            $table->index('MaDK', 'IX_CapNhatGPLXBoSung_MaDK');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('CapNhatGPLXHocVienBoSung');
    }
};
