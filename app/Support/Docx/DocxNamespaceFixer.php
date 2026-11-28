<?php

namespace App\Support\Docx;

/**
 * Chỉ sửa word/document.xml — tránh đụng footer/settings (vẫn cần xmlns:mc cho mc:AlternateContent).
 * Một số tool đổi xmlns:mc/w14/r thành xmlns:ns* nhưng vẫn để mc:/w14:/ns3: → Word Windows báo undeclared prefix.
 */
final class DocxNamespaceFixer
{
    private const PART = 'word/document.xml';

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
            'xmlns:ns1="http://schemas.openxmlformats.org/markup-compatibility/2006"',
            'xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006"',
            $xml
        );
        $xml = str_replace(
            'xmlns:ns2="http://schemas.microsoft.com/office/word/2010/wordml"',
            'xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml"',
            $xml
        );
        $xml = str_replace(
            'xmlns:ns3="http://schemas.openxmlformats.org/officeDocument/2006/relationships"',
            'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"',
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

        return $xml;
    }

    /** @deprecated dùng repairDocumentXml */
    public static function repairXml(string $xml): string
    {
        return self::repairDocumentXml($xml);
    }
}
