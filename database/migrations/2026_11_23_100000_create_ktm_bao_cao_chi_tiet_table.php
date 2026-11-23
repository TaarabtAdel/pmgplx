<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('KtmBaoCaoChiTiet')) {
            return;
        }

        Schema::create('KtmBaoCaoChiTiet', function (Blueprint $table) {
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
        Schema::dropIfExists('KtmBaoCaoChiTiet');
    }
};