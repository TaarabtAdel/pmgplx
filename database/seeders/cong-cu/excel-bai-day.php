<?php

// php database/seeders/cong-cu/excel-bai-day.php <file.xlsx> <Hang> <Ca>
// In ra khối dữ liệu để dán vào DU_LIEU của database/seeders/QldtOtoBaiDaySeeder.php.
// File Excel: dòng 1 là tiêu đề; các cột Ngày thứ | Số giờ | Nội dung | Giờ BD | Giờ KT.
// Mỗi dòng Excel = 1 bài dạy; Giờ BD/KT giữ nguyên văn (vd "13H59' -17H59'").

require __DIR__.'/../../../vendor/autoload.php';

[$_, $file, $hang, $ca] = $argv;

$chu = static function (mixed $v): ?string {
    $v = trim((string) $v);

    return $v === '' ? null : $v;
};

$sheet = PhpOffice\PhpSpreadsheet\IOFactory::load($file)->getActiveSheet();
$out = [];
foreach ($sheet->toArray(null, true, true, false) as $i => $row) {
    if ($i === 0) {
        continue;
    }
    [$ngay, $soGio, $noiDung, $gioBd, $gioKt] = array_pad($row, 5, null);
    if ($chu($ngay) === null) {
        continue;
    }
    $out[] = [
        (int) $ngay,
        $chu($soGio) !== null ? (float) $soGio : null,
        (string) $chu($noiDung),
        $chu($gioBd),
        $chu($gioKt),
    ];
}

$fmt = static fn ($v) => $v === null
    ? 'null'
    : (is_string($v) ? "'".str_replace(['\\', "'"], ['\\\\', "\\'"], $v)."'" : rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.'));

echo "        '{$hang}|{$ca}' => [\n";
echo "            // [Ngày số, Số giờ, Nội dung, Giờ BĐ, Giờ KT]\n";
foreach ($out as $r) {
    echo '            ['.implode(', ', array_map($fmt, $r))."],\n";
}
echo "        ],\n";
fwrite(STDERR, count($out)." dòng\n");
