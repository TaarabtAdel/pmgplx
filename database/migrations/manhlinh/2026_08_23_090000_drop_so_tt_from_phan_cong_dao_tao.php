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
        Schema::table('PhanCongDaoTao', function (Blueprint $table) {
            $table->dropColumn('SoTT');
        });
    }

    public function down(): void
    {
        Schema::table('PhanCongDaoTao', function (Blueprint $table) {
            $table->integer('SoTT')->nullable()->after('Id');
        });
    }
};
