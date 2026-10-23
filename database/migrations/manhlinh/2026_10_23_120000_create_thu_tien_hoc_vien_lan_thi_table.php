<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Các lần thi từng môn của học viên (thi lại lần 2, 3…). KetQua* trên ThuTienHocVien = kết quả lần thi mới nhất.
 * Kết quả đang có được chuyển thành lần thi 1 (giao dịch trừ phí đã có sẵn trong sổ nên không ghi thêm).
 */
return new class extends Migration
{
    protected $connection = 'sqlsrv_manhlinh';

    private const COT_KQ = ['ly_thuyet' => 'KetQuaLyThuyet', 'sa_hinh' => 'KetQuaSaHinh', 'duong' => 'KetQuaDuong'];

    public function up(): void
    {
        $schema = Schema::connection($this->connection);
        if (! $schema->hasTable('ThuTienHocVienLanThi')) {
            $schema->create('ThuTienHocVienLanThi', function (Blueprint $table) {
                $table->increments('Id');
                $table->unsignedInteger('ThuTienHocVienId');
                $table->string('Khoan', 20);
                $table->unsignedSmallInteger('LanThi');
                $table->string('KetQua', 10);
                $table->date('NgayThi')->nullable();
                $table->unsignedInteger('NguoiTaoId')->nullable();
                $table->dateTime('NgayTao');

                $table->unique(['ThuTienHocVienId', 'Khoan', 'LanThi'], 'UX_ThuTienHocVienLanThi_HV_Khoan_Lan');
                $table->foreign('ThuTienHocVienId', 'FK_ThuTienHocVienLanThi_HV')
                    ->references('Id')->on('ThuTienHocVien')
                    ->cascadeOnDelete();
            });
        }

        $conn = DB::connection($this->connection);
        if ($conn->table('ThuTienHocVienLanThi')->exists()) {
            return;
        }
        $rows = [];
        foreach ($conn->table('ThuTienHocVien')->get() as $hv) {
            foreach (self::COT_KQ as $khoan => $cot) {
                if (in_array($hv->{$cot}, ['dau', 'rot'], true)) {
                    $rows[] = [
                        'ThuTienHocVienId' => $hv->Id,
                        'Khoan' => $khoan,
                        'LanThi' => 1,
                        'KetQua' => $hv->{$cot},
                        'NgayThi' => null,
                        'NguoiTaoId' => null,
                        'NgayTao' => $hv->NgayCapNhat ?? Carbon::now(),
                    ];
                }
            }
        }
        foreach (array_chunk($rows, 200) as $chunk) {
            $conn->table('ThuTienHocVienLanThi')->insert($chunk);
        }
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('ThuTienHocVienLanThi');
    }
};
