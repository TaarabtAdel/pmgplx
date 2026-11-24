<?php

namespace App\Support\DaoTao;

use App\Models\PMGPLX\NguoiLX;
use App\Support\QldtOto\KtmChiTiet\DocSheet;
use Carbon\Carbon;

/** CCCD (PMGPLX) → mã học viên DAT (= MaDK trên phiên DAT). */
final class PhuLuc6CccdMaHocVien
{
    /**
     * @param  list<string>  $cccids
     * @return array<string, array{ma_hoc_vien: string, ho_ten: string, ngay_sinh: ?string, hang: string, loi: string}>
     */
    public static function resolve(array $cccids, string $maKhoaHoc): array
    {
        $cccids = array_values(array_unique(array_filter(array_map(
            static fn ($c) => DocSheet::normalizeCccd((string) $c),
            $cccids
        ))));
        if ($cccids === []) {
            return [];
        }

        $byCccd = [];
        foreach ($cccids as $cccd) {
            $byCccd[$cccd] = [
                'ma_hoc_vien' => '',
                'ho_ten' => '',
                'ngay_sinh' => null,
                'hang' => '',
                'loi' => 'Không tìm thấy học viên theo CCCD trong khóa.',
            ];
        }

        foreach (array_chunk($cccids, 300) as $chunk) {
            $rows = NguoiLX::query()
                ->from('NguoiLX as n')
                ->join('NguoiLX_HoSo as h', 'h.MaDK', '=', 'n.MaDK')
                ->where('h.MaKhoaHoc', $maKhoaHoc)
                ->where(function ($q) use ($chunk) {
                    $q->whereIn('n.SoCMT', $chunk)->orWhereIn('n.SO_CMND_CU', $chunk);
                })
                ->select([
                    'n.MaDK',
                    'n.HoVaTen',
                    'n.HoDemNLX',
                    'n.TenNLX',
                    'n.NgaySinh',
                    'n.SoCMT',
                    'n.SO_CMND_CU',
                    'h.HangGPLX',
                ])
                ->orderByDesc('n.MaDK')
                ->get();

            /** @var array<string, list<object>> $nhom */
            $nhom = [];
            foreach ($rows as $row) {
                foreach ([$row->SoCMT, $row->SO_CMND_CU ?? ''] as $so) {
                    $k = DocSheet::normalizeCccd((string) $so);
                    if ($k === '' || ! in_array($k, $chunk, true)) {
                        continue;
                    }
                    $nhom[$k] ??= [];
                    $nhom[$k][] = $row;
                }
            }

            foreach ($chunk as $cccd) {
                $candidates = $nhom[$cccd] ?? [];
                if ($candidates === []) {
                    continue;
                }
                if (count($candidates) > 1) {
                    $byCccd[$cccd]['loi'] = 'Nhiều mã ĐK trùng CCCD trong khóa — cần kiểm tra PMGPLX.';
                    $byCccd[$cccd]['ma_hoc_vien'] = (string) $candidates[0]->MaDK;

                    continue;
                }
                $n = $candidates[0];
                $hoTen = trim((string) ($n->HoVaTen ?: trim(($n->HoDemNLX ?? '').' '.($n->TenNLX ?? ''))));
                $byCccd[$cccd] = [
                    'ma_hoc_vien' => (string) $n->MaDK,
                    'ho_ten' => $hoTen,
                    'ngay_sinh' => self::ngaySinhYmd($n->NgaySinh),
                    'hang' => trim((string) ($n->HangGPLX ?? '')),
                    'loi' => '',
                ];
            }
        }

        return $byCccd;
    }

    private static function ngaySinhYmd(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}