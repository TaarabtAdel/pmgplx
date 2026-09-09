<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phân loại phiên DAT — danh mục + gán nhiều phiên ↔ nhiều loại.
 *
 *   php artisan migrate --database=sqlsrv_manhlinh --path=database/migrations/manhlinh
 */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('DatPhanLoaiPhien')) {
            Schema::connection($this->connection)->create('DatPhanLoaiPhien', function (Blueprint $table) {
                $table->increments('Id');
                $table->string('TenPhanLoai', 150);
                $table->string('MoTa', 500)->nullable();
                $table->unsignedInteger('ThuTu')->default(0);
                $table->dateTime('NgayTao')->nullable();
            });
        }

        if (! Schema::connection($this->connection)->hasTable('DatDSPhienPhanLoai')) {
            Schema::connection($this->connection)->create('DatDSPhienPhanLoai', function (Blueprint $table) {
                $table->unsignedInteger('DatDSPhienId');
                $table->unsignedInteger('PhanLoaiId');
                $table->primary(['DatDSPhienId', 'PhanLoaiId'], 'PK_DatDSPhienPhanLoai');
                $table->index('DatDSPhienId', 'IX_DatDSPhienPhanLoai_Phien');
                $table->index('PhanLoaiId', 'IX_DatDSPhienPhanLoai_Loai');
            });
        }
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('DatDSPhienPhanLoai');
        Schema::connection($this->connection)->dropIfExists('DatPhanLoaiPhien');
    }
};
