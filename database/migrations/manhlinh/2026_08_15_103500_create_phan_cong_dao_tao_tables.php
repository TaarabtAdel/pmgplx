<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sổ phân công đào tạo — DB MANHLINH (sqlsrv_manhlinh / DB_DATABASE_3).
 *
 *   php artisan migrate --database=sqlsrv_manhlinh --path=database/migrations/manhlinh
 */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        Schema::create('GiaoVien', function (Blueprint $table) {
            $table->increments('Id');
            $table->string('MaGV', 20)->nullable();
            $table->string('HoTen', 100);
            $table->string('LoaiGV', 20)->nullable();
            $table->string('SoDienThoai', 15)->nullable();
            $table->boolean('TrangThai')->default(true);
            $table->string('GhiChu', 255)->nullable();
            $table->dateTime('NgayTao')->useCurrent();
            $table->dateTime('NgayCapNhat')->nullable();

            $table->index('HoTen', 'IX_GiaoVien_HoTen');
        });

        DB::connection($this->connection)->statement(
            'CREATE UNIQUE INDEX [IX_GiaoVien_MaGV] ON [GiaoVien]([MaGV]) WHERE [MaGV] IS NOT NULL'
        );

        Schema::create('XeTapLai', function (Blueprint $table) {
            $table->increments('Id');
            $table->string('BienSo', 20)->unique();
            $table->string('LoaiXe', 20)->nullable();
            $table->string('HangXe', 50)->nullable();
            $table->boolean('TrangThai')->default(true);
            $table->string('GhiChu', 255)->nullable();
            $table->dateTime('NgayTao')->useCurrent();
            $table->dateTime('NgayCapNhat')->nullable();
        });

        Schema::create('KhoaDaoTao', function (Blueprint $table) {
            $table->increments('Id');
            $table->string('MaKhoa', 20)->nullable();
            $table->string('TenKhoa', 50);
            $table->string('HangDaoTao', 10)->nullable();
            $table->date('NgayKhaiGiang')->nullable();
            $table->date('NgayBeGiang')->nullable();
            $table->string('TrangThai', 20)->nullable();
            $table->string('GhiChu', 255)->nullable();
            $table->dateTime('NgayTao')->useCurrent();
            $table->dateTime('NgayCapNhat')->nullable();

            $table->unique('TenKhoa', 'IX_KhoaDaoTao_TenKhoa');
        });

        DB::connection($this->connection)->statement(
            'CREATE UNIQUE INDEX [IX_KhoaDaoTao_MaKhoa] ON [KhoaDaoTao]([MaKhoa]) WHERE [MaKhoa] IS NOT NULL'
        );

        Schema::create('PhanCongDaoTao', function (Blueprint $table) {
            $table->increments('Id');
            $table->integer('SoTT')->nullable();
            $table->unsignedInteger('GiaoVienId')->nullable();
            $table->unsignedInteger('XeTapLaiId')->nullable();
            $table->unsignedInteger('KhoaDaoTaoId');
            $table->date('TuNgay');
            $table->date('DenNgay');
            $table->string('NoiDungGiangDay', 100)->nullable();
            $table->string('GhiChu', 255)->nullable();
            $table->dateTime('NgayTao')->useCurrent();
            $table->dateTime('NgayCapNhat')->nullable();

            $table->foreign('GiaoVienId')->references('Id')->on('GiaoVien');
            $table->foreign('XeTapLaiId')->references('Id')->on('XeTapLai');
            $table->foreign('KhoaDaoTaoId')->references('Id')->on('KhoaDaoTao');

            $table->index('KhoaDaoTaoId', 'IX_PhanCong_KhoaDaoTaoId');
            $table->index('GiaoVienId', 'IX_PhanCong_GiaoVienId');
            $table->index('XeTapLaiId', 'IX_PhanCong_XeTapLaiId');
        });

        DB::connection($this->connection)->statement(
            'ALTER TABLE PhanCongDaoTao ADD CONSTRAINT CHK_PhanCong_ThoiGian CHECK (DenNgay >= TuNgay)'
        );
        DB::connection($this->connection)->statement(
            'ALTER TABLE PhanCongDaoTao ADD CONSTRAINT CHK_PhanCong_GVhoacXe CHECK (GiaoVienId IS NOT NULL OR XeTapLaiId IS NOT NULL)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('PhanCongDaoTao');
        Schema::dropIfExists('KhoaDaoTao');
        Schema::dropIfExists('XeTapLai');
        Schema::dropIfExists('GiaoVien');
    }
};
