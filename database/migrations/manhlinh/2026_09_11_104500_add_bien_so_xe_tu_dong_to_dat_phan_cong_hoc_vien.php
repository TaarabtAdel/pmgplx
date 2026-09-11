<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('DatPhanCongHocVien')) {
            return;
        }

        if (Schema::connection($this->connection)->hasColumn('DatPhanCongHocVien', 'BienSoXeTuDong')) {
            return;
        }

        Schema::connection($this->connection)->table('DatPhanCongHocVien', function (Blueprint $table) {
            $table->string('BienSoXeTuDong', 50)->nullable()->after('BienSoXe');
        });
    }

    public function down(): void
    {
        if (! Schema::connection($this->connection)->hasTable('DatPhanCongHocVien')) {
            return;
        }

        if (! Schema::connection($this->connection)->hasColumn('DatPhanCongHocVien', 'BienSoXeTuDong')) {
            return;
        }

        Schema::connection($this->connection)->table('DatPhanCongHocVien', function (Blueprint $table) {
            $table->dropColumn('BienSoXeTuDong');
        });
    }
};
