<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ngày nghỉ lễ, Tết năm 2027 (theo Bộ luật Lao động) + Ngày Nhà giáo 20/11 (trung tâm được nghỉ).
 * Tết Nguyên Đán Đinh Mùi (mùng 1 = 06/02/2027) lấy giống cách nghỉ năm 2026: từ 4 ngày trước Tết đến hết mùng 5;
 * khi Chính phủ công bố lịch nghỉ chính thức thì sửa trên màn Báo cáo Tiến độ đào tạo.
 *
 *   php artisan migrate --database=sqlsrv_manhlinh --path=database/migrations/manhlinh
 */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    private const NGAY_NGHI = [
        ['Ngày Nhà giáo Việt Nam 20/11', '2026-11-20', '2026-11-20'],
        ['Tết Dương lịch', '2027-01-01', '2027-01-01'],
        ['Nghỉ Tết Nguyên Đán', '2027-02-02', '2027-02-10'],
        ['Giỗ Tổ Hùng Vương (10/3 âm lịch)', '2027-04-16', '2027-04-16'],
        ['Nghỉ lễ 30/4 – 1/5 (nghỉ bù thứ 2, 03/05)', '2027-04-30', '2027-05-03'],
        ['Quốc khánh 2/9', '2027-09-02', '2027-09-03'],
        ['Ngày Nhà giáo Việt Nam 20/11', '2027-11-20', '2027-11-20'],
    ];

    public function up(): void
    {
        if (! Schema::connection($this->connection)->hasTable('TienDoDuKienNgayNghi')) {
            return;
        }

        $conn = DB::connection($this->connection);
        foreach (self::NGAY_NGHI as [$ten, $tu, $den]) {
            if ($conn->table('TienDoDuKienNgayNghi')->whereDate('TuNgay', $tu)->exists()) {
                continue;
            }
            $conn->table('TienDoDuKienNgayNghi')->insert([
                'TenNgayNghi' => $ten,
                'TuNgay' => $tu,
                'DenNgay' => $den,
                'NgayTao' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $conn = DB::connection($this->connection);
        foreach (self::NGAY_NGHI as [$ten, $tu]) {
            $conn->table('TienDoDuKienNgayNghi')->where('TenNgayNghi', $ten)->whereDate('TuNgay', $tu)->delete();
        }
    }
};
