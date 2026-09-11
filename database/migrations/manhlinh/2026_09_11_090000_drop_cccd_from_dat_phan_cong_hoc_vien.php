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

        if (! Schema::connection($this->connection)->hasColumn('DatPhanCongHocVien', 'CCCD')) {
            return;
        }

        Schema::connection($this->connection)->table('DatPhanCongHocVien', function (Blueprint $table): void {
            $table->dropUnique('UQ_DatPhanCongHocVien_Khoa_CCCD');
            $table->dropColumn('CCCD');
        });

        Schema::connection($this->connection)->table('DatPhanCongHocVien', function (Blueprint $table): void {
            $table->unique(['MaKhoaHoc', 'MaHocVien'], 'UQ_DatPhanCongHocVien_Khoa_MaHV');
        });
    }

    public function down(): void
    {
        if (! Schema::connection($this->connection)->hasTable('DatPhanCongHocVien')) {
            return;
        }

        Schema::connection($this->connection)->table('DatPhanCongHocVien', function (Blueprint $table): void {
            $table->dropUnique('UQ_DatPhanCongHocVien_Khoa_MaHV');
            $table->string('CCCD', 20)->nullable();
        });

        Schema::connection($this->connection)->table('DatPhanCongHocVien', function (Blueprint $table): void {
            $table->unique(['MaKhoaHoc', 'CCCD'], 'UQ_DatPhanCongHocVien_Khoa_CCCD');
        });
    }
};
