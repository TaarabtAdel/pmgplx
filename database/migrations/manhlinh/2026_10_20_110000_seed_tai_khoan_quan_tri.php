<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Tạo sẵn tài khoản quản trị để đăng nhập lần đầu sau khi deploy (chỉ khi chưa có).
 */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    private const SO_DIEN_THOAI = 'quantri-ml';

    private const VAI_TRO = ['admin', 'dao_tao', 'tuyen_sinh', 'it'];

    public function up(): void
    {
        $schema = Schema::connection($this->connection);
        if (! $schema->hasTable('NguoiDung') || ! $schema->hasTable('NguoiDungVaiTro')) {
            return;
        }

        $conn = DB::connection($this->connection);
        if ($conn->table('NguoiDung')->where('SoDienThoai', self::SO_DIEN_THOAI)->exists()) {
            return;
        }

        $conn->transaction(function () use ($conn): void {
            $id = $conn->table('NguoiDung')->insertGetId([
                'Ten' => 'Quản trị Mạnh Linh',
                'SoDienThoai' => self::SO_DIEN_THOAI,
                'MatKhau' => Hash::make('ML2026@'),
                'NgayTao' => now(),
                'NgayCapNhat' => now(),
            ]);

            foreach (self::VAI_TRO as $maVaiTro) {
                $conn->table('NguoiDungVaiTro')->insert([
                    'NguoiDungId' => $id,
                    'MaVaiTro' => $maVaiTro,
                ]);
            }
        });
    }

    public function down(): void
    {
        // Không xóa tài khoản khi rollback.
    }
};
