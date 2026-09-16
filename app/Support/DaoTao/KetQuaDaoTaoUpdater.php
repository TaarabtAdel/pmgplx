<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\DatDSPhien;
use App\Models\PMGPLX\NguoiLXHoSo;
use Illuminate\Support\Facades\DB;

class KetQuaDaoTaoUpdater
{
    public const PREVIEW_UPDATE_LIMIT = 50;

    public const NHOM_B_SAN = 'B_SAN';

    public const NHOM_B_TU_DONG = 'B_TU_DONG';

    public const NHOM_C1 = 'C1';

    public const NHOM_KHAC = 'KHAC';

    /** @var array<string, array{g: float, h: float, p: float, q: float}> */
    private const NGUONG = [
        self::NHOM_B_SAN => ['g' => 34, 'h' => 120, 'p' => 20, 'q' => 810],
        self::NHOM_B_TU_DONG => ['g' => 34, 'h' => 120, 'p' => 12, 'q' => 710],
        self::NHOM_C1 => ['g' => 35, 'h' => 113, 'p' => 24, 'q' => 830],
    ];

    /** @var list<string> */
    public const UPDATE_FIELDS = [
        'TongQDThucHanh',
        'DiemKQLyThuyet',
        'DiemKQThucHanh',
        'TGThucHanhHinh',
        'TGThucHanhDuong',
        'QDThucHanhHinh',
        'DiemKQMoPhong',
        'DiemKQHinh',
        'NgayRaQDTN',
        'KetLuanCSDT',
    ];

    /** @var array<string, string> */
    public const FIELD_LABELS = [
        'TongQDThucHanh' => 'Quãng đường TH đường (km)',
        'DiemKQLyThuyet' => 'KQ KT lý thuyết',
        'DiemKQThucHanh' => 'KQ KT TH đường',
        'TGThucHanhHinh' => 'TG TH hình (giờ)',
        'TGThucHanhDuong' => 'TG TH đường (giờ)',
        'QDThucHanhHinh' => 'Quãng đường TH hình (km)',
        'DiemKQMoPhong' => 'KQ KT mô phỏng',
        'DiemKQHinh' => 'KQ KT TH hình',
        'NgayRaQDTN' => 'Ngày cấp giấy HTKH',
        'KetLuanCSDT' => 'Kết luận CSDT',
    ];

    /**
     * @return array{
     *     updates: list<array<string, mixed>>,
     *     skipped: list<array<string, mixed>>,
     *     meta: array<string, int>
     * }
     */
    public function analyzeFromFile(string $path): array
    {
        $records = (new KetQuaDaoTaoExcelParser())->readAllRecordsFromFile($path);

        return $this->analyzeRecords($records);
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @return array{
     *     updates: list<array<string, mixed>>,
     *     skipped: list<array<string, mixed>>,
     *     meta: array<string, int>
     * }
     */
    public function analyzeRecords(array $records): array
    {
        $maHocViens = [];
        foreach ($records as $record) {
            $ma = trim((string) ($record['ma_hoc_vien'] ?? ''));
            if ($ma !== '') {
                $maHocViens[$ma] = true;
            }
        }

        $hoSoByMa = NguoiLXHoSo::query()
            ->whereIn('MaDK', array_keys($maHocViens))
            ->get()
            ->keyBy(fn (NguoiLXHoSo $row): string => trim((string) $row->MaDK));

        $datTotalsByKhoa = [];
        $updates = [];
        $skipped = [];
        $seen = [];

        foreach ($records as $record) {
            $maHocVien = trim((string) ($record['ma_hoc_vien'] ?? ''));
            if ($maHocVien === '') {
                $skipped[] = $this->skipRow($record, 'Thiếu mã học viên');
                continue;
            }

            if (isset($seen[$maHocVien])) {
                $skipped[] = $this->skipRow($record, 'Trùng mã học viên trong file (đã lấy dòng trước)');
                continue;
            }
            $seen[$maHocVien] = true;

            $hoSo = $hoSoByMa->get($maHocVien);
            if ($hoSo === null) {
                $skipped[] = $this->skipRow($record, 'Không tìm thấy NguoiLX_HoSo theo MaDK');
                continue;
            }

            $maKhoaHoc = trim((string) ($hoSo->MaKhoaHoc ?? ''));
            if ($maKhoaHoc === '') {
                $maKhoaHoc = $this->inferMaKhoaHocFromSessions($maHocVien);
            }

            if ($maKhoaHoc !== '' && ! isset($datTotalsByKhoa[$maKhoaHoc])) {
                $datTotalsByKhoa[$maKhoaHoc] = DatTheoDoiDat::studentServerTotalsByMaHocVien($maKhoaHoc, true);
            }

            $dat = ($maKhoaHoc !== '' ? ($datTotalsByKhoa[$maKhoaHoc][$maHocVien] ?? null) : null)
                ?? ['gio' => 0.0, 'km' => 0.0];
            $gioMayChu = (float) ($dat['gio'] ?? 0);
            $kmMayChu = (float) ($dat['km'] ?? 0);

            $hang = strtoupper(trim((string) ($hoSo->HangGPLX ?? '')));
            $nhom = self::nhomHang($hang);
            $ketLuan = $this->ketLuanCsdt(
                $nhom,
                (float) ($record['tg_thuc_hanh_hinh'] ?? 0),
                (float) ($record['qd_thuc_hanh_hinh'] ?? 0),
                $gioMayChu,
                $kmMayChu
            );

            $payload = [
                'TongQDThucHanh' => $kmMayChu,
                'DiemKQLyThuyet' => $record['diem_kq_ly_thuyet'],
                'DiemKQThucHanh' => $record['diem_kq_thuc_hanh'],
                'TGThucHanhHinh' => $record['tg_thuc_hanh_hinh'],
                'TGThucHanhDuong' => $gioMayChu,
                'QDThucHanhHinh' => $record['qd_thuc_hanh_hinh'],
                'DiemKQMoPhong' => $record['diem_kq_mo_phong'],
                'DiemKQHinh' => $record['diem_kq_hinh'],
                'NgayRaQDTN' => $record['ngay_ra_kqtn'],
                'KetLuanCSDT' => $ketLuan,
            ];

            $updates[] = [
                'excel_row' => $record['excel_row'] ?? null,
                'stt' => $record['stt'] ?? '',
                'ma_hoc_vien' => $maHocVien,
                'ho_ten' => $record['ho_ten'] ?? '',
                'ma_khoa_hoc' => $maKhoaHoc,
                'hang_gplx' => $hang,
                'nhom' => $nhom,
                'nhom_label' => self::nhomLabel($nhom),
                'payload' => $payload,
                'hien_tai' => $this->currentValues($hoSo),
                'thieu_dat' => $gioMayChu <= 0 && $kmMayChu <= 0,
            ];
        }

        return [
            'updates' => $updates,
            'skipped' => $skipped,
            'meta' => [
                'file_count' => count($records),
                'update_count' => count($updates),
                'skip_count' => count($skipped),
                'dat_count' => count(array_filter($updates, fn (array $row): bool => (int) $row['payload']['KetLuanCSDT'] === 1)),
                'khong_dat_count' => count(array_filter($updates, fn (array $row): bool => (int) $row['payload']['KetLuanCSDT'] === 0)),
            ],
        ];
    }

    /**
     * @return array{updated: int, skipped: int}
     */
    public function applyFromFile(string $path): array
    {
        $analysis = $this->analyzeFromFile($path);
        $updated = 0;

        DB::connection((new NguoiLXHoSo())->getConnectionName() ?: config('database.default'))->transaction(function () use ($analysis, &$updated): void {
            foreach ($analysis['updates'] as $row) {
                NguoiLXHoSo::query()
                    ->where('MaDK', $row['ma_hoc_vien'])
                    ->update($row['payload']);
                $updated++;
            }
        });

        return [
            'updated' => $updated,
            'skipped' => count($analysis['skipped']),
        ];
    }

    public static function nhomHang(string $hang): string
    {
        $hang = strtoupper(trim($hang));
        $hang = str_replace([' ', '.', '-'], '', $hang);

        if ($hang === 'B11') {
            return self::NHOM_B_TU_DONG;
        }

        if ($hang === 'C1' || str_starts_with($hang, 'C')) {
            return self::NHOM_C1;
        }

        if ($hang === 'B' || $hang === 'B1' || $hang === 'B2' || str_starts_with($hang, 'B')) {
            return self::NHOM_B_SAN;
        }

        return self::NHOM_KHAC;
    }

    public static function nhomLabel(string $nhom): string
    {
        return match ($nhom) {
            self::NHOM_B_TU_DONG => 'B tự động',
            self::NHOM_B_SAN => 'B sàn',
            self::NHOM_C1 => 'C1',
            default => 'Không xác định',
        };
    }

    private function ketLuanCsdt(string $nhom, float $g, float $h, float $p, float $q): int
    {
        $nguong = self::NGUONG[$nhom] ?? null;
        if ($nguong === null) {
            return 0;
        }

        if ($g < $nguong['g'] || $h < $nguong['h'] || $p < $nguong['p'] || $q < $nguong['q']) {
            return 0;
        }

        return 1;
    }

    /**
     * @return array<string, mixed>
     */
    private function currentValues(NguoiLXHoSo $hoSo): array
    {
        $values = [];
        foreach (self::UPDATE_FIELDS as $field) {
            $values[$field] = $hoSo->getAttribute($field);
        }

        if (isset($values['KetLuanCSDT'])) {
            $values['KetLuanCSDT'] = $values['KetLuanCSDT'] === null ? null : ((bool) $values['KetLuanCSDT'] ? 1 : 0);
        }

        if (! empty($values['NgayRaQDTN'])) {
            try {
                $values['NgayRaQDTN'] = \Carbon\Carbon::parse($values['NgayRaQDTN'])->format('Y-m-d');
            } catch (\Throwable) {
            }
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    private function skipRow(array $record, string $reason): array
    {
        return [
            'excel_row' => $record['excel_row'] ?? null,
            'stt' => $record['stt'] ?? '',
            'ma_hoc_vien' => $record['ma_hoc_vien'] ?? '',
            'ho_ten' => $record['ho_ten'] ?? '',
            'reason' => $reason,
        ];
    }

    private function inferMaKhoaHocFromSessions(string $maHocVien): string
    {
        $maKhoa = DatDSPhien::query()
            ->where('MaHocVien', $maHocVien)
            ->whereNotNull('MaKhoaHoc')
            ->where('MaKhoaHoc', '!=', '')
            ->value('MaKhoaHoc');

        return trim((string) $maKhoa);
    }
}
