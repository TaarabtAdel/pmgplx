<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kết quả đối chiếu từng trường dò được với PMGPLX: 1 = khớp, 0 = không khớp, NULL = chưa dò trường đó.
 * Tạm thời chỉ lưu trạng thái này; các cột giá trị (HoTen, SoCCCD…) để dành, chưa ghi.
 */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    private const COT = [
        'KhopHoTen', 'KhopSoCCCD', 'KhopNgaySinh', 'KhopNoiThuongTru',
        'KhopNgayCapCCCD', 'KhopSoGPLX', 'KhopNgayCapGPLX', 'KhopNgayTrungTuyen',
    ];

    public function up(): void
    {
        $schema = Schema::connection($this->connection);
        if (! $schema->hasTable('HocVienDoThongTin') || $schema->hasColumn('HocVienDoThongTin', 'KhopHoTen')) {
            return;
        }

        $schema->table('HocVienDoThongTin', function (Blueprint $table) {
            foreach (self::COT as $cot) {
                $table->boolean($cot)->nullable();
            }
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);
        if (! $schema->hasTable('HocVienDoThongTin') || ! $schema->hasColumn('HocVienDoThongTin', 'KhopHoTen')) {
            return;
        }

        $schema->table('HocVienDoThongTin', function (Blueprint $table) {
            $table->dropColumn(self::COT);
        });
    }
};
