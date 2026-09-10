<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ngưỡng dò phiên với lịch xe — một dòng cấu hình, chỉnh tại màn Điều kiện dò phiên.
 *
 *   php artisan migrate --database=sqlsrv_manhlinh --path=database/migrations/manhlinh
 */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('DatDieuKienDoPhien')) {
            Schema::connection($this->connection)->create('DatDieuKienDoPhien', function (Blueprint $table) {
                $table->increments('Id');
                $table->unsignedSmallInteger('ChoPhepSomPhut')->default(0);
                $table->unsignedSmallInteger('ChoPhepMuonPhut')->default(0);
                $table->dateTime('NgayCapNhat')->nullable();
            });

            DB::connection($this->connection)->table('DatDieuKienDoPhien')->insert([
                'ChoPhepSomPhut' => 0,
                'ChoPhepMuonPhut' => 0,
                'NgayCapNhat' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('DatDieuKienDoPhien');
    }
};
