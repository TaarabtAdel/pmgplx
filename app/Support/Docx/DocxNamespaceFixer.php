<?php

namespace App\Support\Docx;

/**
 * Chỉ sửa word/document.xml — tránh đụng footer/settings (vẫn cần xmlns:mc cho mc:AlternateContent).
 * - Sửa xmlns:ns* → mc/w14/r
 * - Bỏ mc:Ignorable / w14:* không cần
 * - Đảm bảo xmlns:v / xmlns:o khi PhpWord setImageValue chèn VML (thiếu → Word báo undeclared prefix)
 */
final class DocxNamespaceFixer
{
    private const PART = 'word/document.xml';

    private const NS = [
        'r' => 'http://schemas.openxmlformats.org/officeDocument/2006/relationships',
        'v' => 'urn:schemas-microsoft-com:vml',
        'o' => 'urn:schemas-microsoft-com:office:office',
        'mc' => 'http://schemas.openxmlformats.org/markup-compatibility/2006',
        'w14' => 'http://schemas.microsoft.com/office/word/2010/wordml',
    ];

    public static function repairFile(string $docxPath): bool
    {
        if (! is_file($docxPath)) {
            return false;
        }

        $zip = new \ZipArchive();
        if ($zip->open($docxPath) !== true) {
            return false;
        }

        $raw = $zip->getFromName(self::PART);
        if ($raw === false) {
            $zip->close();

            return false;
        }

        $fixed = self::repairDocumentXml($raw);
        if ($fixed === $raw) {
            $zip->close();

            return false;
        }

        $zip->deleteName(self::PART);
        $zip->addFromString(self::PART, $fixed);
        $zip->close();

        return true;
    }

    public static function repairDocumentXml(string $xml): string
    {
        $xml = str_replace(
            'xmlns:ns1="'.self::NS['mc'].'"',
            'xmlns:mc="'.self::NS['mc'].'"',
            $xml
        );
        $xml = str_replace(
            'xmlns:ns2="'.self::NS['w14'].'"',
            'xmlns:w14="'.self::NS['w14'].'"',
            $xml
        );
        $xml = str_replace(
            'xmlns:ns3="'.self::NS['r'].'"',
            'xmlns:r="'.self::NS['r'].'"',
            $xml
        );

        $xml = preg_replace('/\bns3:/', 'r:', $xml) ?? $xml;
        $xml = preg_replace('/\bns2:/', 'w14:', $xml) ?? $xml;
        $xml = preg_replace('/\bns1:/', 'mc:', $xml) ?? $xml;

        // Bỏ markup tùy chọn trên document — không cần cho nội dung báo cáo.
        $xml = preg_replace('/\s+mc:Ignorable="[^"]*"/', '', $xml) ?? $xml;
        $xml = preg_replace('/\s+w14:[A-Za-z0-9]+="[^"]*"/', '', $xml) ?? $xml;

        if (! preg_match('/\bmc:/', $xml)) {
            $xml = preg_replace('/\s+xmlns:mc="[^"]*"/', '', $xml) ?? $xml;
        }
        if (! preg_match('/\bw14:/', $xml)) {
            $xml = preg_replace('/\s+xmlns:w14="[^"]*"/', '', $xml) ?? $xml;
        }

        // PhpWord setImageValue dùng VML: <v:shape> <v:imagedata o:title r:id=...>
        foreach (['v', 'o', 'r'] as $prefix) {
            if (preg_match('/\b'.preg_quote($prefix, '/').':/', $xml)) {
                $xml = self::ensureXmlns($xml, $prefix, self::NS[$prefix]);
            }
        }

        return $xml;
    }

    private static function ensureXmlns(string $xml, string $prefix, string $uri): string
    {
        if (preg_match('/\sxmlns:'.preg_quote($prefix, '/').'="/', $xml)) {
            return $xml;
        }

        $attr = ' xmlns:'.$prefix.'="'.$uri.'"';

        return (string) preg_replace(
            '/(<w:document\b[^>]*)(>)/',
            '$1'.$attr.'$2',
            $xml,
            1
        );
    }

    /** @deprecated dùng repairDocumentXml */
    public static function repairXml(string $xml): string
    {
        return self::repairDocumentXml($xml);
    }
}
