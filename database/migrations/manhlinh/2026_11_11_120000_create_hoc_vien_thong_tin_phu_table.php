<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Thông tin bổ sung học viên (SDT, Sale) — MANHLINH, không ghi vào PMGPLX.
 * Một học viên (MaDK) một dòng.
 *
 *   php artisan migrate --database=sqlsrv_manhlinh --path=database/migrations/manhlinh
 */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);
        if ($schema->hasTable('HocVienThongTinPhu')) {
            return;
        }

        $schema->create('HocVienThongTinPhu', function (Blueprint $table) {
            $table->increments('Id');
            $table->string('MaDK', 50);
            $table->string('SoDienThoai', 30)->nullable();
            $table->string('Sale', 100)->nullable();
            $table->unsignedInteger('NguoiCapNhatId')->nullable();
            $table->dateTime('NgayTao')->nullable();
            $table->dateTime('NgayCapNhat')->nullable();

            $table->unique('MaDK', 'UX_HocVienThongTinPhu_MaDK');
            $table->index('SoDienThoai', 'IX_HocVienThongTinPhu_SDT');
            $table->index('Sale', 'IX_HocVienThongTinPhu_Sale');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('HocVienThongTinPhu');
    }
};
