<?php

namespace App\Support\QldtOto;

use App\Support\Docx\DocMauBuilder;
use App\Support\Docx\DocxTextReplacer;
use Carbon\Carbon;
use PhpOffice\PhpWord\TemplateProcessor;

/**
 * Báo cáo / đề xuất hủy phiên DAT (mẫu «Báo cáo phiên học lỗi») — bảng danh sách phiên cloneRow.
 */
final class QuyetDinhHuyPhienKemDanhSachDocx
{
    public const DOC = 'templates/oto/quyet-dinh-huy-phien-tu-choi-phien.doc';

    public const DOCX = 'templates/oto/quyet-dinh-huy-phien-tu-choi-phien.docx';

    public const DE_XUAT_MAC_DINH = 'Tổ IT đề xuất chạy bù cho học viên trên để đảm bảo đúng với quy định và hủy phiên học lỗi trên.';

    private const DE_XUAT_TRONG_MAU = 'Tổ IT đề xuất chạy bù cho học viên trên để đảm bảo đúng với quy đinh và hủy phiên học lỗi trên.';

    public const PHUONG_AN_MAC_DINH = 'Loại phiên';

    public static function buildTemplate(): string
    {
        return DocMauBuilder::tuDoc(resource_path(self::DOC), resource_path(self::DOCX), [
            'Quảng trị, ngày 07 tháng 09 năm 2026' => 'Quảng trị, ngày ${NGAY_DONG}',
            'Đỗ Công Hoan' => '${ds_ho_ten}',
            '44007-20260621-152820' => '${ds_ma_hv}',
            'f44d6880-ba53-4339-8f76-a74267ea67d2' => '${ds_ma_phien}',
            'Lỗi khoảng thời gian giữa hai phiên học <15p' => '${ds_ly_do}',
            self::DE_XUAT_TRONG_MAU => '${DE_XUAT}',
        ], function (\DOMXPath $xpath): void {
            self::ganBienDongMau($xpath);
            DocMauBuilder::boBangRongCuoi($xpath);
            self::canGiuaDongNgay($xpath);
        });
    }

    private static function ganBienDongMau(\DOMXPath $xpath): void
    {
        foreach ($xpath->query('//w:tr') ?: [] as $tr) {
            if (! $tr instanceof \DOMElement || ! str_contains($tr->textContent, '${ds_ho_ten}')) {
                continue;
            }
            $cells = iterator_to_array($xpath->query('w:tc', $tr) ?: []);
            if (isset($cells[0])) {
                $p = $xpath->query('.//w:p', $cells[0])->item(0);
                if ($p instanceof \DOMElement) {
                    DocxTextReplacer::setParagraphText($xpath, $p, '${ds_stt}');
                }
            }
            if (isset($cells[4])) {
                $p = $xpath->query('.//w:p', $cells[4])->item(0);
                if ($p instanceof \DOMElement) {
                    DocxTextReplacer::setParagraphText($xpath, $p, '${ds_phuong_an}');
                }
            }
            break;
        }
    }

    private static function canGiuaDongNgay(\DOMXPath $xpath): void
    {
        $p = $xpath->query('//w:p[contains(., "Quảng trị, ngày")]')->item(0);
        if (! $p instanceof \DOMElement) {
            return;
        }
        foreach ($xpath->query('.//w:t', $p) ?: [] as $t) {
            if (trim($t->textContent) === '') {
                $t->textContent = '';
            } else {
                $t->textContent = ltrim($t->textContent);
                break;
            }
        }

        $pPr = $xpath->query('w:pPr', $p)->item(0);
        if (! $pPr instanceof \DOMElement) {
            $pPr = $p->ownerDocument->createElementNS(DocxTextReplacer::W_NS, 'w:pPr');
            $p->insertBefore($pPr, $p->firstChild);
        }
        foreach ($xpath->query('w:jc|w:ind', $pPr) ?: [] as $old) {
            $pPr->removeChild($old);
        }
        $jc = $p->ownerDocument->createElementNS(DocxTextReplacer::W_NS, 'w:jc');
        $jc->setAttributeNS(DocxTextReplacer::W_NS, 'w:val', 'center');
        $rPr = $xpath->query('w:rPr', $pPr)->item(0);
        $pPr->insertBefore($jc, $rPr);
    }

    /**
     * @param  array{ngay_bao_cao: string, de_xuat: string, phien: list<array{ho_ten: string, ma_hv: string, ma_phien: string, phuong_an: string, ly_do: string}>}  $data
     */
    public static function generate(array $data, string $outputPath): string
    {
        $template = resource_path(self::DOCX);
        if (! is_file($template)) {
            throw new \RuntimeException('Chưa có mẫu docx. Chạy: php artisan qldtoto:build-quyet-dinh-huy-phien-template');
        }

        $phien = array_values($data['phien']);
        if ($phien === []) {
            throw new \InvalidArgumentException('Chưa có dòng phiên nào trong danh sách.');
        }

        @mkdir(dirname($outputPath), 0755, true);

        $processor = new TemplateProcessor($template);
        $processor->setValue('NGAY_DONG', BaoCaoDangKyKhoaHoc::dongNgayThangNam(Carbon::parse($data['ngay_bao_cao'])));
        $processor->setValue('DE_XUAT', htmlspecialchars($data['de_xuat'], ENT_XML1 | ENT_QUOTES, 'UTF-8'));

        $processor->cloneRow('ds_stt', count($phien));
        foreach ($phien as $i => $row) {
            $n = $i + 1;
            $processor->setValue('ds_stt#'.$n, (string) $n);
            $processor->setValue('ds_ho_ten#'.$n, htmlspecialchars($row['ho_ten'], ENT_XML1 | ENT_QUOTES, 'UTF-8'));
            $processor->setValue('ds_ma_hv#'.$n, htmlspecialchars($row['ma_hv'], ENT_XML1 | ENT_QUOTES, 'UTF-8'));
            $processor->setValue('ds_ma_phien#'.$n, htmlspecialchars($row['ma_phien'], ENT_XML1 | ENT_QUOTES, 'UTF-8'));
            $processor->setValue('ds_phuong_an#'.$n, htmlspecialchars($row['phuong_an'], ENT_XML1 | ENT_QUOTES, 'UTF-8'));
            $processor->setValue('ds_ly_do#'.$n, htmlspecialchars($row['ly_do'], ENT_XML1 | ENT_QUOTES, 'UTF-8'));
        }

        $processor->saveAs($outputPath);

        if (! is_file($outputPath)) {
            throw new \RuntimeException('Không ghi được file báo cáo / danh sách hủy phiên.');
        }

        return $outputPath;
    }
}
