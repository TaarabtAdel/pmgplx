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
            if (! $schema->hasColumn('KtmBaoCaoChiTiet', 'LichSuUpload')) {
                $table->text('LichSuUpload')->nullable()->after('CanhBao');
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
            if ($schema->hasColumn('KtmBaoCaoChiTiet', 'LichSuUpload')) {
                $table->dropColumn('LichSuUpload');
            }
        });
    }
};