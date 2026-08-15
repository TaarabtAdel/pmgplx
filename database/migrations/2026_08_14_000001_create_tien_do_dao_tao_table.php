<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        DB::connection('sqlsrv_manhlinh')->unprepared(<<<'SQL'
IF OBJECT_ID(N'dbo.TienDoDaoTao', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.TienDoDaoTao
    (
        Id                  INT IDENTITY(1,1) PRIMARY KEY,
        SoTT                INT             NULL,
        MaKhoaLop           NVARCHAR(50)    NOT NULL,
        GiaoVienDay         NVARCHAR(100)   NULL,
        SoLuongHocVien      INT             NULL,
        SoHocVienTotNghiep  INT             NULL,
        NamHoc              SMALLINT        NULL,

        ThangNam            NVARCHAR(20)    NULL,
        TuanThu             TINYINT         NULL,
        TuNgayDenNgay       NVARCHAR(30)    NULL,

        KyHieu              NVARCHAR(10)    NULL,
        GhiChu              NVARCHAR(500)   NULL
    );

    CREATE INDEX IX_TienDoDaoTao_Lop ON dbo.TienDoDaoTao (MaKhoaLop, NamHoc);
END
SQL);
    }

    public function down(): void
    {
        DB::connection('sqlsrv_manhlinh')->unprepared(<<<'SQL'
IF OBJECT_ID(N'dbo.TienDoDaoTao', N'U') IS NOT NULL
    DROP TABLE dbo.TienDoDaoTao;
SQL);
    }
};
