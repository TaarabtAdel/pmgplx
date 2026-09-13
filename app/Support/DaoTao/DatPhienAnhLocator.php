<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\DatDSPhien;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class DatPhienAnhLocator
{
    public const FILE_PATTERN = '/^(\d{2})-(\d{2})-(\d{2})\.(jpe?g|png|webp)$/i';

    /**
     * @return array{
     *     ok: bool,
     *     message?: string,
     *     so_anh?: int,
     *     anh?: list<array{ten: string, thang: string, ngay: string, thoi_gian: string, url: string}>
     * }
     */
    public function forPhien(DatDSPhien $phien, string $basePath): array
    {
        $resolvedBase = $this->resolveBasePath($basePath);
        if ($resolvedBase === null) {
            return [
                'ok' => false,
                'message' => 'Không đọc được thư mục ảnh đã cấu hình. Kiểm tra đường dẫn hoặc mount vào Docker.',
            ];
        }

        $maKhoaHoc = trim((string) ($phien->MaKhoaHoc ?? ''));
        $maHocVien = trim((string) ($phien->MaHocVien ?? ''));
        if ($maKhoaHoc === '' || $maHocVien === '') {
            return [
                'ok' => false,
                'message' => 'Phiên thiếu mã khóa học hoặc mã học viên.',
            ];
        }

        $start = $phien->ThoiGianBatDauPhienHoc;
        $end = $phien->ThoiGianKetThucPhienHoc;
        if (! $start instanceof Carbon || ! $end instanceof Carbon) {
            return [
                'ok' => false,
                'message' => 'Phiên thiếu thời gian bắt đầu / kết thúc.',
            ];
        }

        $from = $start->copy()->startOfMinute();
        $to = $end->copy()->endOfMinute();
        if ($to->lt($from)) {
            return [
                'ok' => false,
                'message' => 'Thời gian kết thúc phiên sớm hơn thời gian bắt đầu.',
            ];
        }

        $images = [];
        $period = CarbonPeriod::create($from->copy()->startOfDay(), '1 day', $to->copy()->startOfDay());

        foreach ($period as $day) {
            $month = $day->format('m');
            $dayOfMonth = $day->format('d');
            $directory = $resolvedBase
                .DIRECTORY_SEPARATOR.$maKhoaHoc
                .DIRECTORY_SEPARATOR.$maHocVien
                .DIRECTORY_SEPARATOR.$month
                .DIRECTORY_SEPARATOR.$dayOfMonth;

            if (! is_dir($directory)) {
                continue;
            }

            $entries = scandir($directory);
            if ($entries === false) {
                continue;
            }

            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                $parsed = $this->parseFileName($entry);
                if ($parsed === null) {
                    continue;
                }

                $takenAt = $day->copy()->setTime($parsed['hour'], $parsed['minute'], $parsed['second']);
                if ($takenAt->lt($from) || $takenAt->gt($to)) {
                    continue;
                }

                $fullPath = $directory.DIRECTORY_SEPARATOR.$entry;
                if (! is_file($fullPath)) {
                    continue;
                }

                $images[] = [
                    'ten' => $entry,
                    'thang' => $month,
                    'ngay' => $dayOfMonth,
                    'thoi_gian' => $takenAt->format('H:i:s'),
                    'url' => route('daotao.pdt.dat.do-phien-anh.xem-anh', [
                        'id' => (int) $phien->Id,
                        'thang' => $month,
                        'ngay' => $dayOfMonth,
                        'ten' => $entry,
                    ]),
                ];
            }
        }

        usort($images, static function (array $a, array $b): int {
            return [$a['thang'], $a['ngay'], $a['ten']] <=> [$b['thang'], $b['ngay'], $b['ten']];
        });

        return [
            'ok' => true,
            'so_anh' => count($images),
            'anh' => array_values($images),
        ];
    }

    public function absoluteFilePath(DatDSPhien $phien, string $basePath, string $thang, string $ngay, string $ten): ?string
    {
        $resolvedBase = $this->resolveBasePath($basePath);
        if ($resolvedBase === null) {
            return null;
        }

        if (! preg_match('/^\d{2}$/', $thang) || ! preg_match('/^\d{2}$/', $ngay)) {
            return null;
        }

        if ($this->parseFileName($ten) === null) {
            return null;
        }

        $listed = $this->forPhien($phien, $resolvedBase);
        if (! ($listed['ok'] ?? false)) {
            return null;
        }

        foreach ($listed['anh'] ?? [] as $item) {
            if ($item['thang'] === $thang && $item['ngay'] === $ngay && $item['ten'] === $ten) {
                $path = $resolvedBase
                    .DIRECTORY_SEPARATOR.trim((string) $phien->MaKhoaHoc)
                    .DIRECTORY_SEPARATOR.trim((string) $phien->MaHocVien)
                    .DIRECTORY_SEPARATOR.$thang
                    .DIRECTORY_SEPARATOR.$ngay
                    .DIRECTORY_SEPARATOR.$ten;

                $realFile = realpath($path);
                $realBase = realpath($resolvedBase);
                if ($realFile === false || $realBase === false || ! str_starts_with($realFile, $realBase)) {
                    return null;
                }

                return $realFile;
            }
        }

        return null;
    }

    /**
     * @return array{hour: int, minute: int, second: int}|null
     */
    public function parseFileName(string $name): ?array
    {
        if (! preg_match(self::FILE_PATTERN, $name, $matches)) {
            return null;
        }

        $hour = (int) $matches[1];
        $minute = (int) $matches[2];
        $second = (int) $matches[3];

        if ($hour > 23 || $minute > 59 || $second > 59) {
            return null;
        }

        return [
            'hour' => $hour,
            'minute' => $minute,
            'second' => $second,
        ];
    }

    public function resolveBasePath(string $basePath): ?string
    {
        $basePath = DatAnhDuongDan::normalize($basePath);
        if ($basePath === '') {
            return null;
        }

        $real = realpath($basePath);
        if ($real === false || ! is_dir($real)) {
            return null;
        }

        return $real;
    }
}
