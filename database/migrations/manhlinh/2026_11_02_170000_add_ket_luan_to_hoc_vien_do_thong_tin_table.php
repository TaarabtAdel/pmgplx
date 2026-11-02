<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kết luận đối chiếu do người dò chọn: «ok» (chấp nhận — có thể chỉ cần khớp vài trường) hoặc «can_chinh».
 */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);
        if (! $schema->hasTable('HocVienDoThongTin') || $schema->hasColumn('HocVienDoThongTin', 'KetLuan')) {
            return;
        }

        $schema->table('HocVienDoThongTin', function (Blueprint $table) {
            $table->string('KetLuan', 20)->nullable();
            $table->index('KetLuan', 'IX_HocVienDoThongTin_KetLuan');
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);
        if (! $schema->hasTable('HocVienDoThongTin') || ! $schema->hasColumn('HocVienDoThongTin', 'KetLuan')) {
            return;
        }

        $schema->table('HocVienDoThongTin', function (Blueprint $table) {
            $table->dropIndex('IX_HocVienDoThongTin_KetLuan');
            $table->dropColumn('KetLuan');
        });
    }
};
