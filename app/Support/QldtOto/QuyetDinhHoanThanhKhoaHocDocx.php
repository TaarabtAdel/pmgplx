<?php

namespace App\Support\QldtOto;

use App\Support\Docx\DocMauBuilder;
use App\Support\Docx\DocxTextReplacer;
use Carbon\Carbon;
use PhpOffice\PhpWord\TemplateProcessor;

/**
 * Quyết định hoàn thành khoá học + danh sách học viên được cấp giấy xác nhận hoàn thành.
 * Trang 1: mẫu quyet-dinh-hoan-thanh-khoa-hoc.doc (gắn biến). Trang 2: bảng danh sách kèm theo dựng thêm
 * khi build (mẫu gốc không có), một dòng ${hv_stt}… để cloneRow.
 */
final class QuyetDinhHoanThanhKhoaHocDocx
{
    public const DOC = 'templates/oto/quyet-dinh-hoan-thanh-khoa-hoc.doc';

    public const DOCX = 'templates/oto/quyet-dinh-hoan-thanh-khoa-hoc.docx';

    /** Cột danh sách: biến => [tiêu đề, độ rộng twip, canh]. Tổng = vùng in A4 dọc của mẫu (9213). */
    private const COT = [
        'hv_stt' => ['STT', 650, 'center'],
        'hv_ho_ten' => ['Họ và tên', 2400, 'left'],
        'hv_ngay_sinh' => ['Ngày sinh', 1250, 'center'],
        'hv_cccd' => ['Số CCCD', 1650, 'center'],
        'hv_noi_cu_tru' => ['Nơi cư trú', 2350, 'left'],
        'hv_khoa' => ['Khóa', 913, 'center'],
    ];

    public static function buildTemplate(): string
    {
        return DocMauBuilder::tuDoc(resource_path(self::DOC), resource_path(self::DOCX), [
            '743/QĐ-TTML' => '${SO_QD}',
            'Ngày 18 tháng 10 năm 2026' => 'Ngày ${NGAY_DONG}',
            'cho 56 học viên Hạng B (Số sàn) khóa 55 và 01 học viên Hạng B (Số sàn) khóa 54' => 'cho ${DS_NHOM}',
            'hoàn thành lớp học đào tạo lái xe hạng B (Số sàn) khóa 54' => 'hoàn thành lớp học đào tạo lái xe hạng ${HANG_CHINH} khóa ${KHOA_CHINH}',
            'Nguyễn Thị Vinh' => 'Trương Hoàng Linh',
        ], function (\DOMXPath $xpath): void {
            self::doiNguoiKy($xpath);
            DocMauBuilder::boDoanTrongCuoi($xpath);
            self::themTrangDanhSach($xpath);
        });
    }

    /** Khối ký như các mẫu khác: «KT.GIÁM ĐỐC TRUNG TÂM / PHÓ GIÁM ĐỐC», giữ nguyên chiều cao khối. */
    private static function doiNguoiKy(\DOMXPath $xpath): void
    {
        $p = null;
        foreach ($xpath->query('//w:tbl//w:tc/w:p') as $node) {
            if (trim($node->textContent) === 'GIÁM ĐỐC TRUNG TÂM') {
                $p = $node;
                break;
            }
        }
        if (! $p instanceof \DOMElement) {
            throw new \RuntimeException('Không thấy dòng «GIÁM ĐỐC TRUNG TÂM» ở khối ký.');
        }

        $pho = $p->cloneNode(true);
        DocxTextReplacer::setParagraphText($xpath, $p, 'KT.GIÁM ĐỐC TRUNG TÂM');
        DocxTextReplacer::setParagraphText($xpath, $pho, 'PHÓ GIÁM ĐỐC');
        $p->parentNode->insertBefore($pho, $p->nextSibling);

        $sau = $pho->nextSibling;
        if ($sau instanceof \DOMElement && $sau->localName === 'p' && trim($sau->textContent) === '') {
            $sau->parentNode->removeChild($sau);
        }
    }

    /** Thêm trang «Danh sách kèm theo» sau quyết định (đoạn tiêu đề ngắt trang trước, bảng 1 dòng mẫu). */
    private static function themTrangDanhSach(\DOMXPath $xpath): void
    {
        $dom = $xpath->document;
        $body = $xpath->query('/w:document/w:body')->item(0);
        $sectPr = $xpath->query('w:sectPr', $body)->item(0);

        $run = fn (string $text, bool $dam = false, bool $nghieng = false, int $co = 26) => '<w:r><w:rPr>'
            .'<w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman"/>'
            .($dam ? '<w:b/>' : '').($nghieng ? '<w:i/>' : '')
            .'<w:sz w:val="'.$co.'"/><w:szCs w:val="'.$co.'"/></w:rPr><w:t xml:space="preserve">'
            .htmlspecialchars($text, ENT_XML1).'</w:t></w:r>';
        $doan = fn (string $runs, string $canh = 'center', bool $ngatTrang = false, int $sau = 0) => '<w:p><w:pPr>'
            .($ngatTrang ? '<w:pageBreakBefore/>' : '')
            .'<w:spacing w:before="0" w:after="'.$sau.'" w:line="276" w:lineRule="auto"/><w:jc w:val="'.$canh.'"/></w:pPr>'.$runs.'</w:p>';

        $o = function (string $noiDung, int $rong, string $canh, bool $dam) use ($run, $doan): string {
            return '<w:tc><w:tcPr><w:tcW w:w="'.$rong.'" w:type="dxa"/><w:vAlign w:val="center"/></w:tcPr>'
                .$doan($run($noiDung, $dam, false, 24), $canh).'</w:tc>';
        };

        $vien = '';
        foreach (['top', 'left', 'bottom', 'right', 'insideH', 'insideV'] as $b) {
            $vien .= '<w:'.$b.' w:val="single" w:sz="4" w:space="0" w:color="000000"/>';
        }
        $luoi = implode('', array_map(fn ($c) => '<w:gridCol w:w="'.$c[1].'"/>', self::COT));
        $dauBang = '<w:tr><w:trPr><w:tblHeader/><w:cantSplit/></w:trPr>'
            .implode('', array_map(fn ($c) => $o($c[0], $c[1], 'center', true), self::COT)).'</w:tr>';
        $dongMau = '<w:tr><w:trPr><w:cantSplit/></w:trPr>'
            .implode('', array_map(fn ($bien, $c) => $o('${'.$bien.'}', $c[1], $c[2], false), array_keys(self::COT), self::COT)).'</w:tr>';
        $bang = '<w:tbl><w:tblPr><w:tblW w:w="'.array_sum(array_column(self::COT, 1)).'" w:type="dxa"/><w:jc w:val="center"/>'
            .'<w:tblBorders>'.$vien.'</w:tblBorders><w:tblLayout w:type="fixed"/>'
            .'<w:tblCellMar><w:left w:w="70" w:type="dxa"/><w:right w:w="70" w:type="dxa"/></w:tblCellMar></w:tblPr>'
            .'<w:tblGrid>'.$luoi.'</w:tblGrid>'.$dauBang.$dongMau.'</w:tbl>';

        $xml = $doan($run('DANH SÁCH HỌC VIÊN ĐƯỢC CẤP GIẤY XÁC NHẬN HOÀN THÀNH KHÓA ĐÀO TẠO LÁI XE', true, false, 28), 'center', true)
            .$doan($run('(Kèm theo Quyết định số ${SO_QD} ngày ${NGAY_DMY} của Giám đốc Trung tâm GDNN Mạnh Linh)', false, true, 26), 'center', false, 200)
            .$bang
            .$doan($run('Tổng cộng: ${TONG_HV} học viên.', false, true, 26), 'left', false, 0);

        $frag = $dom->createDocumentFragment();
        $wrap = '<w:wrap xmlns:w="'.DocxTextReplacer::W_NS.'">'.$xml.'</w:wrap>';
        $tmp = new \DOMDocument();
        $tmp->loadXML($wrap);
        foreach (iterator_to_array($tmp->documentElement->childNodes) as $node) {
            $frag->appendChild($dom->importNode($node, true));
        }
        $body->insertBefore($frag, $sectPr);
    }

    /**
     * @param  array{so_qd: string, ngay_quyet_dinh: string, khoa: list<array{hang: string, khoa_so: string, ten_ngan: string, hoc_vien: list<array{ho_ten: string, ngay_sinh: string, so_cmnd: string, noi_cu_tru: string}>}>}  $data
     *     khoa[0] là khoá chính («hoàn thành lớp học … khóa …»).
     */
    public static function generate(array $data, string $outputPath): string
    {
        $template = resource_path(self::DOCX);
        if (! is_file($template)) {
            throw new \RuntimeException('Chưa có mẫu docx. Chạy: php artisan qldtoto:build-quyet-dinh-hoan-thanh-khoa-hoc-template');
        }
        $khoa = array_values(array_filter($data['khoa'], fn ($k) => $k['hoc_vien'] !== []));
        if ($khoa === []) {
            throw new \InvalidArgumentException('Chưa chọn học viên nào.');
        }

        @mkdir(dirname($outputPath), 0755, true);

        $ngay = Carbon::parse($data['ngay_quyet_dinh']);
        $nhom = array_map(
            fn ($k) => sprintf('%02d', count($k['hoc_vien'])).' học viên Hạng '.$k['hang'].' khóa '.$k['khoa_so'],
            $khoa
        );
        $dsNhom = count($nhom) > 1 ? implode(', ', array_slice($nhom, 0, -1)).' và '.end($nhom) : $nhom[0];

        $dong = [];
        foreach ($khoa as $k) {
            foreach ($k['hoc_vien'] as $hv) {
                $dong[] = [
                    'hv_ho_ten' => $hv['ho_ten'],
                    'hv_ngay_sinh' => $hv['ngay_sinh'],
                    'hv_cccd' => $hv['so_cmnd'],
                    'hv_noi_cu_tru' => $hv['noi_cu_tru'],
                    'hv_khoa' => $k['ten_ngan'],
                ];
            }
        }

        $processor = new TemplateProcessor($template);
        $processor->cloneRow('hv_stt', count($dong));
        foreach ($dong as $i => $d) {
            $n = $i + 1;
            $processor->setValue('hv_stt#'.$n, (string) $n);
            foreach ($d as $bien => $v) {
                $processor->setValue($bien.'#'.$n, self::esc((string) $v));
            }
        }

        $values = [
            'SO_QD' => $data['so_qd'].QuyetDinhDieuDongXeSupport::HAU_TO_SO_QD,
            'NGAY_DONG' => BaoCaoDangKyKhoaHoc::dongNgayThangNam($ngay),
            'NGAY_DMY' => $ngay->format('d/m/Y'),
            'DS_NHOM' => $dsNhom,
            'HANG_CHINH' => $khoa[0]['hang'],
            'KHOA_CHINH' => $khoa[0]['khoa_so'],
            'TONG_HV' => sprintf('%02d', count($dong)),
        ];
        foreach ($values as $key => $value) {
            $processor->setValue($key, self::esc($value));
        }
        $processor->saveAs($outputPath);

        if (! is_file($outputPath)) {
            throw new \RuntimeException('Không ghi được file quyết định hoàn thành.');
        }

        return $outputPath;
    }

    private static function esc(string $v): string
    {
        return htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
