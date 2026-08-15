<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        DB::connection('sqlsrv_manhlinh')->unprepared(<<<'SQL'
IF COL_LENGTH('dbo.TienDoDaoTao', 'TuNgay') IS NULL
    ALTER TABLE dbo.TienDoDaoTao ADD TuNgay DATE NULL;

IF COL_LENGTH('dbo.TienDoDaoTao', 'DenNgay') IS NULL
    ALTER TABLE dbo.TienDoDaoTao ADD DenNgay DATE NULL;

IF COL_LENGTH('dbo.TienDoDaoTao', 'TuNgayDenNgay') IS NOT NULL
    ALTER TABLE dbo.TienDoDaoTao DROP COLUMN TuNgayDenNgay;
SQL);
    }

    public function down(): void
    {
        DB::connection('sqlsrv_manhlinh')->unprepared(<<<'SQL'
IF COL_LENGTH('dbo.TienDoDaoTao', 'TuNgayDenNgay') IS NULL
    ALTER TABLE dbo.TienDoDaoTao ADD TuNgayDenNgay NVARCHAR(30) NULL;

IF COL_LENGTH('dbo.TienDoDaoTao', 'TuNgay') IS NOT NULL
    ALTER TABLE dbo.TienDoDaoTao DROP COLUMN TuNgay;

IF COL_LENGTH('dbo.TienDoDaoTao', 'DenNgay') IS NOT NULL
    ALTER TABLE dbo.TienDoDaoTao DROP COLUMN DenNgay;
SQL);
    }
};
