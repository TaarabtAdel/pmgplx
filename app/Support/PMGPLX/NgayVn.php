<?php

namespace App\Support\PMGPLX;

class NgayVn
{
    /**
     * Format compact date string to d/m/Y.
     *
     * @param  string|null  $value
     * @param  'ddmmyyyy'|'yyyymmdd'  $storedAs
     */
    public static function format(?string $value, string $storedAs = 'yyyymmdd'): string
    {
        $ns = trim((string) $value);
        if (strlen($ns) !== 8 || ! ctype_digit($ns)) {
            return $ns;
        }

        if ($storedAs === 'ddmmyyyy') {
            return substr($ns, 0, 2).'/'.substr($ns, 2, 2).'/'.substr($ns, 4, 4);
        }

        // yyyymmdd
        return substr($ns, 6, 2).'/'.substr($ns, 4, 2).'/'.substr($ns, 0, 4);
    }
}
