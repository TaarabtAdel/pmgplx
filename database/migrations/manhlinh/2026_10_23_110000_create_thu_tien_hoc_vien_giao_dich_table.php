<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sổ thu/trừ tiền học viên: số tiền từng khoản (LT, Sa hình, Đường, Bìa) = tổng SoTien các giao dịch (thu dương, trừ âm).
 * Học viên đã có trong ThuTienHocVien được chuyển trạng thái hiện tại thành giao dịch để số tiền không đổi.
 */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    /** Mức phí lúc chuyển dữ liệu (giống ThuTienHocVien::PHI tại thời điểm tạo migration). */
    private const PHI = ['ly_thuyet' => 100000, 'sa_hinh' => 350000, 'duong' => 80000, 'bia' => 115000];

    private const COT_KQ = ['ly_thuyet' => 'KetQuaLyThuyet', 'sa_hinh' => 'KetQuaSaHinh', 'duong' => 'KetQuaDuong'];

    private const NHAN = ['ly_thuyet' => 'Lý thuyết', 'sa_hinh' => 'Sa hình', 'duong' => 'Đường', 'bia' => 'Bìa'];

    public function up(): void
    {
        $schema = Schema::connection($this->connection);
        if (! $schema->hasTable('ThuTienHocVienGiaoDich')) {
            $schema->create('ThuTienHocVienGiaoDich', function (Blueprint $table) {
                $table->increments('Id');
                $table->unsignedInteger('ThuTienHocVienId');
                $table->string('Khoan', 20);
                $table->integer('SoTien');
                $table->string('Nguon', 20);
                $table->string('NoiDung', 255)->nullable();
                $table->unsignedInteger('NguoiTaoId')->nullable();
                $table->dateTime('NgayTao');

                $table->index(['ThuTienHocVienId', 'Khoan'], 'IX_ThuTienHocVienGiaoDich_HV_Khoan');
                $table->foreign('ThuTienHocVienId', 'FK_ThuTienHocVienGiaoDich_HV')
                    ->references('Id')->on('ThuTienHocVien')
                    ->cascadeOnDelete();
            });
        }

        $conn = DB::connection($this->connection);
        if ($conn->table('ThuTienHocVienGiaoDich')->exists()) {
            return;
        }
        $now = Carbon::now();
        $rows = [];
        foreach ($conn->table('ThuTienHocVien')->get() as $hv) {
            if ($hv->DaThuTien) {
                foreach (self::PHI as $khoan => $phi) {
                    $rows[] = $this->dong($hv->Id, $khoan, $phi, 'Đã thu tiền (chuyển từ dữ liệu trước)', $hv->NgayThuTien ?? $now);
                }
            }
            foreach (self::COT_KQ as $khoan => $cot) {
                $kq = $hv->{$cot};
                $phi = self::PHI[$khoan];
                $mon = 'KQ '.self::NHAN[$khoan].': ';
                $ngay = $hv->NgayCapNhat ?? $now;
                if ($kq === 'dau') {
                    $rows[] = $this->dong($hv->Id, $khoan, -$phi, $mon.'Đậu (chuyển từ dữ liệu trước)', $ngay);
                } elseif ($kq === 'rot') {
                    // Rớt: trừ phí thi (về 0); số phải thu lại tính từ kết quả, không ghi thêm dòng.
                    $rows[] = $this->dong($hv->Id, $khoan, -$phi, $mon.'Rớt (-'.number_format($phi, 0, ',', '.').') (chuyển từ dữ liệu trước)', $ngay);
                }
            }
        }
        foreach (array_chunk($rows, 200) as $chunk) {
            $conn->table('ThuTienHocVienGiaoDich')->insert($chunk);
        }
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('ThuTienHocVienGiaoDich');
    }

    /** @return array<string, mixed> */
    private function dong(int $id, string $khoan, int $soTien, string $noiDung, mixed $ngay): array
    {
        return [
            'ThuTienHocVienId' => $id,
            'Khoan' => $khoan,
            'SoTien' => $soTien,
            'Nguon' => 'tu_dong',
            'NoiDung' => $noiDung,
            'NguoiTaoId' => null,
            'NgayTao' => $ngay,
        ];
    }
};
