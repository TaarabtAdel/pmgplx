<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lịch chính thức thực hành (QLĐT Ô TÔ → Làm lịch chính thức).
 * Sao chép từ lịch dự kiến, cho phép kéo lại thứ tự bài, xuất Excel và lưu vào PMGPLX.
 */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('QldtOtoLichChinhThuc')) {
            $schema->create('QldtOtoLichChinhThuc', function (Blueprint $table) {
                $table->increments('Id');
                $table->unsignedInteger('LichDuKienId')->nullable();
                $table->string('TenKhoa', 255);
                $table->string('MaKH', 50)->nullable();
                $table->string('Hang', 10);
                $table->date('NgayBatDauThucHanh');
                $table->string('XeTuDong', 20)->nullable();
                $table->date('CabinCapTruocNgay')->nullable();
                $table->unsignedInteger('NguoiTaoId')->nullable();
                $table->dateTime('NgayTao')->nullable();
                $table->dateTime('NgayCapNhat')->nullable();
                $table->dateTime('NgayLuuPm')->nullable();
                $table->unsignedInteger('SoBuoiLuuPm')->nullable();

                $table->index(['Hang', 'NgayBatDauThucHanh'], 'IX_QldtOtoLichChinhThuc_Hang_Ngay');
                $table->index(['LichDuKienId'], 'IX_QldtOtoLichChinhThuc_DuKien');
            });
        }

        if (! $schema->hasTable('QldtOtoLichChinhThucNgayNghi')) {
            $schema->create('QldtOtoLichChinhThucNgayNghi', function (Blueprint $table) {
                $table->increments('Id');
                $table->unsignedInteger('LichChinhThucId');
                $table->date('Ngay');
                $table->string('GhiChu', 255)->nullable();

                $table->unique(['LichChinhThucId', 'Ngay'], 'UX_QldtOtoLichChinhThucNgayNghi_Lich_Ngay');
                $table->foreign('LichChinhThucId', 'FK_QldtOtoLichChinhThucNgayNghi_Lich')
                    ->references('Id')->on('QldtOtoLichChinhThuc')
                    ->cascadeOnDelete();
            });
        }

        if (! $schema->hasTable('QldtOtoLichChinhThucCapGv')) {
            $schema->create('QldtOtoLichChinhThucCapGv', function (Blueprint $table) {
                $table->increments('Id');
                $table->unsignedInteger('LichChinhThucId');
                $table->unsignedSmallInteger('ThuTu')->default(0);
                $table->string('MaGvSang', 20)->nullable();
                $table->string('MaGvChieu', 20)->nullable();
                $table->string('BienSoXe', 20)->nullable();

                $table->index(['LichChinhThucId', 'ThuTu'], 'IX_QldtOtoLichChinhThucCapGv_Lich');
                $table->foreign('LichChinhThucId', 'FK_QldtOtoLichChinhThucCapGv_Lich')
                    ->references('Id')->on('QldtOtoLichChinhThuc')
                    ->cascadeOnDelete();
            });
        }

        if (! $schema->hasTable('QldtOtoLichChinhThucBuoi')) {
            $schema->create('QldtOtoLichChinhThucBuoi', function (Blueprint $table) {
                $table->increments('Id');
                $table->unsignedInteger('LichChinhThucId');
                $table->unsignedSmallInteger('CapThuTu');
                $table->string('Ca', 10);
                $table->string('MaGv', 20)->nullable();
                $table->string('BienSoXe', 20)->nullable();
                $table->date('Ngay');
                $table->unsignedSmallInteger('NgaySo');
                $table->string('NoiDung', 500);
                $table->string('GioBatDau', 50)->nullable();
                $table->string('GioKetThuc', 50)->nullable();
                $table->decimal('SoGio', 5, 2)->nullable();
                $table->unsignedSmallInteger('ThuTuBai')->default(0);

                $table->index(['LichChinhThucId', 'Ngay'], 'IX_QldtOtoLichChinhThucBuoi_Lich_Ngay');
                $table->index(['LichChinhThucId', 'CapThuTu', 'Ca', 'ThuTuBai'], 'IX_QldtOtoLichChinhThucBuoi_ThuTu');
                $table->foreign('LichChinhThucId', 'FK_QldtOtoLichChinhThucBuoi_Lich')
                    ->references('Id')->on('QldtOtoLichChinhThuc')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);
        $schema->dropIfExists('QldtOtoLichChinhThucBuoi');
        $schema->dropIfExists('QldtOtoLichChinhThucCapGv');
        $schema->dropIfExists('QldtOtoLichChinhThucNgayNghi');
        $schema->dropIfExists('QldtOtoLichChinhThuc');
    }
};
