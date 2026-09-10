<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Validate Excel uploads by extension and file signature.
 *
 * Laravel's mimes rule relies on server MIME detection (finfo/file), which can
 * misidentify valid .xlsx files as application/octet-stream on some hosts.
 */
class ExcelUpload implements ValidationRule
{
    /** @var list<string> */
    private array $allowedExtensions;

    /**
     * @param  list<string>  $extraExtensions  e.g. ['csv']
     */
    public function __construct(array $extraExtensions = [])
    {
        $this->allowedExtensions = array_values(array_unique(array_merge(
            ['xls', 'xlsx'],
            array_map(static fn (string $ext): string => strtolower($ext), $extraExtensions)
        )));
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail($this->message());

            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());
        if (! in_array($extension, $this->allowedExtensions, true)) {
            $fail($this->message());

            return;
        }

        if (in_array($extension, ['csv', 'txt'], true)) {
            return;
        }

        $path = $value->getPathname();
        if (! is_readable($path)) {
            return;
        }

        $header = file_get_contents($path, false, null, 0, 4);
        if ($header === false || strlen($header) < 4) {
            $fail($this->message());

            return;
        }

        if ($extension === 'xlsx' && $header !== "PK\x03\x04") {
            $fail($this->message());

            return;
        }

        if ($extension === 'xls' && $header !== "\xD0\xCF\x11\xE0") {
            $fail($this->message());
        }
    }

    private function message(): string
    {
        $labels = array_map(static fn (string $ext): string => '.'.$ext, $this->allowedExtensions);

        return 'File phải là Excel ('.implode(', ', $labels).').';
    }
}
