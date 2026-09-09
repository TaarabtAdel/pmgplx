<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ngưỡng cảnh báo phiên DAT — một dòng cấu hình, chỉnh tại màn Điều kiện cảnh báo.
 *
 *   php artisan migrate --database=sqlsrv_manhlinh --path=database/migrations/manhlinh
 */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('DatDieuKienCanhBao')) {
            Schema::connection($this->connection)->create('DatDieuKienCanhBao', function (Blueprint $table) {
                $table->increments('Id');
                $table->unsignedSmallInteger('ThoiGianPhienToiThieuPhut')->default(5);
                $table->unsignedSmallInteger('ThoiGianPhienToiDaPhut')->default(240);
                $table->unsignedSmallInteger('KhoangPhienLienKePhut')->default(15);
                $table->decimal('TiLeNhanDienToiThieu', 5, 2)->default(75);
                $table->dateTime('NgayCapNhat')->nullable();
            });

            DB::connection($this->connection)->table('DatDieuKienCanhBao')->insert([
                'ThoiGianPhienToiThieuPhut' => 5,
                'ThoiGianPhienToiDaPhut' => 240,
                'KhoangPhienLienKePhut' => 15,
                'TiLeNhanDienToiThieu' => 75,
                'NgayCapNhat' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('DatDieuKienCanhBao');
    }
};
