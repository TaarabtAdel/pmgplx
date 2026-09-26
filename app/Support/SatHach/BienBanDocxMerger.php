<?php

namespace App\Support\SatHach;

use RuntimeException;
use ZipArchive;

/**
 * Gộp nhiều DOCX (cùng mẫu) thành 1 file Word thật: nối body + media, không dùng altChunk.
 */
class BienBanDocxMerger
{
    /**
     * @param  list<string>  $pageDocxPaths
     */
    public function merge(array $pageDocxPaths, string $outputPath): void
    {
        $files = [];
        foreach ($pageDocxPaths as $path) {
            if (is_string($path) && is_file($path)) {
                $files[] = $path;
            }
        }

        if ($files === []) {
            throw new RuntimeException('Không có file Word để gộp.');
        }

        @mkdir(dirname($outputPath), 0755, true);

        if (count($files) === 1) {
            if (! @copy($files[0], $outputPath)) {
                throw new RuntimeException('Không ghi được file Word tổng.');
            }

            return;
        }

        $tmp = $outputPath.'.building.docx';
        @unlink($tmp);
        if (! @copy($files[0], $tmp)) {
            throw new RuntimeException('Không tạo được file Word tổng.');
        }

        $zip = new ZipArchive();
        if ($zip->open($tmp) !== true) {
            @unlink($tmp);
            throw new RuntimeException('Không mở được file Word tổng.');
        }

        $docXml = (string) $zip->getFromName('word/document.xml');
        $relsXml = (string) $zip->getFromName('word/_rels/document.xml.rels');
        $contentTypes = (string) $zip->getFromName('[Content_Types].xml');
        [$baseBody, $sectPr] = $this->splitBody($docXml);
        $nextRid = $this->maxRid($relsXml) + 1;
        $nextDocPr = $this->maxDocPr($docXml) + 1;
        $extraRels = '';
        $extraBody = '';

        for ($i = 1, $n = count($files); $i < $n; $i++) {
            $part = $this->extractPart($files[$i], $i + 1, $nextRid, $nextDocPr);
            $nextRid = $part['nextRid'];
            $nextDocPr = $part['nextDocPr'];
            foreach ($part['media'] as $zipPath => $bytes) {
                $zip->addFromString($zipPath, $bytes);
            }
            $extraRels .= $part['rels'];
            $extraBody .= $this->pageBreak().$part['body'];
            $contentTypes = $this->ensureMediaTypes($contentTypes);
        }

        $zip->addFromString('word/document.xml', $this->rebuildDocument($docXml, $baseBody.$extraBody.$sectPr));
        $zip->addFromString('word/_rels/document.xml.rels', $this->appendRels($relsXml, $extraRels));
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->close();

        @unlink($outputPath);
        if (! @rename($tmp, $outputPath) && ! @copy($tmp, $outputPath)) {
            @unlink($tmp);
            throw new RuntimeException('Không lưu được file Word tổng.');
        }
        @unlink($tmp);
    }

    /**
     * @return array{body: string, rels: string, media: array<string, string>, nextRid: int, nextDocPr: int}
     */
    private function extractPart(string $path, int $pageNo, int $nextRid, int $nextDocPr): array
    {
        $src = new ZipArchive();
        if ($src->open($path) !== true) {
            throw new RuntimeException('Không đọc được file trang '.$pageNo);
        }

        $docXml = (string) $src->getFromName('word/document.xml');
        $relsXml = (string) $src->getFromName('word/_rels/document.xml.rels');
        [$body] = $this->splitBody($docXml);

        $ridMap = [];
        $newRels = '';
        $media = [];

        if (preg_match_all('/<Relationship\b[^>]*>/i', $relsXml, $relMatches)) {
            foreach ($relMatches[0] as $rel) {
                if (! preg_match('/\bType="([^"]+)"/i', $rel, $typeMatch)) {
                    continue;
                }
                if (! preg_match('/\bId="(rId[^"]+)"/i', $rel, $idMatch)) {
                    continue;
                }
                if (! preg_match('/\bTarget="([^"]+)"/i', $rel, $targetMatch)) {
                    continue;
                }

                $oldRid = $idMatch[1];
                $target = str_replace('\\', '/', $targetMatch[1]);
                if (! preg_match('#(?:^|/)(media|embeddings)/#i', $target)) {
                    continue;
                }
                if (str_starts_with($target, '/')) {
                    $zipMedia = ltrim($target, '/');
                } else {
                    $zipMedia = 'word/'.ltrim($target, './');
                    $zipMedia = preg_replace('#^word/word/#', 'word/', $zipMedia) ?: $zipMedia;
                }

                $bytes = $src->getFromName($zipMedia);
                if ($bytes === false) {
                    continue;
                }

                $ext = pathinfo($zipMedia, PATHINFO_EXTENSION) ?: 'bin';
                $newName = sprintf('p%d_%s.%s', $pageNo, substr(md5($oldRid.$zipMedia), 0, 10), $ext);
                $media['word/media/'.$newName] = $bytes;
                $newRid = 'rId'.$nextRid;
                $nextRid++;
                $ridMap[$oldRid] = $newRid;
                $newRels .= '<Relationship Id="'.$newRid.'" Type="'.$typeMatch[1].'" Target="media/'.$newName.'"/>';
            }
        }

        $src->close();

        foreach ($ridMap as $old => $new) {
            $body = str_replace('"'.$old.'"', '"'.$new.'"', $body);
        }

        $body = preg_replace_callback(
            '/<(?:wp:docPr|wpg:docPr|pic:cNvPr|wps:cNvPr)([^>]*?)\sid="(\d+)"/',
            static function (array $m) use (&$nextDocPr): string {
                return str_replace('id="'.$m[2].'"', 'id="'.$nextDocPr++.'"', $m[0]);
            },
            $body
        ) ?? $body;

        return [
            'body' => $body,
            'rels' => $newRels,
            'media' => $media,
            'nextRid' => $nextRid,
            'nextDocPr' => $nextDocPr,
        ];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitBody(string $documentXml): array
    {
        if (! preg_match('#<w:body\b[^>]*>(.*)</w:body>#s', $documentXml, $m)) {
            throw new RuntimeException('File Word thiếu nội dung.');
        }

        $inner = $m[1];
        if (preg_match('#(<w:sectPr\b.*</w:sectPr>)#s', $inner, $s)) {
            $sectPr = $s[1];
            $content = str_replace($sectPr, '', $inner);

            return [$content, $sectPr];
        }

        return [$inner, ''];
    }

    private function rebuildDocument(string $originalXml, string $newInnerBody): string
    {
        return preg_replace(
            '#<w:body\b[^>]*>.*</w:body>#s',
            '<w:body>'.$newInnerBody.'</w:body>',
            $originalXml,
            1
        ) ?? $originalXml;
    }

    private function appendRels(string $relsXml, string $extra): string
    {
        if ($extra === '') {
            return $relsXml;
        }

        return preg_replace('#</Relationships>#', $extra.'</Relationships>', $relsXml, 1) ?? $relsXml;
    }

    private function maxRid(string $relsXml): int
    {
        $max = 0;
        if (preg_match_all('/\bId="rId(\d+)"/', $relsXml, $m)) {
            foreach ($m[1] as $n) {
                $max = max($max, (int) $n);
            }
        }

        return $max;
    }

    private function maxDocPr(string $documentXml): int
    {
        $max = 1;
        if (preg_match_all('/\b(?:wp:docPr|wpg:docPr)\s+id="(\d+)"/', $documentXml, $m)) {
            foreach ($m[1] as $n) {
                $max = max($max, (int) $n);
            }
        }

        return $max;
    }

    private function pageBreak(): string
    {
        return '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';
    }

    private function ensureMediaTypes(string $contentTypes): string
    {
        $needed = [
            'png' => 'image/png',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'emf' => 'image/x-emf',
            'wmf' => 'image/x-wmf',
        ];
        foreach ($needed as $ext => $mime) {
            if (! str_contains($contentTypes, 'Extension="'.$ext.'"')) {
                $contentTypes = str_replace(
                    '</Types>',
                    '<Default Extension="'.$ext.'" ContentType="'.$mime.'"/></Types>',
                    $contentTypes
                );
            }
        }

        return $contentTypes;
    }
}
