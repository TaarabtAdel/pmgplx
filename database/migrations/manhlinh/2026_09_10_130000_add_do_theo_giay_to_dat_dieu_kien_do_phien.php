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
        if (! Schema::connection($this->connection)->hasColumn('DatDieuKienDoPhien', 'DoTheoGiay')) {
            Schema::connection($this->connection)->table('DatDieuKienDoPhien', function (Blueprint $table) {
                $table->boolean('DoTheoGiay')->default(false);
            });
        }
    }

    public function down(): void
    {
        if (Schema::connection($this->connection)->hasColumn('DatDieuKienDoPhien', 'DoTheoGiay')) {
            Schema::connection($this->connection)->table('DatDieuKienDoPhien', function (Blueprint $table) {
                $table->dropColumn('DoTheoGiay');
            });
        }
    }
};
