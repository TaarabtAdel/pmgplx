<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SQL Server: UNIQUE trên cột nullable chỉ cho 1 giá trị NULL.
 * Chuyển MaGV / MaKhoa sang filtered unique index.
 */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('GiaoVien')) {
            return;
        }

        $conn = DB::connection($this->connection);

        $this->dropIndexIfExists($conn, 'GiaoVien', 'giaovien_magv_unique');
        $conn->statement(
            'CREATE UNIQUE INDEX [IX_GiaoVien_MaGV] ON [GiaoVien]([MaGV]) WHERE [MaGV] IS NOT NULL'
        );

        $this->dropIndexIfExists($conn, 'KhoaDaoTao', 'khoadaotao_makhoa_unique');
        $conn->statement(
            'CREATE UNIQUE INDEX [IX_KhoaDaoTao_MaKhoa] ON [KhoaDaoTao]([MaKhoa]) WHERE [MaKhoa] IS NOT NULL'
        );
    }

    public function down(): void
    {
        if (! Schema::connection($this->connection)->hasTable('GiaoVien')) {
            return;
        }

        $conn = DB::connection($this->connection);

        $this->dropIndexIfExists($conn, 'GiaoVien', 'IX_GiaoVien_MaGV');
        $conn->statement(
            'CREATE UNIQUE INDEX [giaovien_magv_unique] ON [GiaoVien]([MaGV])'
        );

        $this->dropIndexIfExists($conn, 'KhoaDaoTao', 'IX_KhoaDaoTao_MaKhoa');
        $conn->statement(
            'CREATE UNIQUE INDEX [khoadaotao_makhoa_unique] ON [KhoaDaoTao]([MaKhoa])'
        );
    }

    private function dropIndexIfExists($conn, string $table, string $index): void
    {
        $conn->statement(
            "IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = N'{$index}' AND object_id = OBJECT_ID(N'dbo.{$table}'))
                DROP INDEX [{$index}] ON [{$table}]"
        );
    }
};
