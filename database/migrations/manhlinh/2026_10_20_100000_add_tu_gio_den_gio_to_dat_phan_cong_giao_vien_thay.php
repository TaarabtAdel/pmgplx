<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Khung giờ dạy thay trong mỗi ngày của khoảng TuNgay–DenNgay; NULL = cả ngày. */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        $schema->table('DatPhanCongGiaoVienThay', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('DatPhanCongGiaoVienThay', 'TuGio')) {
                $table->time('TuGio')->nullable();
            }
            if (! $schema->hasColumn('DatPhanCongGiaoVienThay', 'DenGio')) {
                $table->time('DenGio')->nullable();
            }
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        $schema->table('DatPhanCongGiaoVienThay', function (Blueprint $table) use ($schema) {
            if ($schema->hasColumn('DatPhanCongGiaoVienThay', 'TuGio')) {
                $table->dropColumn('TuGio');
            }
            if ($schema->hasColumn('DatPhanCongGiaoVienThay', 'DenGio')) {
                $table->dropColumn('DenGio');
            }
        });
    }
};
