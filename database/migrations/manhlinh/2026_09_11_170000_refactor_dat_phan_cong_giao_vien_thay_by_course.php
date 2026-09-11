<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('DatPhanCongGiaoVienThay')) {
            $schema->create('DatPhanCongGiaoVienThay', function (Blueprint $table) {
                $table->increments('Id');
                $table->string('MaKhoaHoc', 50);
                $table->string('MaGiaoVienGoc', 50);
                $table->string('MaGiaoVien', 50);
                $table->date('TuNgay');
                $table->date('DenNgay')->nullable();
                $table->dateTime('NgayNhap')->nullable();

                $table->index(['MaKhoaHoc', 'MaGiaoVienGoc'], 'IX_DatPhanCongGiaoVienThay_Khoa_GvGoc');
                $table->index(['MaKhoaHoc', 'MaGiaoVienGoc', 'TuNgay'], 'IX_DatPhanCongGiaoVienThay_Khoa_GvGoc_TuNgay');
            });

            return;
        }

        if (! $schema->hasColumn('DatPhanCongGiaoVienThay', 'MaKhoaHoc')) {
            $schema->table('DatPhanCongGiaoVienThay', function (Blueprint $table) {
                $table->string('MaKhoaHoc', 50)->nullable();
                $table->string('MaGiaoVienGoc', 50)->nullable();
            });
        }

        if ($schema->hasColumn('DatPhanCongGiaoVienThay', 'PhanCongId')) {
            DB::connection($this->connection)->statement('
                UPDATE t
                SET t.MaKhoaHoc = p.MaKhoaHoc,
                    t.MaGiaoVienGoc = p.MaGiaoVien
                FROM DatPhanCongGiaoVienThay t
                INNER JOIN DatPhanCongHocVien p ON p.Id = t.PhanCongId
                WHERE t.MaKhoaHoc IS NULL OR t.MaGiaoVienGoc IS NULL
            ');

            DB::connection($this->connection)->statement('
                DELETE FROM DatPhanCongGiaoVienThay
                WHERE MaKhoaHoc IS NULL OR MaGiaoVienGoc IS NULL
            ');

            $schema->table('DatPhanCongGiaoVienThay', function (Blueprint $table) {
                $table->dropIndex('IX_DatPhanCongGiaoVienThay_PhanCongId');
                $table->dropIndex('IX_DatPhanCongGiaoVienThay_PhanCong_TuNgay');
            });

            $schema->table('DatPhanCongGiaoVienThay', function (Blueprint $table) {
                $table->dropColumn('PhanCongId');
            });
        }

        $schema->table('DatPhanCongGiaoVienThay', function (Blueprint $table) {
            if (! $this->indexExists('DatPhanCongGiaoVienThay', 'IX_DatPhanCongGiaoVienThay_Khoa_GvGoc')) {
                $table->index(['MaKhoaHoc', 'MaGiaoVienGoc'], 'IX_DatPhanCongGiaoVienThay_Khoa_GvGoc');
            }
            if (! $this->indexExists('DatPhanCongGiaoVienThay', 'IX_DatPhanCongGiaoVienThay_Khoa_GvGoc_TuNgay')) {
                $table->index(['MaKhoaHoc', 'MaGiaoVienGoc', 'TuNgay'], 'IX_DatPhanCongGiaoVienThay_Khoa_GvGoc_TuNgay');
            }
        });
    }

    public function down(): void
    {
        // Không hoàn tác refactor cấu trúc.
    }

    private function indexExists(string $table, string $index): bool
    {
        $row = DB::connection($this->connection)->selectOne(
            'SELECT 1 AS ok FROM sys.indexes WHERE name = ? AND object_id = OBJECT_ID(?)',
            [$index, $table]
        );

        return $row !== null;
    }
};
