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
        if (! Schema::connection($this->connection)->hasColumn('DatDieuKienCanhBao', 'PhanLoaiKhongDatIds')) {
            Schema::connection($this->connection)->table('DatDieuKienCanhBao', function (Blueprint $table) {
                $table->string('PhanLoaiKhongDatIds', 500)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::connection($this->connection)->hasColumn('DatDieuKienCanhBao', 'PhanLoaiKhongDatIds')) {
            Schema::connection($this->connection)->table('DatDieuKienCanhBao', function (Blueprint $table) {
                $table->dropColumn('PhanLoaiKhongDatIds');
            });
        }
    }
};
