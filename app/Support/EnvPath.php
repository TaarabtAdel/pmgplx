<?php

namespace App\Support;

class EnvPath
{
    /**
     * Giá trị đường dẫn từ .env (bỏ ngoặc, trim). Dùng slash / trên Windows vẫn hợp lệ với is_file().
     */
    public static function fromEnv(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }
        $path = trim($raw, " \t\n\r\0\x0B\"'");
        if ($path === '') {
            return null;
        }

        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }
}
