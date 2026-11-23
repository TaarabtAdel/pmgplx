<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('KtmBaoCaoChiTiet')) {
            return;
        }

        $schema->table('KtmBaoCaoChiTiet', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('KtmBaoCaoChiTiet', 'LaThiLai')) {
                $table->boolean('LaThiLai')->default(false)->after('DiaDiemSatHach');
            }
            if (! $schema->hasColumn('KtmBaoCaoChiTiet', 'BaoCaoGocId')) {
                $table->unsignedBigInteger('BaoCaoGocId')->nullable()->after('LaThiLai');
            }
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('KtmBaoCaoChiTiet')) {
            return;
        }

        $schema->table('KtmBaoCaoChiTiet', function (Blueprint $table) use ($schema) {
            if ($schema->hasColumn('KtmBaoCaoChiTiet', 'BaoCaoGocId')) {
                $table->dropColumn('BaoCaoGocId');
            }
            if ($schema->hasColumn('KtmBaoCaoChiTiet', 'LaThiLai')) {
                $table->dropColumn('LaThiLai');
            }
        });
    }
};