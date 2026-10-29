<?php

namespace App\Support\Docx;

/**
 * Thay chữ trong document.xml khi Word tách một cụm chữ ra nhiều <w:r> (vd. «70|7|/|TTML|-202|6»).
 * Phần thay thế nằm trong run chứa ký tự đầu tiên nên giữ định dạng của run đó.
 */
final class DocxTextReplacer
{
    public const W_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    public static function xpath(\DOMDocument $dom): \DOMXPath
    {
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('w', self::W_NS);

        return $xpath;
    }

    /** @return int số lần thay */
    public static function replace(\DOMXPath $xpath, \DOMNode $scope, string $from, string $to): int
    {
        if ($from === '') {
            return 0;
        }

        $count = 0;
        foreach ($xpath->query('.//w:p', $scope) ?: [] as $p) {
            while (self::replaceOnceInParagraph($xpath, $p, $from, $to)) {
                $count++;
                if (str_contains($to, $from)) {
                    break;
                }
            }
        }

        return $count;
    }

    /** Thay toàn bộ chữ của đoạn, giữ định dạng run có chữ đầu tiên. */
    public static function setParagraphText(\DOMXPath $xpath, \DOMElement $p, string $text): void
    {
        $nodes = iterator_to_array($xpath->query('.//w:t', $p) ?: []);
        if ($nodes === []) {
            $run = $p->ownerDocument->createElementNS(self::W_NS, 'w:r');
            $t = $p->ownerDocument->createElementNS(self::W_NS, 'w:t');
            $run->appendChild($t);
            $p->appendChild($run);
            $nodes = [$t];
        }

        $first = null;
        foreach ($nodes as $t) {
            if ($first === null && trim($t->textContent) !== '') {
                $first = $t;
            }
        }
        $first ??= $nodes[0];

        foreach ($nodes as $t) {
            self::setText($t, $t === $first ? $text : '');
        }
    }

    /** Đoạn có chữ đầu tiên trong ô bảng. */
    public static function setCellText(\DOMXPath $xpath, \DOMElement $tc, string $text): void
    {
        $paras = iterator_to_array($xpath->query('w:p', $tc) ?: []);
        if ($paras === []) {
            return;
        }
        $target = $paras[0];
        foreach ($paras as $p) {
            if (trim($p->textContent) !== '') {
                $target = $p;
                break;
            }
        }
        self::setParagraphText($xpath, $target, $text);
        foreach ($paras as $p) {
            if ($p !== $target) {
                foreach ($xpath->query('.//w:t', $p) ?: [] as $t) {
                    self::setText($t, '');
                }
            }
        }
    }

    private static function replaceOnceInParagraph(\DOMXPath $xpath, \DOMElement $p, string $from, string $to): bool
    {
        /** @var list<\DOMElement> $nodes */
        $nodes = iterator_to_array($xpath->query('.//w:t', $p) ?: []);
        $full = '';
        $ranges = [];
        foreach ($nodes as $i => $t) {
            $start = strlen($full);
            $full .= $t->textContent;
            $ranges[$i] = [$start, strlen($full)];
        }

        $pos = strpos($full, $from);
        if ($pos === false) {
            return false;
        }
        $end = $pos + strlen($from);

        $placed = false;
        foreach ($nodes as $i => $t) {
            [$s, $e] = $ranges[$i];
            if ($e <= $pos || $s >= $end) {
                continue;
            }
            $text = $t->textContent;
            $before = substr($text, 0, max(0, $pos - $s));
            $after = substr($text, max(0, min(strlen($text), $end - $s)));
            self::setText($t, $before.($placed ? '' : $to).$after);
            $placed = true;
        }

        return true;
    }

    private static function setText(\DOMElement $t, string $text): void
    {
        while ($t->firstChild !== null) {
            $t->removeChild($t->firstChild);
        }
        $t->appendChild($t->ownerDocument->createTextNode($text));
        $t->setAttributeNS('http://www.w3.org/XML/1998/namespace', 'xml:space', 'preserve');
    }
}
