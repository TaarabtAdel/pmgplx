<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        DB::connection('sqlsrv_manhlinh')->unprepared(<<<'SQL'
IF COL_LENGTH('dbo.TienDoDaoTao', 'SoTT') IS NOT NULL
    ALTER TABLE dbo.TienDoDaoTao DROP COLUMN SoTT;
SQL);
    }

    public function down(): void
    {
        DB::connection('sqlsrv_manhlinh')->unprepared(<<<'SQL'
IF COL_LENGTH('dbo.TienDoDaoTao', 'SoTT') IS NULL
    ALTER TABLE dbo.TienDoDaoTao ADD SoTT INT NULL;
SQL);
    }
};
