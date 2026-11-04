<?php

namespace App\Support\QldtOto;

use App\Support\Docx\DocMauBuilder;
use App\Support\Docx\DocxTextReplacer;
use Carbon\Carbon;
use PhpOffice\PhpWord\TemplateProcessor;

/**
 * Biên bản phân công giáo viên kiểm tra kết thúc các môn học lý thuyết, thực hành (tổ chấm kiểm tra).
 * Mẫu gốc: bien-ban-phan-cong-to-cham-kiem-tra.goc.docx. Khi build giữ phần đầu, tên biên bản, câu căn cứ,
 * câu «Các thành viên đã nhất trí…»; dựng lại phần nhiệm vụ (khối ${NV} lồng ${NGUOI}) và khối ký
 * thành bảng không viền (mẫu gốc canh bằng dấu cách, kèm trang trắng thừa).
 */
final class BienBanPhanCongToChamDocx
{
    public const DOC = 'templates/oto/bien-ban-phan-cong-to-cham-kiem-tra.goc.docx';

    public const DOCX = 'templates/oto/bien-ban-phan-cong-to-cham-kiem-tra.docx';

    public const XUNG = ['Ông', 'Bà'];

    public const PHO_TRUONG_PHONG_MAC_DINH = 'Hoàng Ngọc Hà';

    /** Nhiệm vụ mặc định theo mẫu của phòng (người dùng sửa / thêm / bớt trên form). */
    public const NHIEM_VU_MAC_DINH = [
        'Thực hiện coi kiểm tra, chấm bài kiểm tra các môn lý thuyết:',
        'Thực hiện coi kiểm tra mô phỏng:',
        'Thực hiện gọi tên kiểm tra thực hành (nội dung trong hình và đường trường):',
        'Thực hiện chấm thi chữ chi và hỗ trợ PĐT hoàn thiện hồ sơ học viên:',
        'Thực hiện bài thi liên hoàn, hỗ trợ học viên lên xe, hướng dẫn điều khiển xe đến vị trí xuất phát, mang xe không đạt về vị trí xuất phát:',
        'Thực hiện thi lái xe trên đường:',
    ];

    /** Vùng in A4 (11910 − lề trái 1160 − lề phải 1140). */
    private const RONG_IN = 9610;

    private const CAN_CU = 'Căn cứ vào kế hoạch đào tạo của lớp đào tạo lái xe ô tô Hạng ${HANG} Khóa ${KHOA_SO} và Quyết định số: ${SO_QD} ngày ${NGAY_QD} về việc tổ chức kiểm tra kết thúc môn học lý thuyết, thực hành lái xe ô tô hạng ${HANG} khóa ${KHOA_SO}. Phòng đào tạo phân công nhiệm vụ kiểm tra, chấm bài kiểm tra của lớp lái xe ô tô hạng ${HANG} Khóa ${KHOA_SO} ngày ${NGAY_KIEM_TRA} như sau:';

    public static function buildTemplate(): string
    {
        return DocMauBuilder::tuDoc(resource_path(self::DOC), resource_path(self::DOCX), [
            'Ngày 15 tháng 08 năm 2026' => 'Ngày ${NGAY_DONG}',
            'HẠNG B (SỐ SÀN) KHÓA 53' => 'HẠNG ${HANG_HOA} KHÓA ${KHOA_SO}',
        ], function (\DOMXPath $xpath): void {
            self::dungLaiThan($xpath);
        });
    }

    private static function dungLaiThan(\DOMXPath $xpath): void
    {
        $dom = $xpath->document;
        $body = $xpath->query('/w:document/w:body')->item(0);

        $bangDau = $xpath->query('w:tbl', $body)->item(0);
        $canCu = self::doanChua($xpath, $body, 'Căn cứ vào kế hoạch');
        $nhatTri = self::doanChua($xpath, $body, 'Các thành viên đã nhất trí');
        $tieuDe = [];
        for ($n = $bangDau->nextSibling; $n !== null && $n !== $canCu; $n = $n->nextSibling) {
            if ($n instanceof \DOMElement && $n->localName === 'p' && trim($n->textContent) !== '') {
                $tieuDe[] = $n;
            }
        }
        DocxTextReplacer::setParagraphText($xpath, $canCu, self::CAN_CU);

        $giu = array_merge([$bangDau], $tieuDe, [$canCu, $nhatTri]);
        foreach (iterator_to_array($body->childNodes) as $n) {
            if ($n instanceof \DOMElement && $n->localName !== 'sectPr' && ! in_array($n, $giu, true)) {
                $body->removeChild($n);
            }
        }
        foreach (iterator_to_array($xpath->query('.//w:pPr/w:sectPr', $body)) as $s) {
            $s->parentNode->removeChild($s);
        }

        $chen = function (string $xml, ?\DOMNode $truoc) use ($dom, $body): void {
            $tmp = new \DOMDocument;
            $tmp->loadXML('<w:wrap xmlns:w="'.DocxTextReplacer::W_NS.'">'.$xml.'</w:wrap>');
            foreach (iterator_to_array($tmp->documentElement->childNodes) as $node) {
                $body->insertBefore($dom->importNode($node, true), $truoc);
            }
        };

        $danhDau = fn (string $m) => '<w:p><w:r><w:t>'.$m.'</w:t></w:r></w:p>';
        $chen(
            $danhDau('${NV}')
            .self::doan(self::run('${NV_SO}. ${NV_TEN}', true), 'both', 60, true)
            .$danhDau('${NGUOI}')
            .self::doan(self::run('-', true).self::run(' ${NGUOI_XUNG}: ${NGUOI_TEN}${NGUOI_CUOI}'), 'both', 60)
            .$danhDau('${/NGUOI}')
            .$danhDau('${/NV}'),
            $nhatTri
        );

        $sectPr = $xpath->query('w:sectPr', $body)->item(0);
        $chen(self::doan('', 'left', 0).self::bangKy(), $sectPr);
        self::datTrang($xpath, $sectPr);
        DocMauBuilder::boDoanTrongCuoi($xpath);
    }

    /**
     * Bảng ký: dòng tiêu đề (giáo viên | P. trưởng phòng), dòng ${ky_1} ${ky_2} ${ky_3} để cloneRow —
     * ô thứ 3 dòng đầu là P. trưởng phòng, giáo viên điền lần lượt các ô còn lại.
     */
    private static function bangKy(): string
    {
        $cot = (int) floor(self::RONG_IN / 3);
        $o = fn (string $noiDung, int $span = 1, string $vAlign = 'top') => '<w:tc><w:tcPr><w:tcW w:w="'.($cot * $span).'" w:type="dxa"/>'
            .($span > 1 ? '<w:gridSpan w:val="'.$span.'"/>' : '').'<w:vAlign w:val="'.$vAlign.'"/></w:tcPr>'.$noiDung.'</w:tc>';
        $tieuDe = fn (string ...$dong) => implode('', array_map(fn ($d) => self::doan(self::run($d, true, 26), 'center', 0, true), $dong));

        // PhpWord cloneRow tìm đầu dòng bằng «<w:tr » (có thuộc tính) — thiếu thì lùi nhầm về bảng đầu trang.
        $dongTieuDe = '<w:tr w:rsidR="00B1B1B1"><w:trPr><w:cantSplit/></w:trPr>'
            .$o($tieuDe('CÁC GIÁO VIÊN COI KIỂM TRA,', 'CHẤM BÀI KIỂM TRA'), 2)
            .$o($tieuDe('P. TRƯỞNG PHÒNG', 'ĐÀO TẠO'))
            .'</w:tr>';
        $dongKy = '<w:tr w:rsidR="00B1B1B1"><w:trPr><w:cantSplit/><w:trHeight w:val="1700" w:hRule="atLeast"/></w:trPr>'
            .implode('', array_map(
                fn ($i) => $o(self::doan(self::run('${ky_'.$i.'}', true, 26), 'center', 0), 1, 'bottom'),
                [1, 2, 3]
            ))
            .'</w:tr>';

        return '<w:tbl><w:tblPr><w:tblW w:w="'.($cot * 3).'" w:type="dxa"/><w:jc w:val="center"/>'
            .'<w:tblBorders><w:top w:val="nil"/><w:left w:val="nil"/><w:bottom w:val="nil"/><w:right w:val="nil"/>'
            .'<w:insideH w:val="nil"/><w:insideV w:val="nil"/></w:tblBorders><w:tblLayout w:type="fixed"/>'
            .'<w:tblCellMar><w:left w:w="57" w:type="dxa"/><w:right w:w="57" w:type="dxa"/></w:tblCellMar></w:tblPr>'
            .'<w:tblGrid><w:gridCol w:w="'.$cot.'"/><w:gridCol w:w="'.$cot.'"/><w:gridCol w:w="'.$cot.'"/></w:tblGrid>'
            .$dongTieuDe.$dongKy.'</w:tbl>';
    }

    /** Một khổ A4 dọc cho cả biên bản (mẫu gốc có 4 section với lề khác nhau). */
    private static function datTrang(\DOMXPath $xpath, \DOMElement $sectPr): void
    {
        while ($sectPr->firstChild !== null) {
            $sectPr->removeChild($sectPr->firstChild);
        }
        $tmp = new \DOMDocument;
        $tmp->loadXML('<w:wrap xmlns:w="'.DocxTextReplacer::W_NS.'">'
            .'<w:pgSz w:w="11910" w:h="16840"/>'
            .'<w:pgMar w:top="993" w:right="1140" w:bottom="851" w:left="1160" w:header="720" w:footer="720" w:gutter="0"/>'
            .'<w:cols w:space="720"/></w:wrap>');
        foreach (iterator_to_array($tmp->documentElement->childNodes) as $node) {
            $sectPr->appendChild($xpath->document->importNode($node, true));
        }
    }

    private static function doanChua(\DOMXPath $xpath, \DOMNode $body, string $chu): \DOMElement
    {
        foreach ($xpath->query('w:p', $body) as $p) {
            if (str_contains($p->textContent, $chu)) {
                return $p;
            }
        }
        throw new \RuntimeException('Không thấy đoạn «'.$chu.'» trong mẫu.');
    }

    private static function run(string $text, bool $dam = false, int $co = 28): string
    {
        return '<w:r><w:rPr>'.($dam ? '<w:b/>' : '').'<w:sz w:val="'.$co.'"/><w:szCs w:val="'.$co.'"/><w:lang w:val="en-US"/></w:rPr>'
            .'<w:t xml:space="preserve">'.htmlspecialchars($text, ENT_XML1).'</w:t></w:r>';
    }

    private static function doan(string $runs, string $canh, int $sau, bool $giuVoiSau = false): string
    {
        $thut = $canh === 'both' ? '<w:ind w:left="142" w:firstLine="425"/>' : '';

        return '<w:p><w:pPr>'.($giuVoiSau ? '<w:keepNext/>' : '').'<w:spacing w:before="0" w:after="'.$sau.'"/>'.$thut
            .'<w:jc w:val="'.$canh.'"/></w:pPr>'.$runs.'</w:p>';
    }

    /**
     * @param  array{ngay_lap: string, hang: string, khoa_so: string, so_qd: string, ngay_qd: string, ngay_kiem_tra: string,
     *     pho_truong_phong: string, nhiem_vu: list<array{ten: string, nguoi: list<array{xung: string, ten: string}>}>}  $data
     */
    public static function generate(array $data, string $outputPath): string
    {
        $template = resource_path(self::DOCX);
        if (! is_file($template)) {
            throw new \RuntimeException('Chưa có mẫu docx. Chạy: php artisan qldtoto:build-bien-ban-phan-cong-to-cham-template');
        }
        $nhiemVu = array_values($data['nhiem_vu']);
        if ($nhiemVu === []) {
            throw new \InvalidArgumentException('Chưa có nhiệm vụ nào.');
        }

        @mkdir(dirname($outputPath), 0755, true);
        $processor = new TemplateProcessor($template);

        $processor->cloneBlock('NV', count($nhiemVu), true, true);
        $giaoVien = [];
        foreach ($nhiemVu as $i => $nv) {
            $ni = $i + 1;
            $processor->setValue('NV_SO#'.$ni, (string) $ni);
            $processor->setValue('NV_TEN#'.$ni, self::esc(self::coHaiCham($nv['ten'])));

            $nguoi = array_values($nv['nguoi']);
            $processor->cloneBlock('NGUOI#'.$ni, count($nguoi), true, true);
            foreach ($nguoi as $j => $ng) {
                $k = $ni.'#'.($j + 1);
                $processor->setValue('NGUOI_XUNG#'.$k, self::esc($ng['xung']));
                $processor->setValue('NGUOI_TEN#'.$k, self::esc($ng['ten']));
                $processor->setValue('NGUOI_CUOI#'.$k, $j === count($nguoi) - 1 ? '.' : ';');
                $giaoVien[mb_strtolower($ng['ten'])] ??= $ng['ten'];
            }
        }

        $o = array_values($giaoVien);
        array_splice($o, 2, 0, [$data['pho_truong_phong']]);
        $soDong = (int) ceil(count($o) / 3);
        $processor->cloneRow('ky_1', $soDong);
        for ($d = 1; $d <= $soDong; $d++) {
            for ($c = 1; $c <= 3; $c++) {
                $processor->setValue('ky_'.$c.'#'.$d, self::esc($o[($d - 1) * 3 + $c - 1] ?? ''));
            }
        }

        $hang = $data['hang'];
        $values = [
            'NGAY_DONG' => BaoCaoDangKyKhoaHoc::dongNgayThangNam(Carbon::parse($data['ngay_lap'])),
            'HANG' => $hang,
            'HANG_HOA' => mb_strtoupper($hang),
            'KHOA_SO' => $data['khoa_so'],
            'SO_QD' => $data['so_qd'].QuyetDinhDieuDongXeSupport::HAU_TO_SO_QD,
            'NGAY_QD' => Carbon::parse($data['ngay_qd'])->format('d/m/Y'),
            'NGAY_KIEM_TRA' => Carbon::parse($data['ngay_kiem_tra'])->format('d/m/Y'),
        ];
        foreach ($values as $key => $value) {
            $processor->setValue($key, self::esc($value));
        }
        $processor->saveAs($outputPath);

        if (! is_file($outputPath)) {
            throw new \RuntimeException('Không ghi được file biên bản phân công.');
        }

        return $outputPath;
    }

    private static function coHaiCham(string $ten): string
    {
        $ten = rtrim(trim($ten), ' .;:');

        return $ten.':';
    }

    private static function esc(string $v): string
    {
        return htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
