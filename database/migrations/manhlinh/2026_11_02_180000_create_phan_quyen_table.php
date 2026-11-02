<?php

use App\Models\HeThong\NguoiDung;
use App\Support\HeThong\CayChucNang;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ma trận phân quyền: mỗi dòng = vai trò được dùng một chức năng (nút lá của CayChucNang: Tab/Menu 1/Menu 2).
 * Admin luôn đủ quyền, không lưu ở đây.
 * Lúc tạo: các vai trò khác được cấp toàn bộ chức năng đang có (giữ nguyên như trước khi có phân quyền),
 * trừ Hệ Thống → Vai trò → Phân quyền (chỉ Admin).
 *
 *   php artisan migrate --database=sqlsrv_manhlinh --path=database/migrations/manhlinh
 */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);
        if ($schema->hasTable('PhanQuyen')) {
            return;
        }

        $schema->create('PhanQuyen', function (Blueprint $table) {
            $table->increments('Id');
            $table->string('MaVaiTro', 50);
            $table->string('MaChucNang', 255);
            $table->unsignedInteger('NguoiCapNhatId')->nullable();
            $table->dateTime('NgayCapNhat')->nullable();

            $table->unique(['MaVaiTro', 'MaChucNang'], 'UX_PhanQuyen_VaiTro_ChucNang');
        });

        $now = now();
        $rows = [];
        foreach (array_keys(NguoiDung::VAI_TRO_MAC_DINH) as $vaiTro) {
            if ($vaiTro === 'admin') {
                continue;
            }
            foreach (CayChucNang::tatCaKhoaLa() as $khoa) {
                if ($khoa === 'he-thong/vai-tro/phan-quyen') {
                    continue;
                }
                $rows[] = ['MaVaiTro' => $vaiTro, 'MaChucNang' => $khoa, 'NgayCapNhat' => $now];
            }
        }
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::connection($this->connection)->table('PhanQuyen')->insert($chunk);
        }
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('PhanQuyen');
    }
};
