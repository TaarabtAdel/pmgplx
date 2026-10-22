<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Danh sách bài dạy theo hạng đào tạo (B, B01, C) — cấu hình dùng cho lập lịch. */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);
        if ($schema->hasTable('QldtOtoBaiDay')) {
            return;
        }

        $schema->create('QldtOtoBaiDay', function (Blueprint $table) {
            $table->increments('Id');
            $table->string('Hang', 10);
            $table->unsignedSmallInteger('NgaySo');
            $table->decimal('SoGio', 5, 2)->nullable();
            $table->string('NoiDung', 500);
            $table->time('GioBatDau')->nullable();
            $table->time('GioKetThuc')->nullable();
            $table->unsignedSmallInteger('ThuTu')->default(0);
            $table->dateTime('NgayTao')->nullable();
            $table->dateTime('NgayCapNhat')->nullable();

            $table->index(['Hang', 'NgaySo', 'ThuTu'], 'IX_QldtOtoBaiDay_Hang_NgaySo');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('QldtOtoBaiDay');
    }
};
