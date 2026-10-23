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
        if (! $schema->hasTable('ThuTienHocVien') || $schema->hasColumn('ThuTienHocVien', 'DaXuLy')) {
            return;
        }

        $schema->table('ThuTienHocVien', function (Blueprint $table) {
            $table->boolean('DaXuLy')->default(false)->after('DaThuTien');
            $table->index('DaXuLy', 'IX_ThuTienHocVien_DaXuLy');
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);
        if (! $schema->hasTable('ThuTienHocVien') || ! $schema->hasColumn('ThuTienHocVien', 'DaXuLy')) {
            return;
        }

        $schema->table('ThuTienHocVien', function (Blueprint $table) {
            $table->dropIndex('IX_ThuTienHocVien_DaXuLy');
            $table->dropColumn('DaXuLy');
        });
    }
};
