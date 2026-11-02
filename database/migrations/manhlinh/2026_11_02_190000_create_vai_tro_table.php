<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Danh mục vai trò (trước đây cố định trong NguoiDung::VAI_TRO) — thêm / đổi tên / xoá ở Hệ Thống → Vai trò.
 * LaHeThong = 1: vai trò Admin (luôn đủ quyền, không xoá, không đổi mã).
 */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    private const MAC_DINH = [
        ['admin', 'Admin', true],
        ['dao_tao', 'Đào Tạo', false],
        ['tuyen_sinh', 'Tuyển Sinh', false],
        ['it', 'IT', false],
    ];

    public function up(): void
    {
        $schema = Schema::connection($this->connection);
        if ($schema->hasTable('VaiTro')) {
            return;
        }

        $schema->create('VaiTro', function (Blueprint $table) {
            $table->increments('Id');
            $table->string('MaVaiTro', 50);
            $table->string('TenVaiTro', 100);
            $table->unsignedInteger('ThuTu')->default(0);
            $table->boolean('LaHeThong')->default(false);
            $table->dateTime('NgayTao')->nullable();

            $table->unique('MaVaiTro', 'UX_VaiTro_MaVaiTro');
        });

        $now = now();
        foreach (self::MAC_DINH as $i => [$ma, $ten, $heThong]) {
            DB::connection($this->connection)->table('VaiTro')->insert([
                'MaVaiTro' => $ma,
                'TenVaiTro' => $ten,
                'ThuTu' => $i + 1,
                'LaHeThong' => $heThong,
                'NgayTao' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('VaiTro');
    }
};
