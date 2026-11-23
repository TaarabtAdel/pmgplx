<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('KtmBaoCaoChiTiet')) {
            return;
        }

        Schema::table('KtmBaoCaoChiTiet', function (Blueprint $table) {
            if (! Schema::hasColumn('KtmBaoCaoChiTiet', 'LaThiLai')) {
                $table->boolean('LaThiLai')->default(false)->after('DiaDiemSatHach');
            }
            if (! Schema::hasColumn('KtmBaoCaoChiTiet', 'BaoCaoGocId')) {
                $table->unsignedBigInteger('BaoCaoGocId')->nullable()->after('LaThiLai');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('KtmBaoCaoChiTiet')) {
            return;
        }

        Schema::table('KtmBaoCaoChiTiet', function (Blueprint $table) {
            if (Schema::hasColumn('KtmBaoCaoChiTiet', 'BaoCaoGocId')) {
                $table->dropColumn('BaoCaoGocId');
            }
            if (Schema::hasColumn('KtmBaoCaoChiTiet', 'LaThiLai')) {
                $table->dropColumn('LaThiLai');
            }
        });
    }
};