<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\HocVienThongTinPhu;
use App\Models\PMGPLX\NguoiLX;
use Illuminate\Support\Facades\DB;

class HocVienSdtSaleTuFileUpdater
{
    public const TRANG_CAP_NHAT = 'cap_nhat';

    public const TRANG_KHONG_DOI = 'khong_doi';

    public const TRANG_KHONG_TIM_THAY = 'khong_tim_thay';

    public const TRANG_NHIEU_HV = 'nhieu_hoc_vien';

    /** @var array<string, string> */
    private array $nhanTrangThai = [
        self::TRANG_CAP_NHAT => 'Sẽ cập nhật',
        self::TRANG_KHONG_DOI => 'Không đổi',
        self::TRANG_KHONG_TIM_THAY => 'Không tìm thấy HV',
        self::TRANG_NHIEU_HV => 'Nhiều học viên cùng CCCD',
    ];

    /**
     * @param  list<array{sheet_index: int, sheet_name: string, excel_row: int, cccd: string, sdt: string, sale: string}>  $rawRows
     * @return array{meta: array<string, int>, sheets: list<array{sheet_index: int, sheet_name: string, meta: array<string, int>, preview: list<array<string, mixed>>}>}
     */
    public function analyzeAllSheets(array $rawRows, int $previewPerSheet = HocVienSdtSaleTuFileExcel::PREVIEW_SAMPLE): array
    {
        $bySheet = [];
        foreach ($rawRows as $row) {
            $idx = (int) $row['sheet_index'];
            $bySheet[$idx]['sheet_name'] = $row['sheet_name'];
            $bySheet[$idx]['rows'][] = $row;
        }

        ksort($bySheet);

        $cccids = array_values(array_unique(array_map(fn ($r) => $r['cccd'], $rawRows)));
        $lookup = $this->traCuuHocVienTheoCccd($cccids);
        $phuCu = $this->thongTinPhuHienTai(array_column($lookup, 'ma_dk'));

        $sheets = [];
        $meta = [
            'sheet_count' => 0,
            'row_count' => count($rawRows),
            'will_update' => 0,
            'unchanged' => 0,
            'not_found' => 0,
            'ambiguous' => 0,
        ];

        foreach ($bySheet as $idx => $pack) {
            $enriched = $this->phanTichHang($pack['rows'], $lookup, $phuCu);
            $sheetMeta = ['row_count' => count($enriched)];
            foreach ([self::TRANG_CAP_NHAT, self::TRANG_KHONG_DOI, self::TRANG_KHONG_TIM_THAY, self::TRANG_NHIEU_HV] as $k) {
                $sheetMeta[$k] = count(array_filter($enriched, fn ($r) => $r['trang_thai'] === $k));
            }
            foreach (['will_update' => self::TRANG_CAP_NHAT, 'unchanged' => self::TRANG_KHONG_DOI, 'not_found' => self::TRANG_KHONG_TIM_THAY, 'ambiguous' => self::TRANG_NHIEU_HV] as $metaKey => $status) {
                $meta[$metaKey] += $sheetMeta[$status];
            }

            $sheets[] = [
                'sheet_index' => $idx,
                'sheet_name' => $pack['sheet_name'],
                'meta' => $sheetMeta,
                'preview' => array_slice($enriched, 0, $previewPerSheet),
            ];
        }

        $meta['sheet_count'] = count($sheets);

        return ['meta' => $meta, 'sheets' => $sheets];
    }

    /**
     * @return array{saved: int, unchanged: int, skipped: int, not_found: int, ambiguous: int}
     */
    public function applySheet(string $filePath, int $sheetIndex, ?int $nguoiCapNhatId): array
    {
        $spreadsheet = HocVienSdtSaleTuFileExcel::loadSpreadsheet($filePath);
        try {
            $sheet = HocVienSdtSaleTuFileExcel::sheetByIndex($spreadsheet, $sheetIndex);
            if ($sheet === null) {
                return ['saved' => 0, 'unchanged' => 0, 'skipped' => 0, 'not_found' => 0, 'ambiguous' => 0];
            }
            $rows = HocVienSdtSaleTuFileExcel::docSheet($sheet, $sheetIndex);
        } finally {
            HocVienSdtSaleTuFileExcel::releaseSpreadsheet($spreadsheet);
        }

        $rows = $this->gopTrungCccd($rows);
        $cccids = array_column($rows, 'cccd');
        $lookup = $this->traCuuHocVienTheoCccd($cccids);
        $phuCu = $this->thongTinPhuHienTai(array_column($lookup, 'ma_dk'));

        $result = ['saved' => 0, 'unchanged' => 0, 'skipped' => 0, 'not_found' => 0, 'ambiguous' => 0];
        $now = now();

        DB::connection('sqlsrv_manhlinh')->transaction(function () use ($rows, $lookup, $phuCu, $nguoiCapNhatId, $now, &$result): void {
            foreach (array_chunk($rows, 150) as $chunk) {
                foreach ($chunk as $row) {
                    $cccd = $row['cccd'];
                    $match = $lookup[$cccd] ?? null;
                    if ($match === null) {
                        $result['not_found']++;

                        continue;
                    }
                    if (! empty($match['ambiguous'])) {
                        $result['ambiguous']++;

                        continue;
                    }
                    $maDk = $match['ma_dk'];
                    $sdtMoi = $row['sdt'] !== '' ? $row['sdt'] : null;
                    $saleMoi = $row['sale'] !== '' ? $row['sale'] : null;

                    $cu = $phuCu[$maDk] ?? ['sdt' => null, 'sale' => null];
                    $sdtCu = $cu['sdt'];
                    $saleCu = $cu['sale'];

                    if ($this->bangNhau($sdtCu, $sdtMoi) && $this->bangNhau($saleCu, $saleMoi)) {
                        $result['unchanged']++;

                        continue;
                    }

                    $record = HocVienThongTinPhu::query()->firstOrNew(['MaDK' => $maDk]);
                    if (! $record->exists) {
                        $record->NgayTao = $now;
                    }
                    if ($sdtMoi !== null) {
                        $record->SoDienThoai = $sdtMoi;
                    }
                    if ($saleMoi !== null) {
                        $record->Sale = $saleMoi;
                    }
                    $record->NguoiCapNhatId = $nguoiCapNhatId;
                    $record->NgayCapNhat = $now;
                    $record->save();

                    $phuCu[$maDk] = [
                        'sdt' => $record->SoDienThoai,
                        'sale' => $record->Sale,
                    ];
                    $result['saved']++;
                }
            }
        });

        return $result;
    }

    public function nhanTrangThai(string $key): string
    {
        return $this->nhanTrangThai[$key] ?? $key;
    }

    /**
     * @param  list<array{cccd: string, sdt: string, sale: string, sheet_name: string, excel_row: int}>  $rows
     * @param  array<string, array{ma_dk: string, ho_ten: string, ambiguous: bool}>  $lookup
     * @param  array<string, array{sdt: ?string, sale: ?string}>  $phuCu
     * @return list<array<string, mixed>>
     */
    private function phanTichHang(array $rows, array $lookup, array $phuCu): array
    {
        $out = [];
        foreach ($rows as $row) {
            $cccd = $row['cccd'];
            $match = $lookup[$cccd] ?? null;
            $trang = self::TRANG_KHONG_TIM_THAY;
            $maDk = '';
            $hoTen = '';

            if ($match !== null) {
                if (! empty($match['ambiguous'])) {
                    $trang = self::TRANG_NHIEU_HV;
                } else {
                    $maDk = $match['ma_dk'];
                    $hoTen = $match['ho_ten'];
                    $cu = $phuCu[$maDk] ?? ['sdt' => null, 'sale' => null];
                    $sdtMoi = $row['sdt'] !== '' ? $row['sdt'] : null;
                    $saleMoi = $row['sale'] !== '' ? $row['sale'] : null;
                    $trang = ($this->bangNhau($cu['sdt'], $sdtMoi) && $this->bangNhau($cu['sale'], $saleMoi))
                        ? self::TRANG_KHONG_DOI
                        : self::TRANG_CAP_NHAT;
                }
            }

            $out[] = [
                'sheet_name' => $row['sheet_name'],
                'excel_row' => $row['excel_row'],
                'cccd' => $cccd,
                'sdt' => $row['sdt'],
                'sale' => $row['sale'],
                'ma_dk' => $maDk,
                'ho_ten' => $hoTen,
                'sdt_cu' => $maDk !== '' ? ($phuCu[$maDk]['sdt'] ?? '') : '',
                'sale_cu' => $maDk !== '' ? ($phuCu[$maDk]['sale'] ?? '') : '',
                'trang_thai' => $trang,
                'trang_thai_nhan' => $this->nhanTrangThai($trang),
            ];
        }

        return $out;
    }

    /**
     * @param  list<string>  $cccids
     * @return array<string, array{ma_dk: string, ho_ten: string, ambiguous: bool}>
     */
    private function traCuuHocVienTheoCccd(array $cccids): array
    {
        $cccids = array_values(array_unique(array_filter($cccids)));
        if ($cccids === []) {
            return [];
        }

        $map = [];
        foreach (array_chunk($cccids, 400) as $chunk) {
            $rows = NguoiLX::query()
                ->select(['MaDK', 'HoVaTen', 'HoDemNLX', 'TenNLX', 'SoCMT', 'SO_CMND_CU'])
                ->where(function ($q) use ($chunk) {
                    $q->whereIn('SoCMT', $chunk)->orWhereIn('SO_CMND_CU', $chunk);
                })
                ->orderByDesc('MaDK')
                ->get();

            foreach ($rows as $n) {
                $hoTen = $n->HoVaTen ?: trim(($n->HoDemNLX ?? '').' '.($n->TenNLX ?? ''));
                foreach ([HocVienSdtSaleTuFileExcel::chuanHoaCccd($n->SoCMT), HocVienSdtSaleTuFileExcel::chuanHoaCccd($n->SO_CMND_CU ?? '')] as $k) {
                    if ($k === '' || ! in_array($k, $chunk, true)) {
                        continue;
                    }
                    if (! isset($map[$k])) {
                        $map[$k] = ['ma_dk' => (string) $n->MaDK, 'ho_ten' => $hoTen, 'ambiguous' => false, 'count' => 1];
                    } else {
                        if ($map[$k]['ma_dk'] !== (string) $n->MaDK) {
                            $map[$k]['ambiguous'] = true;
                            $map[$k]['count']++;
                        }
                    }
                }
            }
        }

        return $map;
    }

    /**
     * @param  list<string>  $maDks
     * @return array<string, array{sdt: ?string, sale: ?string}>
     */
    private function thongTinPhuHienTai(array $maDks): array
    {
        $maDks = array_values(array_unique(array_filter($maDks)));
        if ($maDks === []) {
            return [];
        }

        $out = [];
        foreach (array_chunk($maDks, 500) as $chunk) {
            foreach (HocVienThongTinPhu::query()->whereIn('MaDK', $chunk)->get() as $r) {
                $out[(string) $r->MaDK] = [
                    'sdt' => $r->SoDienThoai,
                    'sale' => $r->Sale,
                ];
            }
        }

        return $out;
    }

    /**
     * @param  list<array{cccd: string, sdt: string, sale: string, sheet_index: int, sheet_name: string, excel_row: int}>  $rows
     * @return list<array{cccd: string, sdt: string, sale: string, sheet_index: int, sheet_name: string, excel_row: int}>
     */
    private function gopTrungCccd(array $rows): array
    {
        $by = [];
        foreach ($rows as $row) {
            $by[$row['cccd']] = $row;
        }

        return array_values($by);
    }

    private function bangNhau(?string $a, ?string $b): bool
    {
        $a = $a === null ? '' : trim($a);
        $b = $b === null ? '' : trim($b);

        return $a === $b;
    }
}
