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
        if (! Schema::connection($this->connection)->hasColumn('DatDieuKienDat', 'GioCaoTocGio')) {
            Schema::connection($this->connection)->table('DatDieuKienDat', function (Blueprint $table) {
                $table->decimal('GioCaoTocGio', 8, 2)->default(0)->after('XeSoTuDongGio');
            });
        }
    }

    public function down(): void
    {
        if (Schema::connection($this->connection)->hasColumn('DatDieuKienDat', 'GioCaoTocGio')) {
            Schema::connection($this->connection)->table('DatDieuKienDat', function (Blueprint $table) {
                $table->dropColumn('GioCaoTocGio');
            });
        }
    }
};
