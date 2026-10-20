<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Một học viên có thể thuộc nhiều GV theo từng khoảng ngày (TuNgay–DenNgay, NULL = mở).
 * Bỏ unique (MaKhoaHoc, MaHocVien); việc chống chồng khoảng ngày do ứng dụng kiểm tra.
 */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);
        $conn = DB::connection($this->connection);

        $schema->table('DatPhanCongHocVien', function (Blueprint $table) use ($schema) {
            if (! $schema->hasColumn('DatPhanCongHocVien', 'TuNgay')) {
                $table->date('TuNgay')->nullable();
            }
            if (! $schema->hasColumn('DatPhanCongHocVien', 'DenNgay')) {
                $table->date('DenNgay')->nullable();
            }
        });

        $this->dropIndexIfExists($conn, 'UQ_DatPhanCongHocVien_Khoa_MaHV');
        $conn->statement(
            "IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'IX_DatPhanCongHocVien_Khoa_MaHV' AND object_id = OBJECT_ID(N'dbo.DatPhanCongHocVien'))
                CREATE INDEX [IX_DatPhanCongHocVien_Khoa_MaHV] ON [DatPhanCongHocVien]([MaKhoaHoc], [MaHocVien])"
        );
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);
        $conn = DB::connection($this->connection);

        $this->dropIndexIfExists($conn, 'IX_DatPhanCongHocVien_Khoa_MaHV');

        // Giữ dòng phân công mới nhất của mỗi HV trước khi khôi phục unique.
        $conn->statement(
            'WITH x AS (
                SELECT Id, ROW_NUMBER() OVER (PARTITION BY MaKhoaHoc, MaHocVien ORDER BY ISNULL(TuNgay, \'1900-01-01\') DESC, Id DESC) AS rn
                FROM DatPhanCongHocVien
            )
            DELETE FROM DatPhanCongHocVien WHERE Id IN (SELECT Id FROM x WHERE rn > 1)'
        );
        $conn->statement(
            'CREATE UNIQUE INDEX [UQ_DatPhanCongHocVien_Khoa_MaHV] ON [DatPhanCongHocVien]([MaKhoaHoc], [MaHocVien])'
        );

        $schema->table('DatPhanCongHocVien', function (Blueprint $table) use ($schema) {
            if ($schema->hasColumn('DatPhanCongHocVien', 'TuNgay')) {
                $table->dropColumn('TuNgay');
            }
            if ($schema->hasColumn('DatPhanCongHocVien', 'DenNgay')) {
                $table->dropColumn('DenNgay');
            }
        });
    }

    private function dropIndexIfExists($conn, string $index): void
    {
        $conn->statement(
            "IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'{$index}' AND object_id = OBJECT_ID(N'dbo.DatPhanCongHocVien'))
                DROP INDEX [{$index}] ON [DatPhanCongHocVien]"
        );
    }
};
