<?php

namespace App\Support\SatHach;

final class Utf8
{
    public static function sanitize(mixed $value): string
    {
        $text = (string) $value;
        if ($text === '') {
            return '';
        }

        if (! mb_check_encoding($text, 'UTF-8')) {
            $detected = mb_detect_encoding(
                $text,
                ['UTF-8', 'UTF-16LE', 'UTF-16BE', 'Windows-1258', 'Windows-1252', 'ISO-8859-1'],
                true
            );
            if (is_string($detected) && $detected !== '') {
                $converted = @mb_convert_encoding($text, 'UTF-8', $detected);
                $text = is_string($converted) ? $converted : $text;
            }
        }

        $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $text);

        return $clean === false ? '' : $clean;
    }
}
