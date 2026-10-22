<?php

namespace Database\Seeders;

use App\Models\QldtOto\BaiDay;
use App\Models\QldtOto\BaiHoc;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Bài học + bài dạy theo hạng/ca (màn Hệ Thống → Cấu hình bài dạy theo hạng).
 * Chạy: php artisan db:seed --class=QldtOtoBaiDaySeeder --force
 *
 * DU_LIEU giữ nguyên dạng dòng Excel. Mỗi Nội dung khác nhau trong một hạng + ca là một bài học (QldtOtoBaiHoc),
 * khung giờ lấy theo dòng đầu tiên có Nội dung đó; bài học đã có (cùng hạng, ca, tên) được cập nhật khung giờ.
 * Mỗi nhóm 'Hạng|ca' có trong DU_LIEU: xoá các ngày cũ rồi ghi lại đúng danh sách (theo thứ tự);
 * nhóm không có trong DU_LIEU giữ nguyên.
 *
 * Dữ liệu xuất từ màn cấu hình bài dạy (B sáng/chiều, B01 sáng/chiều) ngày 22/10/2026.
 * Thêm hạng/ca từ Excel: php database/seeders/cong-cu/excel-bai-day.php <file.xlsx> <Hang> <Ca>
 */
class QldtOtoBaiDaySeeder extends Seeder
{
    /** @var array<string, list<array{0: int, 1: float|int|null, 2: string, 3: ?string, 4: ?string}>> */
    private const DU_LIEU = [
        'B|sang' => [
            // [Ngày số, Số giờ, Nội dung, Giờ BĐ, Giờ KT]
            [1, 8, 'Hình (S)', '5h59', '13h59'],
            [2, 8, 'Hình (S)', '5h59', '13h59'],
            [3, 8, 'Hình (S)', '5h59', '13h59'],
            [4, 8, 'Hình (S)', '5h59', '13h59'],
            [5, 10, 'Cabin', '7h00', '17h00'],
            [6, 5, 'Tự động (S)', '7h00', '12h00'],
            [7, 5, 'Tự động (S)', '7h00', '12h00'],
            [8, 8, 'Phức tạp(4) - Ban đêm', '13H59\' -17H59\'', '18h00-22h00'],
            [9, 8, 'Phức tạp(4) - Ban đêm', '13H59\' -17H59\'', '18h00-22h00'],
            [10, 8, 'Phức tạp(4) - Ban đêm', '13H59\' -17H59\'', '18h00-22h00'],
            [11, 8, 'Phức tạp(4) - Ban đêm', '13H59\' -17H59\'', '18h00-22h00'],
            [12, 8, 'Phức tạp(4) - Ban đêm', '13H59\' -17H59\'', '18h00-22h00'],
            [13, 8, 'Phức tạp(4) - Phức tạp(4)', '5H58\'', '13H58\''],
            [14, 8, 'Phức tạp(4) - Phức tạp(4)', '5H58\'', '13H58\''],
            [15, 8, 'Phức tạp(4) - Phức tạp(4)', '5H58\'', '13H58\''],
            [16, 8, 'Phức tạp(4) - Phức tạp(4)', '5H58\'', '13H58\''],
            [17, 8, 'Phức tạp(4) - Phức tạp(4)', '5H58\'', '13H58\''],
            [18, 8, 'Phức tạp (S6) - Phức tạp(2)', '5H59 -11h59', '20h01-22h01'],
            [19, 6, 'Phức tạp (S6)', '5H59 -11h59', null],
            [20, 8, 'Phức tạp - Có tải (S8)', '5H59 -11h59', '20H01\'-22H01\''],
            [21, 8, 'Phức tạp - Có tải (S8)', '5H59 -11h59', '20H01\'-22H01\''],
            [22, 8, 'DỐC, QC - Có tải (S8)', '5H59 -11h59', '20H01\'-22H01\''],
            [23, 8, 'DỐC, QC - Có tải (S8)', '5H59 -11h59', '20H01\'-22H01\''],
            [24, 8, 'DỐC, QC - Có tải (S8)', '5H59 -11h59', '20H01\'-22H01\''],
            [25, 8, 'DỐC, QC - Có tải (S8)', '5H59 -11h59', '20H01\'-22H01\''],
            [26, 8, 'DỐC, QC - Có tải (S8)', '5H59 -11h59', '20H01\'-22H01\''],
            [27, 8, 'DỐC, QC - Có tải (S8)', '5H59 -11h59', '20H01\'-22H01\''],
            [28, 8, 'DỐC, QC - Có tải (S8)', '5H59 -11h59', '20H01\'-22H01\''],
            [29, 8, 'Cao Tốc - Có tải (S8)', '5H59 -11h59', '20H01\'-22H01\''],
            [30, 8, 'Cao Tốc - Có tải (S8)', '5H59 -11h59', '20H01\'-22H01\''],
            [31, 8, 'Có tải - Có tải (S8)', '5H59 -11h59', '20H01\'-22H01\''],
            [32, 8, 'Bổ sung (S)', '5H59 -11h59', '20H01\'-22H01\''],
            [33, 8, 'Bổ sung tự động (S)', '5H59 -11h59', '20H01\'-22H01\''],
            [34, 8, 'Hình (S)', '5h59', '13h59'],
            [35, 8, 'Hình (S)', '5h59', '13h59'],
            [36, 8, 'Hình (S)', '5h59', '13h59'],
            [37, 8, 'Hình (S)', '5h59', '13h59'],
            [38, 8, 'Hình (S)', '5h59', '13h59'],
            [39, 8, 'Hình (S)', '5h59', '13h59'],
            [40, 8, 'Ôn luyện', '5H59', '13H59'],
            [41, 8, 'Ôn luyện', '5H59', '13H59'],
            [42, null, 'Kiểm tra', null, null],
        ],
        'B|chieu' => [
            // [Ngày số, Số giờ, Nội dung, Giờ BĐ, Giờ KT]
            [1, 8, 'Hình (C)', '14h00', '22h00'],
            [2, 8, 'Hình (C)', '14h00', '22h00'],
            [3, 8, 'Hình (C)', '14h00', '22h00'],
            [4, 8, 'Hình (C)', '14h00', '22h00'],
            [5, 10, 'Cabin', '7h00', '17h00'],
            [6, 5, 'Tự động (C)', '13h00', '18h00'],
            [7, 5, 'Tự động (C)', '13h00', '18h00'],
            [8, 8, 'Phức tạp(4) - Phức tạp(4)', '5H58\'', '13H58\''],
            [9, 8, 'Phức tạp(4) - Phức tạp(4)', '5H58\'', '13H58\''],
            [10, 8, 'Phức tạp(4) - Phức tạp(4)', '5H58\'', '13H58\''],
            [11, 8, 'Phức tạp(4) - Phức tạp(4)', '5H58\'', '13H58\''],
            [12, 8, 'Phức tạp(4) - Phức tạp(4)', '5H58\'', '13H58\''],
            [13, 8, 'Phức tạp(4) - Ban đêm', '13H59\' -17H59\'', '18h00-22h00'],
            [14, 8, 'Phức tạp(4) - Ban đêm', '13H59\' -17H59\'', '18h00-22h00'],
            [15, 8, 'Phức tạp(4) - Ban đêm', '13H59\' -17H59\'', '18h00-22h00'],
            [16, 8, 'Phức tạp(4) - Ban đêm', '13H59\' -17H59\'', '18h00-22h00'],
            [17, 8, 'Phức tạp(4) - Ban đêm', '13H59\' -17H59\'', '18h00-22h00'],
            [18, 8, 'Phức tạp (C6) - Phức tạp(2)', '12h00\'', '20h00\''],
            [19, 6, 'Phức tạp (C6)', '12H00\'', '18H00\''],
            [20, 8, 'Phức tạp - Có tải (C8)', '12H00\'', '20H00'],
            [21, 8, 'Phức tạp - Có tải (C8)', '12H00\'', '20H00'],
            [22, 8, 'DỐC, QC - Có tải (C8)', '12H00\'', '20H00'],
            [23, 8, 'DỐC, QC - Có tải (C8)', '12H00\'', '20H00'],
            [24, 8, 'DỐC, QC - Có tải (C8)', '12H00\'', '20H00'],
            [25, 8, 'DỐC, QC - Có tải (C8)', '12H00\'', '20H00'],
            [26, 8, 'DỐC, QC - Có tải (C8)', '12H00\'', '20H00'],
            [27, 8, 'DỐC, QC - Có tải (C8)', '12H00\'', '20H00'],
            [28, 8, 'DỐC, QC - Có tải (C8)', '12H00\'', '20H00'],
            [29, 8, 'Cao Tốc - Có tải (C8)', '12H00\'', '20H00'],
            [30, 8, 'Cao Tốc - Có tải (C8)', '12H00\'', '20H00'],
            [31, 8, 'Có tải - Có tải (C8)', '12H00\'', '20H00'],
            [32, 8, 'Bổ sung (C)', '12H00\'', '20H00'],
            [33, 8, 'Bổ sung tự động (C)', '12H00\'', '20H00'],
            [34, 8, 'Hình (C)', '14h00', '22h00'],
            [35, 8, 'Hình (C)', '14h00', '22h00'],
            [36, 8, 'Hình (C)', '14h00', '22h00'],
            [37, 8, 'Hình (C)', '14h00', '22h00'],
            [38, 8, 'Hình (C)', '14h00', '22h00'],
            [39, 8, 'Hình (C)', '14h00', '22h00'],
            [40, 8, 'Ôn luyện', '14h00', '22h00'],
            [41, 8, 'Ôn luyện', '14h00', '22h00'],
            [42, null, 'Kiểm tra', null, null],
        ],
        'B01|sang' => [
            // [Ngày số, Số giờ, Nội dung, Giờ BĐ, Giờ KT]
            [1, 6, 'Hình (S)', '5H59', '11H59'],
            [2, 4, 'Hình (S4)', '7H30', '11H30'],
            [3, 6, 'Hình (S)', '5H59', '11H59'],
            [4, 6, 'Hình (S)', '5H59', '11H59'],
            [5, 5, 'Hình (S5)', '6H30', '11H30'],
            [6, 5, 'Hình (S5)', '6H30', '11H30'],
            [7, 10, 'Cabin', '7h00', '17h00'],
            [8, 8, 'Phức tạp(4) - Ban đêm', '13H-17H', '18H-22H'],
            [9, 8, 'Phức tạp(4) - Ban đêm', '13H-17H', '18H-22H'],
            [10, 8, 'Phức tạp(4) - Ban đêm', '13H-17H', '18H-22H'],
            [11, 8, 'Phức tạp(4) - Ban đêm', '13H-17H', '18H-22H'],
            [12, 8, 'Phức tạp(4) - Ban đêm', '13H-17H', '18H-22H'],
            [13, 6, 'Phức tạp (6)', '6H30', '12H30'],
            [14, 6, 'Phức tạp (6)', '6H30', '12H30'],
            [15, 6, 'Phức tạp 4 - Có tải 2', '6H30', '12H30'],
            [16, 6, 'Có tải 6', '6H30', '12H30'],
            [17, 5, 'Có tải 5', '7H30', '12H30'],
            [18, 5, 'DỐC, QC - Có tải (S5)', '7H30', '12H30'],
            [19, 5, 'DỐC, QC - Có tải (S5)', '7H30', '12H30'],
            [20, 5, 'DỐC, QC - Có tải (S5)', '7H30', '12H30'],
            [21, 5, 'DỐC, QC - Có tải (S5)', '7H30', '12H30'],
            [22, 5, 'DỐC, QC - Có tải (S5)', '7H30', '12H30'],
            [23, 5, 'Cao tốc (S)', '7H30', '12H30'],
            [24, 5, 'Cao tốc (S)', '7H30', '12H30'],
            [25, 4, 'Có tải (S4)', '7H30', '11H30'],
            [26, 4, 'Có tải (S4)', '7H30', '11H30'],
            [27, 4, 'Có tải (S4)', '7H30', '11H30'],
            [28, 4, 'Có tải (S4)', '7H30', '11H30'],
            [29, 8, 'Bổ sung (S)', null, null],
            [30, 4, 'HÌNH (S4.1)', '7H', '11H'],
            [31, 4, 'HÌNH (S4.1)', '7H', '11H'],
            [32, 4, 'Hình (S)', '5H59', '11H59'],
            [33, 4, 'Hình (S)', '5H59', '11H59'],
            [34, 4, 'Hình (S)', '5H59', '11H59'],
            [35, 4, 'Hình (S)', '5H59', '11H59'],
            [36, 4, 'Hình (S)', '5H59', '11H59'],
            [37, 4, 'Hình (S)', '5H59', '11H59'],
            [38, 4, 'Hình (S)', '5H59', '11H59'],
            [39, 4, 'ÔN LUYỆN STL', '5H59\'', '11H59\''],
            [40, 4, 'ÔN LUYỆN TĐ', '5H59\'', '11H59\''],
            [41, null, 'KIỂM TRA', null, null],
        ],
        'B01|chieu' => [
            // [Ngày số, Số giờ, Nội dung, Giờ BĐ, Giờ KT]
            [1, 6, 'Hình (C)', '12H', '18H'],
            [2, 4, 'Hình (C4)', '13H30', '17H30'],
            [3, 6, 'Hình (C)', '12H', '18H'],
            [4, 6, 'Hình (C)', '12H', '18H'],
            [5, 5, 'Hình (C5)', '13H', '18H'],
            [6, 5, 'Hình (C5)', '13H', '18H'],
            [7, 10, 'Cabin', '7h00', '17h00'],
            [8, 6, 'Phức tạp (6)', '6H30', '12H30'],
            [9, 6, 'Phức tạp (6)', '6H30', '12H30'],
            [10, 6, 'Phức tạp 4 - Có tải 2', '6H30', '12H30'],
            [11, 6, 'Có tải 6', '6H30', '12H30'],
            [12, 5, 'Có tải 5', '7H30\'', '12H30\''],
            [13, 8, 'Phức tạp(4) - Ban đêm', '13H-17H', '18H-22H'],
            [14, 8, 'Phức tạp(4) - Ban đêm', '13H-17H', '18H-22H'],
            [15, 8, 'Phức tạp(4) - Ban đêm', '13H-17H', '18H-22H'],
            [16, 8, 'Phức tạp(4) - Ban đêm', '13H-17H', '18H-22H'],
            [17, 8, 'Phức tạp(4) - Ban đêm', '13H-17H', '18H-22H'],
            [18, 5, 'DỐC, QC - Có tải (C5)', '13H', '18H'],
            [19, 5, 'DỐC, QC - Có tải (C5)', '13H', '18H'],
            [20, 5, 'DỐC, QC - Có tải (C5)', '13H', '18H'],
            [21, 5, 'DỐC, QC - Có tải (C5)', '13H', '18H'],
            [22, 5, 'DỐC, QC - Có tải (C5)', '13H', '18H'],
            [23, 5, 'Cao tốc (C)', '13H', '18H'],
            [24, 5, 'Cao tốc (C)', '13H', '18H'],
            [25, 4, 'Có tải (C4)', '13H30', '17H30'],
            [26, 4, 'Có tải (C4)', '13H30', '17H30'],
            [27, 4, 'Có tải (C4)', '13H30', '17H30'],
            [28, 4, 'Có tải (C4)', '13H30', '17H30'],
            [29, null, 'Bổ sung (C)', null, null],
            [30, 4, 'HÌNH (C4.1)', '13H', '17H'],
            [31, 4, 'HÌNH (C4.1)', '13H', '17H'],
            [32, 4, 'Hình (C)', '12H', '18H'],
            [33, 4, 'Hình (C)', '12H', '18H'],
            [34, 4, 'Hình (C)', '12H', '18H'],
            [35, 4, 'Hình (C)', '12H', '18H'],
            [36, 4, 'Hình (C)', '12H', '18H'],
            [37, 4, 'Hình (C)', '12H', '18H'],
            [38, 4, 'Hình (C)', '12H', '18H'],
            [39, 4, 'ÔN LUYỆN STL (C)', '12H', '18H'],
            [40, 4, 'ÔN LUYỆN TĐ (C)', '12H', '18H'],
            [41, null, 'KIỂM TRA', null, null],
        ],
    ];

    public function run(): void
    {
        $now = Carbon::now();

        DB::connection('sqlsrv_manhlinh')->transaction(function () use ($now): void {
            $baiHocId = [];
            foreach (self::DU_LIEU as $nhom => $dongs) {
                [$hang, $ca] = explode('|', $nhom);

                $payload = [];
                foreach ($dongs as $i => [$ngaySo, $soGio, $noiDung, $gioBd, $gioKt]) {
                    $ten = BaiHoc::chuanHoaTen($noiDung);
                    $key = $nhom.'|'.mb_strtolower($ten);
                    if (! isset($baiHocId[$key])) {
                        $baiHoc = BaiHoc::query()->where('Hang', $hang)->where('Ca', $ca)->where('Ten', $ten)->first()
                            ?? new BaiHoc([
                                'Hang' => $hang,
                                'Ca' => $ca,
                                'Ten' => $ten,
                                'ThuTu' => (int) BaiHoc::query()->where('Hang', $hang)->where('Ca', $ca)->max('ThuTu') + 1,
                                'NgayTao' => $now,
                            ]);
                        $baiHoc->fill(['GioBatDau' => $gioBd, 'GioKetThuc' => $gioKt, 'NgayCapNhat' => $now])->save();
                        $baiHocId[$key] = $baiHoc->Id;
                    }

                    $payload[] = [
                        'Hang' => $hang,
                        'Ca' => $ca,
                        'NgaySo' => $ngaySo,
                        'SoGio' => $soGio,
                        'BaiHocId' => $baiHocId[$key],
                        'ThuTu' => $i + 1,
                        'NgayTao' => $now,
                        'NgayCapNhat' => $now,
                    ];
                }

                BaiDay::query()->where('Hang', $hang)->where('Ca', $ca)->delete();
                foreach (array_chunk($payload, 100) as $chunk) {
                    BaiDay::query()->insert($chunk);
                }
                $soBaiHoc = count(array_unique(array_column($payload, 'BaiHocId')));
                $this->command?->info('Đã ghi '.count($payload)." ngày {$hang} ({$ca}), {$soBaiHoc} bài học.");
            }
        });
    }
}
