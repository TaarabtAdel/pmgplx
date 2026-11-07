<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Loại khoá gán riêng cho khoá thực tế (vd. B01K41 = «SỐ TỰ ĐỘNG - Đêm»); khoá không gán thì theo hạng
 * (B01 = SỐ TỰ ĐỘNG, C = C1, B = SỐ SÀN). Đổi tên 3 loại khoá dự kiến mặc định cho cùng cách gọi.
 *
 *   php artisan migrate --database=sqlsrv_manhlinh --path=database/migrations/manhlinh
 */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    private const DOI_TEN = [
        'B01 (tự động)' => 'SỐ TỰ ĐỘNG',
        'C1 (hạng C)' => 'C1',
        'B (số sàn)' => 'SỐ SÀN',
    ];

    public function up(): void
    {
        $schema = Schema::connection($this->connection);
        $conn = DB::connection($this->connection);

        if (! $schema->hasTable('TienDoThucTeLoaiKhoa')) {
            $schema->create('TienDoThucTeLoaiKhoa', function (Blueprint $table) {
                $table->increments('Id');
                $table->string('MaKhoaLop', 20)->unique('UX_TienDoThucTeLoaiKhoa_MaKhoaLop');
                $table->string('TenLoaiKhoa', 100);
                $table->dateTime('NgayTao');
            });

            $now = now();
            $conn->table('TienDoThucTeLoaiKhoa')->insert([
                ['MaKhoaLop' => 'B01K41', 'TenLoaiKhoa' => 'SỐ TỰ ĐỘNG - Đêm', 'NgayTao' => $now],
                ['MaKhoaLop' => 'B01K42', 'TenLoaiKhoa' => 'SỐ TỰ ĐỘNG - 25 người', 'NgayTao' => $now],
            ]);
        }

        foreach (self::DOI_TEN as $cu => $moi) {
            $conn->table('TienDoDuKienLoaiKhoa')->where('TenLoai', $cu)->update(['TenLoai' => $moi]);
        }
    }

    public function down(): void
    {
        $conn = DB::connection($this->connection);
        foreach (self::DOI_TEN as $cu => $moi) {
            $conn->table('TienDoDuKienLoaiKhoa')->where('TenLoai', $moi)->update(['TenLoai' => $cu]);
        }

        Schema::connection($this->connection)->dropIfExists('TienDoThucTeLoaiKhoa');
    }
};
