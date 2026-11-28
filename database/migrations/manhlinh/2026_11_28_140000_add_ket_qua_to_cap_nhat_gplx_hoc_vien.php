<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kết quả sát hạch (cột R «Tổng hợp» khi nhập Excel) trên danh sách cập nhật GPLX.
 *
 *   php artisan migrate --database=sqlsrv_manhlinh --path=database/migrations/manhlinh
 */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);
        if (! $schema->hasTable('CapNhatGPLXHocVien') || $schema->hasColumn('CapNhatGPLXHocVien', 'KetQua')) {
            return;
        }

        $schema->table('CapNhatGPLXHocVien', function (Blueprint $table) {
            $table->string('KetQua', 100)->nullable();
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);
        if ($schema->hasTable('CapNhatGPLXHocVien') && $schema->hasColumn('CapNhatGPLXHocVien', 'KetQua')) {
            $schema->table('CapNhatGPLXHocVien', function (Blueprint $table) {
                $table->dropColumn('KetQua');
            });
        }
    }
};
