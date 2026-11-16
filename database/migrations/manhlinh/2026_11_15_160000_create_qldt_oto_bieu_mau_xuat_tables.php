<?php

use App\Support\QldtOto\BieuMauXuatDongBo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Danh mục biểu mẫu xuất + mở rộng lịch sử (số công văn / QĐ, ngày văn bản, mã khoá).
 */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('QldtOtoBieuMauXuat')) {
            $schema->create('QldtOtoBieuMauXuat', function (Blueprint $table) {
                $table->increments('Id');
                $table->string('Ma', 80);
                $table->string('TenRouteExport', 160)->unique('UX_QldtOtoBieuMauXuat_TenRoute');
                $table->string('TenBieuMau', 255);
                $table->string('LoaiXuat', 40)->nullable();
                $table->unsignedSmallInteger('ThuTu')->default(0);
                $table->boolean('HoatDong')->default(true);

                $table->index(['Ma', 'ThuTu'], 'IX_QldtOtoBieuMauXuat_Ma_ThuTu');
            });
        }

        if ($schema->hasTable('QldtOtoLichSuXuatFile')) {
            $schema->table('QldtOtoLichSuXuatFile', function (Blueprint $table) use ($schema) {
                if (! $schema->hasColumn('QldtOtoLichSuXuatFile', 'BieuMauId')) {
                    $table->unsignedInteger('BieuMauId')->nullable()->after('Id');
                    $table->index('BieuMauId', 'IX_QldtOtoLichSuXuatFile_BieuMau');
                }
                if (! $schema->hasColumn('QldtOtoLichSuXuatFile', 'SoCongVan')) {
                    $table->string('SoCongVan', 80)->nullable()->after('LoaiFile');
                    $table->index('SoCongVan', 'IX_QldtOtoLichSuXuatFile_SoCongVan');
                }
                if (! $schema->hasColumn('QldtOtoLichSuXuatFile', 'NgayVanBan')) {
                    $table->date('NgayVanBan')->nullable()->after('SoCongVan');
                    $table->index('NgayVanBan', 'IX_QldtOtoLichSuXuatFile_NgayVanBan');
                }
                if (! $schema->hasColumn('QldtOtoLichSuXuatFile', 'MaKh')) {
                    $table->string('MaKh', 50)->nullable()->after('NgayVanBan');
                    $table->index('MaKh', 'IX_QldtOtoLichSuXuatFile_MaKh');
                }
            });
        }

        (new BieuMauXuatDongBo)->dongBoTuConfig();
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('QldtOtoLichSuXuatFile')) {
            $schema->table('QldtOtoLichSuXuatFile', function (Blueprint $table) use ($schema) {
                foreach (['BieuMauId', 'SoCongVan', 'NgayVanBan', 'MaKh'] as $col) {
                    if ($schema->hasColumn('QldtOtoLichSuXuatFile', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        $schema->dropIfExists('QldtOtoBieuMauXuat');
    }
};