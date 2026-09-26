<?php

namespace App\Support\SatHach;

final class Utf8
{
    /** @var list<string>|null */
    private static ?array $mbEncodings = null;

    public static function sanitize(mixed $value): string
    {
        $text = (string) $value;
        if ($text === '') {
            return '';
        }

        if (mb_check_encoding($text, 'UTF-8')) {
            return self::stripInvalidUtf8($text);
        }

        foreach (self::sourceEncodings() as $from) {
            $converted = self::convertFrom($text, $from);
            if ($converted !== null && mb_check_encoding($converted, 'UTF-8')) {
                return self::stripInvalidUtf8($converted);
            }
        }

        return self::stripInvalidUtf8($text);
    }

    /**
     * Thứ tự ưu tiên: tiếng Việt Windows (CP1258) rồi Latin/Western.
     *
     * @return list<string>
     */
    private static function sourceEncodings(): array
    {
        return ['CP1258', 'Windows-1252', 'ISO-8859-1', 'UTF-16LE', 'UTF-16BE'];
    }

    private static function convertFrom(string $text, string $from): ?string
    {
        if (self::mbSupports($from)) {
            $out = @mb_convert_encoding($text, 'UTF-8', $from);
            if (is_string($out) && $out !== '') {
                return $out;
            }
        }

        $out = @iconv($from, 'UTF-8//IGNORE', $text);

        return ($out !== false && $out !== '') ? $out : null;
    }

    private static function mbSupports(string $encoding): bool
    {
        if (self::$mbEncodings === null) {
            self::$mbEncodings = mb_list_encodings();
        }

        return in_array($encoding, self::$mbEncodings, true);
    }

    private static function stripInvalidUtf8(string $text): string
    {
        $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $text);

        return $clean === false ? '' : $clean;
    }
}
