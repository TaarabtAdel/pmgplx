<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** PMGPLX không lưu điện thoại học viên — nhập tay trên màn thu tiền. */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);
        if (! $schema->hasTable('ThuTienHocVien') || $schema->hasColumn('ThuTienHocVien', 'SoDienThoai')) {
            return;
        }

        $schema->table('ThuTienHocVien', function (Blueprint $table) {
            $table->string('SoDienThoai', 30)->nullable()->after('MaKhoaHoc');
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);
        if (! $schema->hasTable('ThuTienHocVien') || ! $schema->hasColumn('ThuTienHocVien', 'SoDienThoai')) {
            return;
        }

        $schema->table('ThuTienHocVien', function (Blueprint $table) {
            $table->dropColumn('SoDienThoai');
        });
    }
};
