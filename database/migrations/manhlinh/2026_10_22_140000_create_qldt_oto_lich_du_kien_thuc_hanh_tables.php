<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lịch dự kiến thực hành (QLĐT Ô TÔ → Làm lịch dự kiến → Thực hành), ngày nghỉ, các cặp giáo viên sáng/chiều + xe
 * và các buổi đã sinh (chụp lại nội dung/giờ của bài dạy lúc sinh lịch).
 * Mã GV / biển số tham chiếu bảng GiaoVien / XeTap bên DB PMGPLX (khác DB nên không đặt khoá ngoại).
 */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('QldtOtoLichDuKienThucHanh')) {
            $schema->create('QldtOtoLichDuKienThucHanh', function (Blueprint $table) {
                $table->increments('Id');
                $table->string('TenKhoa', 255);
                $table->string('Hang', 10);
                $table->date('NgayBatDauThucHanh');
                $table->string('XeTuDong', 20)->nullable();
                $table->date('CabinCapTruocNgay')->nullable();
                $table->unsignedInteger('NguoiTaoId')->nullable();
                $table->dateTime('NgaySinhLich')->nullable();
                $table->dateTime('NgayTao')->nullable();
                $table->dateTime('NgayCapNhat')->nullable();

                $table->index(['Hang', 'NgayBatDauThucHanh'], 'IX_QldtOtoLichDuKienThucHanh_Hang_Ngay');
            });
        }

        if (! $schema->hasTable('QldtOtoLichDuKienThucHanhNgayNghi')) {
            $schema->create('QldtOtoLichDuKienThucHanhNgayNghi', function (Blueprint $table) {
                $table->increments('Id');
                $table->unsignedInteger('LichDuKienId');
                $table->date('Ngay');
                $table->string('GhiChu', 255)->nullable();

                $table->unique(['LichDuKienId', 'Ngay'], 'UX_QldtOtoLichDuKienThucHanhNgayNghi_Lich_Ngay');
                $table->foreign('LichDuKienId', 'FK_QldtOtoLichDuKienThucHanhNgayNghi_Lich')
                    ->references('Id')->on('QldtOtoLichDuKienThucHanh')
                    ->cascadeOnDelete();
            });
        }

        if (! $schema->hasTable('QldtOtoLichDuKienThucHanhCapGv')) {
            $schema->create('QldtOtoLichDuKienThucHanhCapGv', function (Blueprint $table) {
                $table->increments('Id');
                $table->unsignedInteger('LichDuKienId');
                $table->unsignedSmallInteger('ThuTu')->default(0);
                $table->string('MaGvSang', 20)->nullable();
                $table->string('MaGvChieu', 20)->nullable();
                $table->string('BienSoXe', 20)->nullable();

                $table->index(['LichDuKienId', 'ThuTu'], 'IX_QldtOtoLichDuKienThucHanhCapGv_Lich');
                $table->foreign('LichDuKienId', 'FK_QldtOtoLichDuKienThucHanhCapGv_Lich')
                    ->references('Id')->on('QldtOtoLichDuKienThucHanh')
                    ->cascadeOnDelete();
            });
        }

        if (! $schema->hasTable('QldtOtoLichDuKienThucHanhBuoi')) {
            $schema->create('QldtOtoLichDuKienThucHanhBuoi', function (Blueprint $table) {
                $table->increments('Id');
                $table->unsignedInteger('LichDuKienId');
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

                $table->index(['LichDuKienId', 'Ngay'], 'IX_QldtOtoLichDuKienThucHanhBuoi_Lich_Ngay');
                $table->foreign('LichDuKienId', 'FK_QldtOtoLichDuKienThucHanhBuoi_Lich')
                    ->references('Id')->on('QldtOtoLichDuKienThucHanh')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);
        $schema->dropIfExists('QldtOtoLichDuKienThucHanhBuoi');
        $schema->dropIfExists('QldtOtoLichDuKienThucHanhCapGv');
        $schema->dropIfExists('QldtOtoLichDuKienThucHanhNgayNghi');
        $schema->dropIfExists('QldtOtoLichDuKienThucHanh');
    }
};
