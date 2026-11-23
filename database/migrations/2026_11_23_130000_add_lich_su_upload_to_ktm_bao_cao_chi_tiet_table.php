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
            if (! Schema::hasColumn('KtmBaoCaoChiTiet', 'LichSuUpload')) {
                $table->text('LichSuUpload')->nullable()->after('CanhBao');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('KtmBaoCaoChiTiet')) {
            return;
        }

        Schema::table('KtmBaoCaoChiTiet', function (Blueprint $table) {
            if (Schema::hasColumn('KtmBaoCaoChiTiet', 'LichSuUpload')) {
                $table->dropColumn('LichSuUpload');
            }
        });
    }
};