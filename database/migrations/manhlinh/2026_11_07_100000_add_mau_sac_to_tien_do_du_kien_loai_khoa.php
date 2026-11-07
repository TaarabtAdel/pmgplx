<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    /** Màu phân biệt loại khoá trên bảng tiến độ (thay cho cột Thứ tự trên giao diện). */
    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('TienDoDuKienLoaiKhoa') && ! $schema->hasColumn('TienDoDuKienLoaiKhoa', 'MauSac')) {
            $schema->table('TienDoDuKienLoaiKhoa', function (Blueprint $table) {
                $table->string('MauSac', 7)->nullable();
            });
        }

        $conn = DB::connection($this->connection);
        $palette = ['#FFB3B3', '#FFD966', '#C5E0B4', '#B4C7E7', '#D9B3FF', '#F8CBAD', '#A9D18E'];
        $rows = $conn->table('TienDoDuKienLoaiKhoa')->orderBy('ThuTu')->orderBy('Id')->get();
        foreach ($rows as $i => $row) {
            if (! empty($row->MauSac)) {
                continue;
            }
            $conn->table('TienDoDuKienLoaiKhoa')->where('Id', $row->Id)->update([
                'MauSac' => $palette[$i % count($palette)],
            ]);
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);
        if ($schema->hasColumn('TienDoDuKienLoaiKhoa', 'MauSac')) {
            $schema->table('TienDoDuKienLoaiKhoa', function (Blueprint $table) {
                $table->dropColumn('MauSac');
            });
        }
    }
};
