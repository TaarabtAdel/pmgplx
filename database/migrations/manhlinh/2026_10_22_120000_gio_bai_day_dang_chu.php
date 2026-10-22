<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Giờ BĐ / Giờ KT của bài dạy lưu nguyên văn (vd "13H59' -17H59'", "5h59") thay vì kiểu time. */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        DB::connection($this->connection)->statement('ALTER TABLE [QldtOtoBaiDay] ALTER COLUMN [GioBatDau] NVARCHAR(50) NULL');
        DB::connection($this->connection)->statement('ALTER TABLE [QldtOtoBaiDay] ALTER COLUMN [GioKetThuc] NVARCHAR(50) NULL');
    }

    public function down(): void
    {
        $conn = DB::connection($this->connection);
        // Giá trị không đổi được sang time thì bỏ trống.
        $conn->statement('UPDATE [QldtOtoBaiDay] SET [GioBatDau] = NULL WHERE TRY_CONVERT(time, [GioBatDau]) IS NULL');
        $conn->statement('UPDATE [QldtOtoBaiDay] SET [GioKetThuc] = NULL WHERE TRY_CONVERT(time, [GioKetThuc]) IS NULL');
        $conn->statement('ALTER TABLE [QldtOtoBaiDay] ALTER COLUMN [GioBatDau] TIME NULL');
        $conn->statement('ALTER TABLE [QldtOtoBaiDay] ALTER COLUMN [GioKetThuc] TIME NULL');
    }
};
