<?php

namespace App\Support\QldtOto;

use App\Support\Docx\DocMauBuilder;
use PhpOffice\PhpWord\TemplateProcessor;

/**
 * Hợp đồng đào tạo in sẵn điều khoản — thông tin Bên B (học viên) giữ dấu chấm để học viên tự điền khi ký.
 */
final class HopDongDaoTaoLaiXeDocx
{
    public const LOAI_B1 = 'b1_tu_dong';

    public const LOAI_B2 = 'b2_so_san';

    public const LOAI_C1 = 'c1';

    /** @var array<string, array{label: string, goc: string, docx: string, mac_dinh: array<string, mixed>}> */
    public const LOAI = [
        self::LOAI_B1 => [
            'label' => 'Hạng B — số tự động (16 triệu)',
            'goc' => 'templates/oto/hop-dong-dao-tao-b1-tu-dong-tt17.goc.docx',
            'docx' => 'templates/oto/hop-dong-dao-tao-b1-tu-dong-tt17.docx',
            'mac_dinh' => [
                'mo_ta_hang' => 'Hạng B – Học xe chuyển số tự động Khóa ….',
                'gio_tong' => '196',
                'gio_lt' => '136',
                'gio_th' => '60',
                'hoc_phi' => '16000000',
                'hoc_phi_chu' => 'Mười sáu triệu đồng chẵn',
                'lan_1' => '2000000',
                'lan_1_chu' => 'Hai triệu đồng chẵn',
                'lan_2' => '7000000',
                'lan_2_chu' => 'Bảy triệu đồng chẵn',
                'lan_3' => '7000000',
                'lan_3_chu' => 'Bảy triệu đồng chẵn',
            ],
        ],
        self::LOAI_B2 => [
            'label' => 'Hạng B — số sàn (16 triệu)',
            'goc' => 'templates/oto/hop-dong-dao-tao-b2-so-san-tt17.goc.docx',
            'docx' => 'templates/oto/hop-dong-dao-tao-b2-so-san-tt17.docx',
            'mac_dinh' => [
                'mo_ta_hang' => 'Hạng B – Học xe chuyển số cơ khí Khóa ….',
                'gio_tong' => '228',
                'gio_lt' => '152',
                'gio_th' => '76',
                'hoc_phi' => '16000000',
                'hoc_phi_chu' => 'Mười sáu triệu đồng chẵn',
                'lan_1' => '2000000',
                'lan_1_chu' => 'Hai triệu đồng chẵn',
                'lan_2' => '7000000',
                'lan_2_chu' => 'Bảy triệu đồng chẵn',
                'lan_3' => '7000000',
                'lan_3_chu' => 'Bảy triệu đồng chẵn',
            ],
        ],
        self::LOAI_C1 => [
            'label' => 'Hạng C1 (17,9 triệu)',
            'goc' => 'templates/oto/hop-dong-dao-tao-c1-tt17.goc.docx',
            'docx' => 'templates/oto/hop-dong-dao-tao-c1-tt17.docx',
            'mac_dinh' => [
                'mo_ta_hang' => 'Hạng C1 – Khóa ….',
                'gio_tong' => '237',
                'gio_lt' => '152',
                'gio_th' => '85',
                'hoc_phi' => '17900000',
                'hoc_phi_chu' => 'Mười bảy triệu chín trăm đồng chẵn',
                'lan_1' => '2000000',
                'lan_1_chu' => 'Hai triệu đồng chẵn',
                'lan_2' => '7000000',
                'lan_2_chu' => 'Bảy triệu đồng chẵn',
                'lan_3' => '8900000',
                'lan_3_chu' => 'Tám triệu chín trăm nghìn đồng chẵn',
            ],
        ],
    ];

    public static function buildTemplate(string $loai): string
    {
        $cfg = self::LOAI[$loai] ?? null;
        if ($cfg === null) {
            throw new \InvalidArgumentException('Loại hợp đồng không hợp lệ.');
        }

        $map = match ($loai) {
            self::LOAI_B1 => self::mapB1(),
            self::LOAI_B2 => self::mapB2(),
            self::LOAI_C1 => self::mapC1(),
            default => throw new \InvalidArgumentException('Loại hợp đồng không hợp lệ.'),
        };

        return DocMauBuilder::tuDoc(resource_path($cfg['goc']), resource_path($cfg['docx']), $map, function (\DOMXPath $xpath): void {
            DocMauBuilder::boDoanTrongCuoi($xpath);
        });
    }

    /** @return array<string, string> */
    private static function mapB1(): array
    {
        return array_merge(self::mapGio19615260(), self::mapHocPhi16(), [
            'Học xe chuyển số tự động Khóa ….' => '${MO_TA_HANG}',
        ]);
    }

    /** @return array<string, string> */
    private static function mapB2(): array
    {
        return array_merge(self::mapGio22815276(), self::mapHocPhi16(), [
            'Học xe chuyển số cơ khí Khóa ….' => '${MO_TA_HANG}',
        ]);
    }

    /** @return array<string, string> */
    private static function mapC1(): array
    {
        return [
            '237 giờ/ học viên/ khóa học' => '${GIO_TONG} giờ/ học viên/ khóa học',
            '152 giờ học/ học viên/ khóa học' => '${GIO_LT} giờ học/ học viên/ khóa học',
            '85 giờ học/ học viên/ khóa học' => '${GIO_TH} giờ học/ học viên/ khóa học',
            'Hạng C1 – Khóa ….' => '${MO_TA_HANG}',
            'Mức học phí: 17.900.000 đồng/người/khóa. (Bằng chữ: Mười bảy triệu chín trăm đồng chẵn).' => 'Mức học phí: ${HOC_PHI} đồng/người/khóa. (Bằng chữ: ${HOC_PHI_CHU}).',
            '+ Lần 1: 2.000.000 đồng (Bằng chữ: Hai triệu đồng chẵn) vào ngày làm thủ tục đăng ký học.' => '+ Lần 1: ${LAN_1} đồng (Bằng chữ: ${LAN_1_CHU}) vào ngày làm thủ tục đăng ký học.',
            '+ Lần 2: 7.000.000 đồng (Bằng chữ: Bảy triệu đồng chẵn) chuẩn bị vào học lý thuyết.' => '+ Lần 2: ${LAN_2} đồng (Bằng chữ: ${LAN_2_CHU}) chuẩn bị vào học lý thuyết.',
            '+ Lần 3: 8.900.000 đồng (Bằng chữ: Tám triệu chín trăm nghìn đồng chẵn) ngay sau khi học xong lý thuyết.' => '+ Lần 3: ${LAN_3} đồng (Bằng chữ: ${LAN_3_CHU}) ngay sau khi học xong lý thuyết.',
            'Ghi chú: Học viên có thể nộp học phí 1 lần với số tiền là 17.900.000 đồng (Bằng chữ: Mười bảy triệu tám trăm nghìn đồng chẵn) vào ngày làm thủ tục đăng ký học.' => 'Ghi chú: Học viên có thể nộp học phí 1 lần với số tiền là ${HOC_PHI} đồng (Bằng chữ: ${HOC_PHI_MOT_LAN_CHU}) vào ngày làm thủ tục đăng ký học.',
        ];
    }

    /** @return array<string, string> */
    private static function mapGio19615260(): array
    {
        return [
            '196 giờ/ học viên/ khóa học' => '${GIO_TONG} giờ/ học viên/ khóa học',
            '136 giờ học/ học viên/ khóa học' => '${GIO_LT} giờ học/ học viên/ khóa học',
            '60 giờ học/ học viên/ khóa học' => '${GIO_TH} giờ học/ học viên/ khóa học',
        ];
    }

    /** @return array<string, string> */
    private static function mapGio22815276(): array
    {
        return [
            '228 giờ/ học viên/ khóa học' => '${GIO_TONG} giờ/ học viên/ khóa học',
            '152 giờ học/ học viên/ khóa học' => '${GIO_LT} giờ học/ học viên/ khóa học',
            '76 giờ học/ học viên/ khóa học' => '${GIO_TH} giờ học/ học viên/ khóa học',
        ];
    }

    /** @return array<string, string> */
    private static function mapHocPhi16(): array
    {
        return [
            'Mức học phí: 16.000.000 đồng/người/khóa. (Bằng chữ: Mười sáu triệu đồng chẵn).' => 'Mức học phí: ${HOC_PHI} đồng/người/khóa. (Bằng chữ: ${HOC_PHI_CHU}).',
            '+ Lần 1: 2.000.000 đồng (Bằng chữ: Hai triệu đồng chẵn) vào ngày làm thủ tục đăng ký học.' => '+ Lần 1: ${LAN_1} đồng (Bằng chữ: ${LAN_1_CHU}) vào ngày làm thủ tục đăng ký học.',
            '+ Lần 2: 7.000.000 đồng (Bằng chữ: Bảy triệu đồng chẵn) chuẩn bị vào học lý thuyết.' => '+ Lần 2: ${LAN_2} đồng (Bằng chữ: ${LAN_2_CHU}) chuẩn bị vào học lý thuyết.',
            '+ Lần 3: 7.000.000 đồng (Bằng chữ: Bảy triệu đồng chẵn) ngay sau khi học xong lý thuyết.' => '+ Lần 3: ${LAN_3} đồng (Bằng chữ: ${LAN_3_CHU}) ngay sau khi học xong lý thuyết.',
            'Ghi chú: Học viên có thể nộp học phí 1 lần với số tiền là 16.000.000 đồng (Bằng chữ: Mười sáu triệu đồng chẵn) vào ngày làm thủ tục đăng ký học.' => 'Ghi chú: Học viên có thể nộp học phí 1 lần với số tiền là ${HOC_PHI} đồng (Bằng chữ: ${HOC_PHI_MOT_LAN_CHU}) vào ngày làm thủ tục đăng ký học.',
        ];
    }

    public static function dinhDangTien(int $so): string
    {
        return number_format(max(0, $so), 0, '', '.');
    }

    /**
     * @param  array{
     *     loai: string,
     *     mo_ta_hang: string,
     *     gio_tong: string,
     *     gio_lt: string,
     *     gio_th: string,
     *     hoc_phi: int,
     *     hoc_phi_chu: string,
     *     lan_1: int,
     *     lan_1_chu: string,
     *     lan_2: int,
     *     lan_2_chu: string,
     *     lan_3: int,
     *     lan_3_chu: string,
     *     hoc_phi_mot_lan_chu: string,
     * }  $data
     */
    public static function generate(array $data, string $outputPath): string
    {
        $cfg = self::LOAI[$data['loai']] ?? null;
        if ($cfg === null) {
            throw new \InvalidArgumentException('Chọn loại hợp đồng.');
        }
        $template = resource_path($cfg['docx']);
        if (! is_file($template)) {
            throw new \RuntimeException('Chưa có mẫu. Chạy: php artisan qldtoto:build-hop-dong-dao-tao-template');
        }

        @mkdir(dirname($outputPath), 0755, true);

        $values = [
            'MO_TA_HANG' => $data['mo_ta_hang'],
            'GIO_TONG' => $data['gio_tong'],
            'GIO_LT' => $data['gio_lt'],
            'GIO_TH' => $data['gio_th'],
            'HOC_PHI' => self::dinhDangTien($data['hoc_phi']),
            'HOC_PHI_CHU' => $data['hoc_phi_chu'],
            'LAN_1' => self::dinhDangTien($data['lan_1']),
            'LAN_1_CHU' => $data['lan_1_chu'],
            'LAN_2' => self::dinhDangTien($data['lan_2']),
            'LAN_2_CHU' => $data['lan_2_chu'],
            'LAN_3' => self::dinhDangTien($data['lan_3']),
            'LAN_3_CHU' => $data['lan_3_chu'],
            'HOC_PHI_MOT_LAN_CHU' => $data['hoc_phi_mot_lan_chu'],
        ];

        $processor = new TemplateProcessor($template);
        foreach ($values as $key => $value) {
            $processor->setValue($key, self::esc($value));
        }
        $processor->saveAs($outputPath);

        if (! is_file($outputPath)) {
            throw new \RuntimeException('Không ghi được hợp đồng.');
        }

        return $outputPath;
    }

    private static function esc(string $v): string
    {
        return htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
