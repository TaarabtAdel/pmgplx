<?php

namespace App\Support\QldtOto;

use App\Models\DaoTao\KhoaDaoTao;
use App\Models\PMGPLX\GiaoVien;
use App\Models\PMGPLX\KhoaHoc;
use App\Models\PMGPLX\XeTap;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PhanCongGvXeTapLaiFormSupport
{
    /** @var list<string> */
    public const NOI_DUNG_OPTIONS = ['GVLT', 'GVTH', 'Xe tự động'];

    public const NOI_DUNG_XE_TU_DONG = 'Xe tự động';

    /** @return Collection<int, array{ma: string, ho_ten: string, ghi_chu: string, label: string}> */
    public static function giaoVienSelectOptions(): Collection
    {
        return GiaoVien::query()
            ->where('TrangThai', 1)
            ->orderBy('TenGV')
            ->orderBy('MaGV')
            ->get(['MaGV', 'HoTenDem', 'TenGV', 'GhiChu'])
            ->map(function (GiaoVien $gv): array {
                $hoTen = trim(($gv->HoTenDem ?? '').' '.($gv->TenGV ?? ''));
                $ghiChu = trim((string) ($gv->GhiChu ?? ''));
                $label = $hoTen.' ('.$gv->MaGV.')';
                if ($ghiChu !== '') {
                    $label .= ' — '.$ghiChu;
                }

                return [
                    'ma' => (string) $gv->MaGV,
                    'ho_ten' => $hoTen,
                    'ghi_chu' => $ghiChu,
                    'label' => $label,
                ];
            })
            ->values();
    }

    /** @return Collection<int, array{bien_so: string, label: string}> */
    public static function xeSelectOptions(): Collection
    {
        return XeTap::query()
            ->where('TrangThai', 1)
            ->orderBy('BienSoXe')
            ->get(['BienSoXe', 'NhanHieu', 'HangGPLXXe'])
            ->map(function (XeTap $xe): array {
                $bien = (string) $xe->BienSoXe;
                $extra = trim((string) (($xe->NhanHieu ?? '') !== '' ? $xe->NhanHieu : ($xe->HangGPLXXe ?? '')));
                $label = $bien;
                if ($extra !== '') {
                    $label .= ' — '.$extra;
                }

                return ['bien_so' => $bien, 'label' => $label];
            })
            ->values();
    }

    /** Khớp biển số (Ghi chú GV) với danh mục XeTap. */
    public static function bienSoTuDanhMucXe(?string $raw): string
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return '';
        }

        if (XeTap::query()->where('BienSoXe', $raw)->exists()) {
            return $raw;
        }

        $norm = self::normalizeBienSoKey($raw);
        $all = XeTap::query()->where('TrangThai', 1)->pluck('BienSoXe');
        foreach ($all as $bien) {
            if (self::normalizeBienSoKey((string) $bien) === $norm) {
                return (string) $bien;
            }
        }

        return $raw;
    }

    private static function normalizeBienSoKey(string $value): string
    {
        return mb_strtoupper(preg_replace('/[\s.\-]/u', '', $value) ?? $value);
    }

    /**
     * Gắn họ tên từ mã GV; «Xe tự động» → biển số từ GhiChu (cột xe trên danh mục GV).
     *
     * @param  array<int, array<string, mixed>>  $rowsInput
     * @return array<int, array<string, mixed>>
     */
    public static function resolveGiaoVienRows(array $rowsInput): array
    {
        foreach ($rowsInput as &$row) {
            $maGv = trim((string) ($row['ma_gv'] ?? ''));
            if ($maGv !== '') {
                $gv = GiaoVien::query()->find($maGv);
                if ($gv instanceof GiaoVien) {
                    $row['ho_ten'] = trim(($gv->HoTenDem ?? '').' '.($gv->TenGV ?? ''));
                    if (trim((string) ($row['noi_dung'] ?? '')) === self::NOI_DUNG_XE_TU_DONG) {
                        $row['bien_so'] = self::bienSoTuDanhMucXe((string) ($gv->GhiChu ?? ''));
                    }
                }
            }
            if (trim((string) ($row['bien_so'] ?? '')) !== '') {
                $row['bien_so'] = self::bienSoTuDanhMucXe((string) $row['bien_so']);
            }
        }
        unset($row);

        return $rowsInput;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rowsInput
     * @return list<array<string, mixed>>
     */
    public static function recordsFromForm(string $tenKhoa, array $rowsInput): array
    {
        $tenKhoaNorm = KhoaDaoTao::normalizeTenKhoa($tenKhoa);
        $records = [];

        foreach ($rowsInput as $i => $row) {
            $line = $i + 1;
            $hoTen = trim((string) ($row['ho_ten'] ?? ''));
            $tu = trim((string) ($row['tu_ngay'] ?? ''));
            $den = trim((string) ($row['den_ngay'] ?? ''));
            $bienSo = trim((string) ($row['bien_so'] ?? ''));
            $noiDung = trim((string) ($row['noi_dung'] ?? ''));

            $tuNgay = self::parseDate($tu);
            $denNgay = self::parseDate($den);

            $records[] = [
                'form_row' => $line,
                'excel_row' => $line,
                'HoTen' => $hoTen,
                'TuNgay' => $tuNgay,
                'DenNgay' => $denNgay,
                'ThoiGian' => self::formatThoiGian($tuNgay, $denNgay, $tu, $den),
                'TenKhoa' => $tenKhoaNorm,
                'BienSo' => $bienSo,
                'NoiDungGiangDay' => $noiDung,
            ];
        }

        return $records;
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @return list<array{stt: int, giao_vien: string, thoi_gian: string, khoa_dt: string, bien_so: string, noi_dung: string}>
     */
    public static function docxRows(string $tenKhoaShort, array $records): array
    {
        $out = [];
        foreach ($records as $i => $record) {
            $out[] = [
                'stt' => $i + 1,
                'giao_vien' => trim((string) ($record['HoTen'] ?? '')),
                'thoi_gian' => (string) ($record['ThoiGian'] ?? ''),
                'khoa_dt' => $tenKhoaShort,
                'bien_so' => trim((string) ($record['BienSo'] ?? '')),
                'noi_dung' => trim((string) ($record['NoiDungGiangDay'] ?? '')),
            ];
        }

        return $out;
    }

    public static function tenKhoaNganTuPm(KhoaHoc $khoa): string
    {
        $ten = trim((string) ($khoa->TenKH ?? ''));
        if (preg_match('/K(\d+)\s*$/i', $ten, $m)) {
            return 'K'.$m[1];
        }
        if (preg_match('/BK(\d+)/i', $ten, $m)) {
            return 'K'.$m[1];
        }

        return $ten !== '' ? $ten : (string) $khoa->MaKH;
    }

    public static function tenKhoaDaoTaoTuPm(KhoaHoc $khoa): string
    {
        $ten = trim((string) ($khoa->TenKH ?? ''));

        return $ten !== '' ? KhoaDaoTao::normalizeTenKhoa($ten) : (string) $khoa->MaKH;
    }

    private static function parseDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        try {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                return Carbon::parse($value)->toDateString();
            }
            if (preg_match('#^\d{1,2}/\d{1,2}/\d{4}$#', $value)) {
                return Carbon::createFromFormat('d/m/Y', $value)->toDateString();
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    private static function formatThoiGian(?string $tu, ?string $den, string $tuRaw, string $denRaw): string
    {
        if ($tu && $den) {
            $a = Carbon::parse($tu)->format('d/m/Y');
            $b = Carbon::parse($den)->format('d/m/Y');

            return $a.' - '.$b;
        }

        $parts = array_filter([trim($tuRaw), trim($denRaw)]);

        return implode(' - ', $parts);
    }
}
