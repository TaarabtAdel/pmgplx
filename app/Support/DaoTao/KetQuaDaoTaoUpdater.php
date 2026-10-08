<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\DatDSPhien;
use App\Models\PMGPLX\NguoiLXHoSo;
use Illuminate\Support\Facades\DB;

class KetQuaDaoTaoUpdater
{
    public const PREVIEW_UPDATE_LIMIT = 200;

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

    /** Tạm thời: các cột KQ KT trên file phải > giá trị này. */
    private const NGUONG_KQ_KT_MIN = 0.0;

    /** @var list<string> */
    private const NON_NUMERIC_UPDATE_FIELDS = ['KetLuanCSDT', 'NgayRaQDTN', 'SoGiayCNTN', 'TGBatDau', 'TGKetThuc'];

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
        'DiemKQTienLui',
        'TGBatDau',
        'TGKetThuc',
        'NgayRaQDTN',
        'KetLuanCSDT',
        'SoGiayCNTN',
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
        'DiemKQTienLui' => 'KQ tiến lùi',
        'TGBatDau' => 'TG bắt đầu',
        'TGKetThuc' => 'TG kết thúc',
        'NgayRaQDTN' => 'Ngày HTKH',
        'KetLuanCSDT' => 'Kết luận CSDT',
        'SoGiayCNTN' => 'Số giấy CNTN',
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

            $g = self::asNumber($record['tg_thuc_hanh_hinh'] ?? null);
            $h = self::asNumber($record['qd_thuc_hanh_hinh'] ?? null);
            $diemLt = self::asNumber($record['diem_kq_ly_thuyet'] ?? null);
            $diemThDuong = self::asNumber($record['diem_kq_thuc_hanh'] ?? null);
            $diemMoPhong = self::asNumber($record['diem_kq_mo_phong'] ?? null);
            $diemThHinh = self::asNumber($record['diem_kq_hinh'] ?? null);
            $diemTienLui = self::asNumber($record['diem_kq_tien_lui'] ?? null);

            $ketLuan = $this->ketLuanCsdt(
                $nhom,
                $g,
                $h,
                $gioMayChu,
                $kmMayChu,
                $diemLt,
                $diemThDuong,
                $diemMoPhong,
                $diemThHinh,
            );

            $payload = [
                'TongQDThucHanh' => $kmMayChu,
                'DiemKQLyThuyet' => $diemLt,
                'DiemKQThucHanh' => $diemThDuong,
                'TGThucHanhHinh' => $g,
                'TGThucHanhDuong' => $gioMayChu,
                'QDThucHanhHinh' => $h,
                'DiemKQMoPhong' => $diemMoPhong,
                'DiemKQHinh' => $diemThHinh,
                'DiemKQTienLui' => $diemTienLui,
                'TGBatDau' => self::normalizeYmd($record['tg_bat_dau'] ?? null),
                'TGKetThuc' => self::normalizeYmd($record['tg_ket_thuc'] ?? null),
                'NgayRaQDTN' => $record['ngay_ra_kqtn'] ?? null,
                'KetLuanCSDT' => $ketLuan,
                'SoGiayCNTN' => self::soGiayCntnFromHoSo($hoSo),
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

    public static function isNumericUpdateField(string $field): bool
    {
        return in_array($field, self::UPDATE_FIELDS, true)
            && ! in_array($field, self::NON_NUMERIC_UPDATE_FIELDS, true);
    }

    /** Ô Excel / DB rỗng (null, '') → 0 khi tính kết luận và ghi số. */
    public static function normalizeYmd(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', (string) $value) ?? '';
        if (strlen($digits) === 8) {
            return $digits;
        }

        try {
            return \Carbon\Carbon::parse((string) $value)->format('Ymd');
        } catch (\Throwable) {
            return trim((string) $value) !== '' ? trim((string) $value) : null;
        }
    }

    public static function soGiayCntnFromHoSo(NguoiLXHoSo $hoSo): ?string
    {
        $maDk = trim((string) ($hoSo->MaDK ?? ''));
        $hangDaoTao = trim((string) ($hoSo->HangDaoTao ?? ''));

        if ($maDk === '' || $hangDaoTao === '') {
            return null;
        }

        return $maDk.'-'.$hangDaoTao;
    }

    public static function asNumber(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_bool($value)) {
            return $value ? 1.0 : 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $text = str_replace([' ', ','], ['', '.'], trim((string) $value));

        return is_numeric($text) ? (float) $text : 0.0;
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

    /**
     * Giải thích Kết luận CSDT (4 ngưỡng G/H/P/Q + 4 KQ KT trên file).
     *
     * @return array{
     *     ket_luan: int,
     *     dat_du: bool,
     *     nguong: array<string, float>|null,
     *     chi_tiet: list<array{key: string, label: string, value: float, min: float, ok: bool, min_exclusive?: bool}>
     * }
     */
    public static function explainKetLuan(
        string $nhom,
        float $g,
        float $h,
        float $p,
        float $q,
        float $diemLyThuyet = 0,
        float $diemThDuong = 0,
        float $diemMoPhong = 0,
        float $diemThHinh = 0,
    ): array {
        $nguong = self::NGUONG[$nhom] ?? null;
        if ($nguong === null) {
            return [
                'ket_luan' => 0,
                'dat_du' => false,
                'nguong' => null,
                'chi_tiet' => [],
            ];
        }

        $ktMin = self::NGUONG_KQ_KT_MIN;

        $chiTiet = [
            [
                'key' => 'g',
                'label' => 'Thời gian thực hành hình (G)',
                'value' => $g,
                'min' => (float) $nguong['g'],
                'ok' => $g >= (float) $nguong['g'],
            ],
            [
                'key' => 'h',
                'label' => 'Quãng đường thực hành hình (H)',
                'value' => $h,
                'min' => (float) $nguong['h'],
                'ok' => $h >= (float) $nguong['h'],
            ],
            [
                'key' => 'p',
                'label' => 'Thời gian thực hành đường (P)',
                'value' => $p,
                'min' => (float) $nguong['p'],
                'ok' => $p >= (float) $nguong['p'],
            ],
            [
                'key' => 'q',
                'label' => 'Quãng đường thực hành đường (Q)',
                'value' => $q,
                'min' => (float) $nguong['q'],
                'ok' => $q >= (float) $nguong['q'],
            ],
            [
                'key' => 'DiemKQLyThuyet',
                'label' => 'KQ KT lý thuyết (I)',
                'value' => $diemLyThuyet,
                'min' => $ktMin,
                'min_exclusive' => true,
                'ok' => $diemLyThuyet > $ktMin,
            ],
            [
                'key' => 'DiemKQThucHanh',
                'label' => 'KQ KT TH đường (M)',
                'value' => $diemThDuong,
                'min' => $ktMin,
                'min_exclusive' => true,
                'ok' => $diemThDuong > $ktMin,
            ],
            [
                'key' => 'DiemKQMoPhong',
                'label' => 'KQ KT mô phỏng (J)',
                'value' => $diemMoPhong,
                'min' => $ktMin,
                'min_exclusive' => true,
                'ok' => $diemMoPhong > $ktMin,
            ],
            [
                'key' => 'DiemKQHinh',
                'label' => 'KQ KT TH hình (K)',
                'value' => $diemThHinh,
                'min' => $ktMin,
                'min_exclusive' => true,
                'ok' => $diemThHinh > $ktMin,
            ],
        ];

        $datDu = ! in_array(false, array_column($chiTiet, 'ok'), true);

        return [
            'ket_luan' => $datDu ? 1 : 0,
            'dat_du' => $datDu,
            'nguong' => $nguong,
            'chi_tiet' => $chiTiet,
        ];
    }

    /**
     * Thử tính kết quả một học viên từ file đang chờ import.
     *
     * @return array<string, mixed>
     */
    public function testOneFromFile(string $path, string $maHocVien): array
    {
        $maHocVien = trim($maHocVien);
        if ($maHocVien === '') {
            return ['success' => false, 'message' => 'Nhập mã học viên (cột B file Excel).'];
        }

        $records = (new KetQuaDaoTaoExcelParser())->readAllRecordsFromFile($path);
        $record = null;
        foreach ($records as $row) {
            if (trim((string) ($row['ma_hoc_vien'] ?? '')) === $maHocVien) {
                $record = $row;
                break;
            }
        }

        if ($record === null) {
            return [
                'success' => false,
                'message' => 'Không có dòng nào trong file với mã HV «'.$maHocVien.'».',
            ];
        }

        $analysis = $this->analyzeRecords([$record]);
        if ($analysis['updates'] !== []) {
            $update = $analysis['updates'][0];
            $payload = $update['payload'] ?? [];
            $explain = self::explainKetLuan(
                (string) ($update['nhom'] ?? self::NHOM_KHAC),
                self::asNumber($record['tg_thuc_hanh_hinh'] ?? null),
                self::asNumber($record['qd_thuc_hanh_hinh'] ?? null),
                self::asNumber($payload['TGThucHanhDuong'] ?? null),
                self::asNumber($payload['TongQDThucHanh'] ?? null),
                self::asNumber($record['diem_kq_ly_thuyet'] ?? null),
                self::asNumber($record['diem_kq_thuc_hanh'] ?? null),
                self::asNumber($record['diem_kq_mo_phong'] ?? null),
                self::asNumber($record['diem_kq_hinh'] ?? null),
            );

            return [
                'success' => true,
                'file_row' => $record,
                'update' => $update,
                'explain' => $explain,
            ];
        }

        $skip = $analysis['skipped'][0] ?? null;

        return [
            'success' => false,
            'message' => is_array($skip) ? (string) ($skip['reason'] ?? 'Không cập nhật được.') : 'Không cập nhật được.',
            'skip' => $skip,
            'file_row' => $record,
        ];
    }

    /**
     * Cập nhật một học viên vào NguoiLX_HoSo (cùng payload như import hàng loạt).
     *
     * @return array{updated: int, ma_hoc_vien: string}
     */
    public function applyOneFromFile(string $path, string $maHocVien): array
    {
        $test = $this->testOneFromFile($path, $maHocVien);
        if (! ($test['success'] ?? false)) {
            throw new \RuntimeException((string) ($test['message'] ?? 'Không lưu được học viên này.'));
        }

        $update = $test['update'];
        $ma = trim((string) ($update['ma_hoc_vien'] ?? ''));
        NguoiLXHoSo::query()
            ->where('MaDK', $ma)
            ->update($update['payload']);

        return [
            'updated' => 1,
            'ma_hoc_vien' => $ma,
        ];
    }

    private function ketLuanCsdt(
        string $nhom,
        float $g,
        float $h,
        float $p,
        float $q,
        float $diemLyThuyet,
        float $diemThDuong,
        float $diemMoPhong,
        float $diemThHinh,
    ): int {
        return self::explainKetLuan(
            $nhom,
            $g,
            $h,
            $p,
            $q,
            $diemLyThuyet,
            $diemThDuong,
            $diemMoPhong,
            $diemThHinh,
        )['ket_luan'];
    }

    /**
     * @return array<string, mixed>
     */
    private function currentValues(NguoiLXHoSo $hoSo): array
    {
        $values = [];
        foreach (self::UPDATE_FIELDS as $field) {
            $raw = $hoSo->getAttribute($field);
            $values[$field] = self::isNumericUpdateField($field) ? self::asNumber($raw) : $raw;
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

        foreach (['TGBatDau', 'TGKetThuc'] as $ymdField) {
            if (! empty($values[$ymdField])) {
                $values[$ymdField] = self::normalizeYmd($values[$ymdField]);
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
