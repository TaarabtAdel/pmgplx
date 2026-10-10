<?php

namespace App\Support\PMGPLX;

use App\Models\PMGPLX\GiaoVien;
use App\Models\PMGPLX\KhoaHoc;
use App\Models\PMGPLX\KhoaHocXeTap;
use App\Support\DaoTao\DatPhanCongHocVienSaver;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Tính khung rảnh trong ngày của giáo viên theo lịch các khóa đã chọn.
 */
class GiaoVienLichTrongScanner
{
    public const MAX_RANGE_DAYS = 93;

    public const MAX_SLOTS = 15_000;

    public const MAX_ROWS = 2_000;

    /** Phút nghỉ sau buổi bận trước khi tính rảnh (vd. hết 10:00 → rảnh từ 10:01). */
    public const GAP_AFTER_BUSY_MINUTES = 1;

    /**
     * @param  array{
     *     ma_kh?: list<string>|string,
     *     ma_gv?: list<string>|string,
     *     tu_ngay?: string,
     *     den_ngay?: string,
     *     gio_bat_dau?: string,
     *     gio_ket_thuc?: string,
     *     include_xe?: bool,
     *     loai_gv?: string,
     *     trang_thai?: string|int|null,
     * }  $input
     * @return array{
     *     ok: bool,
     *     errors: list<string>,
     *     rows: list<array<string, mixed>>,
     *     stats: array<string, int|bool>
     * }
     */
    public static function scan(array $input): array
    {
        $pairs = self::normalizePairs($input['pairs'] ?? []);
        $tuNgay = trim((string) ($input['tu_ngay'] ?? ''));
        $denNgay = trim((string) ($input['den_ngay'] ?? ''));
        $gioBatDau = self::parseTime($input['gio_bat_dau'] ?? '06:00', '06:00');
        $gioKetThuc = self::parseTime($input['gio_ket_thuc'] ?? '22:00', '22:00');
        $trangThai = $input['trang_thai'] ?? '1';
        $hideDay = LichXeLoaiGhiChu::parseDoTrongHideDayFilters($input['bo_qua_loai'] ?? []);
        $boQuaLoai = $hideDay['loai'];
        $anNgayNghiTrongLich = $hideDay['an_ngay_nghi_trong_lich'];
        $minFreePhut = self::parseMinFreePhut($input['min_free_phut'] ?? 0);

        $errors = [];
        if ($pairs === []) {
            $errors[] = 'Thêm ít nhất một cặp khóa học — giáo viên.';
        }

        foreach ($pairs as $index => $pair) {
            $line = $index + 1;
            if ($pair['ma_kh'] === '') {
                $errors[] = "Cặp #{$line}: chọn khóa học.";
            }
            if ($pair['ma_gv_sang'] === '') {
                $errors[] = "Cặp #{$line}: chọn giáo viên sáng.";
            }
            if ($pair['ma_gv_chieu'] === '') {
                $errors[] = "Cặp #{$line}: chọn giáo viên chiều.";
            }
        }

        $maKhList = array_values(array_unique(array_column($pairs, 'ma_kh')));

        $resolved = null;
        if ($maKhList !== [] && $errors === []) {
            $resolved = self::resolveScanDates($maKhList, $tuNgay, $denNgay);
            if (isset($resolved['error'])) {
                $errors[] = $resolved['error'];
            }
        }

        if ($resolved !== null && ! isset($resolved['error'])) {
            try {
                $from = $resolved['from'];
                $to = $resolved['to'];
                if ($to->lt($from)) {
                    $errors[] = 'Đến ngày phải sau hoặc bằng từ ngày.';
                } elseif ($from->diffInDays($to) > self::MAX_RANGE_DAYS) {
                    $errors[] = 'Khoảng ngày vượt '.self::MAX_RANGE_DAYS
                        .' ngày — thu hẹp thủ công từ/đến hoặc bớt khóa.';
                }
            } catch (\Throwable) {
                $errors[] = 'Khoảng ngày không hợp lệ.';
            }
        }

        if ($gioKetThuc <= $gioBatDau) {
            $errors[] = 'Giờ kết thúc khung ngày phải sau giờ bắt đầu.';
        }

        if ($errors !== []) {
            return [
                'ok' => false,
                'errors' => $errors,
                'rows' => [],
                'groups' => [],
                'stats' => self::emptyStats(),
                'resolved_range' => null,
            ];
        }

        $from = $resolved['from'];
        $to = $resolved['to'];
        $rangeEnd = $to->copy()->endOfDay();
        $rangeNotice = self::rangeNotice($resolved);
        $tenKhoaByMa = self::loadTenKhoaByMa($maKhList);
        $khoaBoundsByMa = self::loadKhoaBoundsByMa($maKhList);

        $allRows = [];
        $groups = [];
        $slotsLoaded = 0;
        $slotsTruncated = false;

        foreach ($pairs as $index => $pair) {
            $pairLine = $index + 1;
            $maGvList = array_values(array_unique(array_filter([
                trim($pair['ma_gv_sang']),
                trim($pair['ma_gv_chieu']),
            ], static fn (string $v): bool => $v !== '')));

            $pairCtx = [
                'ma_kh' => $pair['ma_kh'],
                'ma_gv_sang' => $pair['ma_gv_sang'],
                'ma_gv_chieu' => $pair['ma_gv_chieu'],
                'loai' => $boQuaLoai,
                'an_ngay_nghi_trong_lich' => $anNgayNghiTrongLich,
                'min_free_phut' => $minFreePhut,
            ];

            $loaded = self::loadSlots(
                $from,
                $rangeEnd,
                [$pair['ma_kh']],
                $maGvList,
                $trangThai
            );
            $slotsLoaded += $loaded['slots']->count();
            $slotsTruncated = $slotsTruncated || $loaded['truncated'];

            $pairRows = self::buildDayRowsForTeamPair(
                $from,
                $to,
                $gioBatDau,
                $gioKetThuc,
                $pairCtx,
                $loaded['slots'],
                $tenKhoaByMa,
                $khoaBoundsByMa
            );

            $allRows = array_merge($allRows, $pairRows);
            $group = self::pairGroupFromTeamRows($pairCtx, $pairRows, $tenKhoaByMa, $pairLine);
            if ($group['days'] !== []) {
                $groups[] = $group;
            }
        }

        usort($allRows, function (array $a, array $b): int {
            $c = strcmp($a['ma_kh'] ?? '', $b['ma_kh'] ?? '');
            if ($c !== 0) {
                return $c;
            }
            $c = strcmp($a['ma_gv_sang_norm'] ?? '', $b['ma_gv_sang_norm'] ?? '');
            if ($c !== 0) {
                return $c;
            }
            $c = strcmp($a['ma_gv_chieu_norm'] ?? '', $b['ma_gv_chieu_norm'] ?? '');
            if ($c !== 0) {
                return $c;
            }

            return strcmp($a['ngay'] ?? '', $b['ngay'] ?? '');
        });

        $totalRows = count($allRows);
        $truncated = $totalRows > self::MAX_ROWS;
        if ($truncated) {
            $allRows = array_slice($allRows, 0, self::MAX_ROWS);
        }

        return [
            'ok' => true,
            'errors' => [],
            'notice' => $rangeNotice,
            'rows' => $allRows,
            'groups' => $groups,
            'resolved_range' => self::resolvedRangePayload($resolved),
            'stats' => [
                'slots_loaded' => $slotsLoaded,
                'slots_truncated' => $slotsTruncated,
                'pair_count' => count($pairs),
                'gv_count' => count($pairs),
                'day_count' => (int) $from->diffInDays($to) + 1,
                'rows_total' => $totalRows,
                'rows_shown' => count($allRows),
                'rows_truncated' => $truncated,
                'bo_qua_loai_label' => LichXeLoaiGhiChu::boQuaLoaiFiltersLabel($input['bo_qua_loai'] ?? []),
                'min_free_phut' => $minFreePhut,
            ],
        ];
    }

    /**
     * @param  list<array{ma_kh: string, ma_gv_sang?: string, ma_gv_chieu?: string, ma_gv?: string}>  $raw
     * @return list<array{ma_kh: string, ma_gv_sang: string, ma_gv_chieu: string}>
     */
    public static function normalizePairs(array $raw): array
    {
        $out = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $maKh = trim((string) ($row['ma_kh'] ?? ''));
            $legacyGv = trim((string) ($row['ma_gv'] ?? ''));
            $maGvSang = trim((string) ($row['ma_gv_sang'] ?? $legacyGv));
            $maGvChieu = trim((string) ($row['ma_gv_chieu'] ?? $maGvSang));
            if ($maKh === '' && $maGvSang === '' && $maGvChieu === '') {
                continue;
            }
            $out[] = [
                'ma_kh' => $maKh,
                'ma_gv_sang' => $maGvSang,
                'ma_gv_chieu' => $maGvChieu,
            ];
        }

        return $out;
    }

    /**
     * @param  list<array{ma_kh: string, ma_gv_sang: string, ma_gv_chieu: string}>  $pairs
     * @return list<array{pair_line: int, ca: string, ca_label: string, ma_kh: string, ma_gv: string}>
     */
    public static function expandPairsToScanUnits(array $pairs): array
    {
        $units = [];
        foreach ($pairs as $index => $pair) {
            $line = $index + 1;
            $sang = trim($pair['ma_gv_sang'] ?? '');
            $chieu = trim($pair['ma_gv_chieu'] ?? '');
            $sangNorm = DatPhanCongHocVienSaver::normalizeMaGiaoVien($sang);
            $chieuNorm = DatPhanCongHocVienSaver::normalizeMaGiaoVien($chieu);

            if ($sangNorm !== '' && $chieuNorm !== '' && $sangNorm === $chieuNorm) {
                $units[] = [
                    'pair_line' => $line,
                    'ca' => 'both',
                    'ca_label' => 'Sáng · Chiều',
                    'ma_kh' => $pair['ma_kh'],
                    'ma_gv' => $sang,
                ];

                continue;
            }

            if ($sang !== '') {
                $units[] = [
                    'pair_line' => $line,
                    'ca' => 'sang',
                    'ca_label' => 'Sáng',
                    'ma_kh' => $pair['ma_kh'],
                    'ma_gv' => $sang,
                ];
            }
            if ($chieu !== '' && $chieuNorm !== $sangNorm) {
                $units[] = [
                    'pair_line' => $line,
                    'ca' => 'chieu',
                    'ca_label' => 'Chiều',
                    'ma_kh' => $pair['ma_kh'],
                    'ma_gv' => $chieu,
                ];
            }
        }

        return $units;
    }

    public static function parseMinFreePhut(mixed $value): int
    {
        if ($value === '' || $value === null) {
            return 0;
        }
        $n = (int) $value;
        if ($n < 0) {
            return 0;
        }

        return min($n, 16 * 60);
    }

    /**
     * @param  list<string>  $maKhList
     * @return array<string, string>
     */
    private static function loadTenKhoaByMa(array $maKhList): array
    {
        if ($maKhList === []) {
            return [];
        }

        $map = [];
        KhoaHoc::query()
            ->whereIn('MaKH', $maKhList)
            ->get(['MaKH', 'TenKH'])
            ->each(function (KhoaHoc $kh) use (&$map): void {
                $map[trim((string) $kh->MaKH)] = trim((string) ($kh->TenKH ?? ''));
            });

        return $map;
    }

    /**
     * NgayKG–NgayBG theo từng khóa (key MaKH upper).
     *
     * @param  list<string>  $maKhList
     * @return array<string, array{tu: ?Carbon, den: ?Carbon}>
     */
    private static function loadKhoaBoundsByMa(array $maKhList): array
    {
        if ($maKhList === []) {
            return [];
        }

        $map = [];
        KhoaHoc::query()
            ->whereIn('MaKH', $maKhList)
            ->get(['MaKH', 'NgayKG', 'NgayBG'])
            ->each(function (KhoaHoc $khoa) use (&$map): void {
                $key = GiaoVienDoTrongCapXeTrongKhoa::normalizeMaKh((string) $khoa->MaKH);
                $tu = $khoa->NgayKG !== null
                    ? Carbon::parse($khoa->NgayKG)->startOfDay()
                    : null;
                $den = $khoa->NgayBG !== null
                    ? Carbon::parse($khoa->NgayBG)->startOfDay()
                    : null;
                if ($tu !== null && $den === null) {
                    $den = Carbon::today()->startOfDay();
                    if ($den->lt($tu)) {
                        $den = $tu->copy();
                    }
                }
                $map[$key] = ['tu' => $tu, 'den' => $den];
            });

        return $map;
    }

    /**
     * @param  array{ma_kh: string, ma_gv: string, loai: list<string>, min_free_phut: int}  $pair
     * @param  Collection<int, array<string, mixed>>  $slots
     * @param  array<string, string>  $tenKhoaByMa
     * @return list<array<string, mixed>>
     */
    private static function buildDayRowsForPair(
        Carbon $from,
        Carbon $to,
        string $gioBatDau,
        string $gioKetThuc,
        array $pair,
        Collection $slots,
        array $tenKhoaByMa
    ): array {
        $minFreePhut = (int) ($pair['min_free_phut'] ?? 0);
        $gvNorm = DatPhanCongHocVienSaver::normalizeMaGiaoVien($pair['ma_gv']);
        $gvMeta = self::buildGvMeta($slots, [$pair['ma_gv']], true);
        if ($gvMeta === []) {
            $gvMeta = [
                self::metaKeyForMaGvNorm($gvNorm) => [
                    'ma_gv_norm' => $gvNorm,
                    'ma_gv' => $pair['ma_gv'],
                    'ten_gv' => $pair['ma_gv'],
                ],
            ];
        }
        self::enrichTenGv($gvMeta);
        $meta = reset($gvMeta) ?: [
            'ma_gv_norm' => $gvNorm,
            'ma_gv' => $pair['ma_gv'],
            'ten_gv' => $pair['ma_gv'],
        ];

        $rows = [];
        $day = $from->copy();
        while ($day->lte($to)) {
            $dayKey = $day->toDateString();
            $boQuaLoai = $pair['loai'];
            $daySlots = $slots->filter(function (array $slot) use ($gvNorm, $dayKey): bool {
                if ((string) ($slot['ma_gv_norm'] ?? '') !== $gvNorm) {
                    return false;
                }

                return self::slotTouchesCalendarDay($slot['ngay_bd'], $slot['ngay_kt'], $dayKey);
            });

            if ($boQuaLoai !== [] && self::dayHasAnyBoQuaLoai($daySlots, $boQuaLoai)) {
                $day->addDay();

                continue;
            }

            $busy = self::mergedBusyForDay($day, $daySlots);
            $free = self::freeWindowsForDay($day, $gioBatDau, $gioKetThuc, $busy);
            $free = self::filterFreeWindowsByMinMinutes($free, $minFreePhut);
            if ($minFreePhut > 0 && $free === []) {
                $day->addDay();

                continue;
            }
            $busyDisplay = self::busyDisplayForDay($day, $daySlots);

            $loaiKey = implode(',', $pair['loai']);
            $rows[] = [
                'pair_key' => $pair['ma_kh'].'|'.$gvNorm.'|'.$loaiKey,
                'ma_kh' => $pair['ma_kh'],
                'ten_kh' => $tenKhoaByMa[$pair['ma_kh']] ?? $pair['ma_kh'],
                'loai_filter' => $pair['loai'],
                'loai_filter_label' => LichXeLoaiGhiChu::boQuaLoaiFiltersLabel($pair['loai']),
                'ma_gv_norm' => $gvNorm,
                'ma_gv' => $meta['ma_gv'],
                'ten_gv' => $meta['ten_gv'],
                'ngay' => $dayKey,
                'ngay_label' => $day->format('d/m/Y'),
                'busy_label' => $busyDisplay['busy_label'],
                'noi_dung_label' => $busyDisplay['noi_dung_label'],
                'loai_label' => $busyDisplay['loai_label'],
                'busy_segments' => $busyDisplay['segments'],
                'free_label' => self::formatIntervals($free),
                'busy_count' => count($busy),
            ];
            $day->addDay();
        }

        return $rows;
    }

    /**
     * Một hàng/ngày theo cặp GV sáng + chiều: bận hiển thị tách từng GV, rảnh = khung ngày trừ hợp nhất bận cả hai.
     *
     * @param  array{ma_kh: string, ma_gv_sang: string, ma_gv_chieu: string, loai: list<string>, min_free_phut: int, an_ngay_nghi_trong_lich?: bool}  $pair
     * @param  Collection<int, array<string, mixed>>  $slots
     * @param  array<string, string>  $tenKhoaByMa
     * @param  array<string, array{tu: ?Carbon, den: ?Carbon}>  $khoaBoundsByMa
     * @return list<array<string, mixed>>
     */
    private static function buildDayRowsForTeamPair(
        Carbon $from,
        Carbon $to,
        string $gioBatDau,
        string $gioKetThuc,
        array $pair,
        Collection $slots,
        array $tenKhoaByMa,
        array $khoaBoundsByMa = []
    ): array {
        $minFreePhut = (int) ($pair['min_free_phut'] ?? 0);
        $sangNorm = DatPhanCongHocVienSaver::normalizeMaGiaoVien($pair['ma_gv_sang']);
        $chieuNorm = DatPhanCongHocVienSaver::normalizeMaGiaoVien($pair['ma_gv_chieu']);
        $gvMeta = self::buildGvMeta($slots, [$pair['ma_gv_sang'], $pair['ma_gv_chieu']], true);
        self::enrichTenGv($gvMeta);
        $metaSang = $gvMeta[self::metaKeyForMaGvNorm($sangNorm)] ?? [
            'ma_gv_norm' => $sangNorm,
            'ma_gv' => $pair['ma_gv_sang'],
            'ten_gv' => $pair['ma_gv_sang'],
        ];
        $metaChieu = $gvMeta[self::metaKeyForMaGvNorm($chieuNorm)] ?? [
            'ma_gv_norm' => $chieuNorm,
            'ma_gv' => $pair['ma_gv_chieu'],
            'ten_gv' => $pair['ma_gv_chieu'],
        ];

        $khoaKey = GiaoVienDoTrongCapXeTrongKhoa::normalizeMaKh($pair['ma_kh']);
        $khoaBounds = $khoaBoundsByMa[$khoaKey] ?? null;

        $rows = [];
        $day = $from->copy();
        while ($day->lte($to)) {
            $dayKey = $day->toDateString();
            $boQuaLoai = $pair['loai'];
            $daySlotsSang = self::filterSlotsForGvOnDay($slots, $sangNorm, $dayKey);
            $daySlotsChieu = self::filterSlotsForGvOnDay($slots, $chieuNorm, $dayKey);
            $daySlotsCombined = $daySlotsSang->merge($daySlotsChieu);

            if ($boQuaLoai !== [] && self::dayHasAnyBoQuaLoai($daySlotsCombined, $boQuaLoai)) {
                $day->addDay();

                continue;
            }

            if (self::shouldHideNgayNghiTrongLichDay(
                $daySlotsCombined,
                (bool) ($pair['an_ngay_nghi_trong_lich'] ?? false),
                $day,
                $khoaBounds
            )) {
                $day->addDay();

                continue;
            }

            $busyCombined = self::mergedBusyForDay($day, $daySlotsCombined);
            $free = self::freeWindowsForDay($day, $gioBatDau, $gioKetThuc, $busyCombined);
            $free = self::filterFreeWindowsByMinMinutes($free, $minFreePhut);
            if ($minFreePhut > 0 && $free === []) {
                $day->addDay();

                continue;
            }

            $busyDisplaySang = self::busyDisplayForDay($day, $daySlotsSang);
            $busyDisplayChieu = self::busyDisplayForDay($day, $daySlotsChieu);

            $loaiKey = implode(',', $pair['loai']);
            $rows[] = [
                'pair_key' => $pair['ma_kh'].'|'.$sangNorm.'|'.$chieuNorm.'|'.$loaiKey,
                'ma_kh' => $pair['ma_kh'],
                'ten_kh' => $tenKhoaByMa[$pair['ma_kh']] ?? $pair['ma_kh'],
                'loai_filter' => $pair['loai'],
                'loai_filter_label' => LichXeLoaiGhiChu::boQuaLoaiFiltersLabel($pair['loai']),
                'ma_gv_sang_norm' => $sangNorm,
                'ma_gv_sang' => $metaSang['ma_gv'],
                'ten_gv_sang' => $metaSang['ten_gv'],
                'ma_gv_chieu_norm' => $chieuNorm,
                'ma_gv_chieu' => $metaChieu['ma_gv'],
                'ten_gv_chieu' => $metaChieu['ten_gv'],
                'ngay' => $dayKey,
                'ngay_label' => $day->format('d/m/Y'),
                'busy_a_label' => $busyDisplaySang['busy_label'],
                'noi_dung_a_label' => $busyDisplaySang['noi_dung_label'],
                'busy_a_segments' => $busyDisplaySang['segments'],
                'busy_b_label' => $busyDisplayChieu['busy_label'],
                'noi_dung_b_label' => $busyDisplayChieu['noi_dung_label'],
                'busy_b_segments' => $busyDisplayChieu['segments'],
                'free_label' => self::formatIntervals($free),
                'busy_count' => count($busyCombined),
            ];
            $day->addDay();
        }

        return $rows;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $slots
     * @return Collection<int, array<string, mixed>>
     */
    private static function filterSlotsForGvOnDay(Collection $slots, string $gvNorm, string $dayKey): Collection
    {
        return $slots->filter(function (array $slot) use ($gvNorm, $dayKey): bool {
            if ((string) ($slot['ma_gv_norm'] ?? '') !== $gvNorm) {
                return false;
            }

            return self::slotTouchesCalendarDay($slot['ngay_bd'], $slot['ngay_kt'], $dayKey);
        });
    }

    /**
     * @param  array{ma_kh: string, ma_gv_sang: string, ma_gv_chieu: string, loai: list<string>, min_free_phut?: int}  $pair
     * @param  list<array<string, mixed>>  $pairRows
     * @param  array<string, string>  $tenKhoaByMa
     * @return array<string, mixed>
     */
    private static function pairGroupFromTeamRows(array $pair, array $pairRows, array $tenKhoaByMa, int $pairLine): array
    {
        $sangNorm = DatPhanCongHocVienSaver::normalizeMaGiaoVien($pair['ma_gv_sang']);
        $chieuNorm = DatPhanCongHocVienSaver::normalizeMaGiaoVien($pair['ma_gv_chieu']);
        $tenKh = $tenKhoaByMa[$pair['ma_kh']] ?? $pair['ma_kh'];
        $tenGvSang = $pair['ma_gv_sang'];
        $tenGvChieu = $pair['ma_gv_chieu'];
        if ($pairRows !== []) {
            $tenGvSang = (string) ($pairRows[0]['ten_gv_sang'] ?? $tenGvSang);
            $tenGvChieu = (string) ($pairRows[0]['ten_gv_chieu'] ?? $tenGvChieu);
        }

        $days = array_map(static fn (array $row): array => [
            'ngay' => (string) ($row['ngay'] ?? ''),
            'ngay_label' => (string) ($row['ngay_label'] ?? ''),
            'busy_a_label' => (string) ($row['busy_a_label'] ?? ''),
            'noi_dung_a_label' => (string) ($row['noi_dung_a_label'] ?? '—'),
            'busy_a_segments' => $row['busy_a_segments'] ?? [],
            'busy_b_label' => (string) ($row['busy_b_label'] ?? ''),
            'noi_dung_b_label' => (string) ($row['noi_dung_b_label'] ?? '—'),
            'busy_b_segments' => $row['busy_b_segments'] ?? [],
            'free_label' => (string) ($row['free_label'] ?? ''),
        ], $pairRows);

        return [
            'pair_line' => $pairLine,
            'ma_kh' => $pair['ma_kh'],
            'ten_kh' => $tenKh,
            'ma_gv_sang' => $pair['ma_gv_sang'],
            'ma_gv_sang_norm' => $sangNorm,
            'ten_gv_sang' => $tenGvSang,
            'ma_gv_chieu' => $pair['ma_gv_chieu'],
            'ma_gv_chieu_norm' => $chieuNorm,
            'ten_gv_chieu' => $tenGvChieu,
            'loai_filter' => $pair['loai'],
            'loai_filter_label' => LichXeLoaiGhiChu::boQuaLoaiFiltersLabel($pair['loai']),
            'min_free_phut' => (int) ($pair['min_free_phut'] ?? 0),
            'days' => $days,
        ];
    }

    /**
     * @param  array{ma_kh: string, ma_gv: string, loai: list<string>}  $pair
     * @param  list<array<string, mixed>>  $pairRows
     * @param  array<string, string>  $tenKhoaByMa
     * @return array<string, mixed>
     */
    private static function pairGroupFromRows(array $pair, array $pairRows, array $tenKhoaByMa, int $pairLine, string $caLabel = ''): array
    {
        $gvNorm = DatPhanCongHocVienSaver::normalizeMaGiaoVien($pair['ma_gv']);
        $tenGv = $pair['ma_gv'];
        $tenKh = $tenKhoaByMa[$pair['ma_kh']] ?? $pair['ma_kh'];
        if ($pairRows !== []) {
            $tenGv = (string) ($pairRows[0]['ten_gv'] ?? $tenGv);
        }

        $days = array_map(static fn (array $row): array => [
            'ngay' => (string) ($row['ngay'] ?? ''),
            'ngay_label' => (string) ($row['ngay_label'] ?? ''),
            'busy_label' => (string) ($row['busy_label'] ?? ''),
            'noi_dung_label' => (string) ($row['noi_dung_label'] ?? '—'),
            'loai_label' => (string) ($row['loai_label'] ?? '—'),
            'busy_segments' => $row['busy_segments'] ?? [],
            'free_label' => (string) ($row['free_label'] ?? ''),
        ], $pairRows);

        return [
            'pair_line' => $pairLine,
            'ca_label' => $caLabel !== '' ? $caLabel : (string) ($pair['ca_label'] ?? ''),
            'ma_kh' => $pair['ma_kh'],
            'ten_kh' => $tenKh,
            'ma_gv' => $pair['ma_gv'],
            'ma_gv_norm' => $gvNorm,
            'ten_gv' => $tenGv,
            'loai_filter' => $pair['loai'],
            'loai_filter_label' => LichXeLoaiGhiChu::boQuaLoaiFiltersLabel($pair['loai']),
            'min_free_phut' => (int) ($pair['min_free_phut'] ?? 0),
            'days' => $days,
        ];
    }

    /**
     * @param  array{tu: ?Carbon, den: ?Carbon}  $bounds
     */
    private static function dayWithinKhoaBounds(Carbon $day, array $bounds): bool
    {
        $d = $day->copy()->startOfDay();
        $tu = $bounds['tu'] ?? null;
        $den = $bounds['den'] ?? null;
        if ($tu !== null && $d->lt($tu)) {
            return false;
        }
        if ($den !== null && $d->gt($den)) {
            return false;
        }

        return true;
    }

    /**
     * Trong khung khóa (NgayKG–NgayBG): cặp không có buổi xe → ẩn (ngày nghỉ / xin nghỉ).
     *
     * @param  Collection<int, array<string, mixed>>  $daySlotsCombined
     * @param  array{tu: ?Carbon, den: ?Carbon}|null  $khoaBounds
     */
    private static function shouldHideNgayNghiTrongLichDay(
        Collection $daySlotsCombined,
        bool $enabled,
        Carbon $day,
        ?array $khoaBounds
    ): bool {
        if (! $enabled) {
            return false;
        }
        if ($khoaBounds !== null && ! self::dayWithinKhoaBounds($day, $khoaBounds)) {
            return false;
        }

        return $daySlotsCombined->isEmpty();
    }

    /**
     * Ngày có ít nhất một buổi xe khớp loại «bỏ qua» → không hiển thị cả ngày.
     *
     * @param  Collection<int, array<string, mixed>>  $daySlots
     * @param  list<string>  $boQuaLoai
     */

    /**
     * @param  Collection<int, array<string, mixed>>  $daySlots
     * @param  list<string>  $boQuaLoai
     */
    private static function dayHasAnyBoQuaLoai(Collection $daySlots, array $boQuaLoai): bool
    {
        if ($boQuaLoai === []) {
            return false;
        }

        foreach ($daySlots as $slot) {
            if (LichXeLoaiGhiChu::ghiChuMatchesAnyLoaiFilter(
                (string) ($slot['ghi_chu'] ?? ''),
                $boQuaLoai
            )) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<array{bd: Carbon, kt: Carbon}>  $free
     * @return list<array{bd: Carbon, kt: Carbon}>
     */
    private static function filterFreeWindowsByMinMinutes(array $free, int $minMinutes): array
    {
        if ($minMinutes <= 0) {
            return $free;
        }

        $out = [];
        foreach ($free as $iv) {
            $mins = (int) $iv['bd']->diffInMinutes($iv['kt']);
            if ($mins >= $minMinutes) {
                $out[] = $iv;
            }
        }

        return $out;
    }

    /**
     * @return array{slots: Collection<int, array<string, mixed>>, truncated: bool}
     */
    private static function loadSlots(
        Carbon $from,
        Carbon $rangeEnd,
        array $maKhList,
        array $maGvFilter,
        mixed $trangThai
    ): array {
        $rangeStart = $from->format('Y-m-d H:i:s');
        $rangeEndStr = $rangeEnd->format('Y-m-d H:i:s');

        $xeQuery = KhoaHocXeTap::query()
            ->where('IsKhoaHocXeTap', 0)
            ->whereNotNull('NgayBD')
            ->whereIn('MaKH', $maKhList)
            ->where('NgayBD', '<=', $rangeEndStr)
            ->where('NgayKT', '>=', $rangeStart);

        if ($trangThai !== '' && $trangThai !== null) {
            $xeQuery->where('TrangThai', (int) $trangThai);
        }
        if ($maGvFilter !== []) {
            $xeQuery->where(function ($q) use ($maGvFilter): void {
                foreach ($maGvFilter as $ma) {
                    $norm = DatPhanCongHocVienSaver::normalizeMaGiaoVien($ma);
                    $variants = array_values(array_unique(array_filter([$norm, ltrim($norm, '0')])));
                    $q->orWhereIn('MaGV', $variants);
                }
            });
        }
        $slots = $xeQuery
            ->orderBy('NgayBD')
            ->limit(self::MAX_SLOTS + 1)
            ->get(['MaLichSD', 'MaKH', 'MaGV', 'TenGV', 'GhiChu', 'NgayBD', 'NgayKT'])
            ->map(function (KhoaHocXeTap $row): ?array {
                $norm = DatPhanCongHocVienSaver::normalizeMaGiaoVien((string) $row->MaGV);
                if ($norm === '') {
                    return null;
                }

                return self::mapXeSlot($row, $norm);
            })
            ->filter();

        $truncated = $slots->count() > self::MAX_SLOTS;
        if ($truncated) {
            $slots = $slots->take(self::MAX_SLOTS);
        }

        return ['slots' => $slots->values(), 'truncated' => $truncated];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $slots
     * @param  list<string>  $maGvFilter
     * @return array<string, array{ma_gv: string, ten_gv: string}>
     */
    private static function buildGvMeta(Collection $slots, array $maGvFilter, bool $onlyFilteredGv): array
    {
        $meta = [];
        foreach ($slots as $slot) {
            $norm = $slot['ma_gv_norm'];
            if ($norm === '') {
                continue;
            }
            $metaKey = self::metaKeyForMaGvNorm($norm);
            if (! isset($meta[$metaKey])) {
                $meta[$metaKey] = [
                    'ma_gv_norm' => $norm,
                    'ma_gv' => $slot['ma_gv'],
                    'ten_gv' => $slot['ten_gv'] !== '' ? $slot['ten_gv'] : $norm,
                ];
            }
        }

        foreach ($maGvFilter as $ma) {
            $norm = DatPhanCongHocVienSaver::normalizeMaGiaoVien($ma);
            if ($norm === '') {
                continue;
            }
            $metaKey = self::metaKeyForMaGvNorm($norm);
            if (! isset($meta[$metaKey])) {
                $meta[$metaKey] = [
                    'ma_gv_norm' => $norm,
                    'ma_gv' => $ma,
                    'ten_gv' => $norm,
                ];
            }
        }

        if ($onlyFilteredGv) {
            $allowed = [];
            foreach ($maGvFilter as $ma) {
                $allowed[self::metaKeyForMaGvNorm(DatPhanCongHocVienSaver::normalizeMaGiaoVien($ma))] = true;
            }
            $meta = array_filter($meta, fn (array $_, string $k): bool => isset($allowed[$k]), ARRAY_FILTER_USE_BOTH);
        }

        uasort($meta, fn (array $a, array $b): int => strcasecmp($a['ten_gv'], $b['ten_gv']));

        return $meta;
    }

    private static function slotTouchesCalendarDay(string $ngayBd, string $ngayKt, string $dayKey): bool
    {
        try {
            $start = Carbon::parse($ngayBd)->toDateString();
            $end = Carbon::parse($ngayKt)->toDateString();
        } catch (\Throwable) {
            return false;
        }

        return $dayKey >= $start && $dayKey <= $end;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $daySlots
     * @return list<array{bd: Carbon, kt: Carbon}>
     */
    /**
     * @param  Collection<int, array<string, mixed>>  $daySlots
     * @return array{
     *     busy_label: string,
     *     noi_dung_label: string,
     *     loai_label: string,
     *     segments: list<array{time: string, noi_dung: string, loai: string}>
     * }
     */
    private static function busyDisplayForDay(Carbon $day, Collection $daySlots): array
    {
        $dayStart = $day->copy()->startOfDay();
        $dayEnd = $day->copy()->endOfDay();
        $segments = [];

        $sorted = $daySlots->sortBy('ngay_bd')->values();
        foreach ($sorted as $slot) {
            try {
                $bd = Carbon::parse($slot['ngay_bd']);
                $kt = Carbon::parse($slot['ngay_kt']);
            } catch (\Throwable) {
                continue;
            }
            if ($kt->lte($bd)) {
                continue;
            }
            $clipBd = $bd->greaterThan($dayStart) ? $bd->copy() : $dayStart->copy();
            $clipKt = $kt->lessThan($dayEnd) ? $kt->copy() : $dayEnd->copy();
            if ($clipKt->lte($clipBd)) {
                continue;
            }

            $segments[] = [
                'time' => $clipBd->format('H:i').'–'.$clipKt->format('H:i'),
                'nguon' => (string) ($slot['nguon'] ?? 'gv'),
                'noi_dung' => (string) ($slot['noi_dung'] ?? '—'),
                'loai' => (string) ($slot['loai_label'] ?? '—'),
            ];
        }

        $segments = self::dedupeBusySegments($segments);

        if ($segments === []) {
            return [
                'busy_label' => '—',
                'noi_dung_label' => '—',
                'loai_label' => '—',
                'segments' => [],
            ];
        }

        return [
            'busy_label' => implode('; ', array_column($segments, 'time')),
            'noi_dung_label' => implode('; ', array_map(
                fn (array $s): string => $s['noi_dung'] !== '' ? $s['noi_dung'] : '—',
                $segments
            )),
            'loai_label' => implode('; ', array_column($segments, 'loai')),
            'segments' => $segments,
        ];
    }

    /**
     * Gộp buổi trùng khung giờ (nhiều dòng lịch xe cùng slot).
     *
     * @param  list<array{time: string, nguon?: string, noi_dung: string, loai: string}>  $segments
     * @return list<array{time: string, nguon: string, noi_dung: string, loai: string}>
     */
    private static function dedupeBusySegments(array $segments): array
    {
        $byTime = [];
        foreach ($segments as $seg) {
            $key = $seg['time'];
            $nguon = (string) ($seg['nguon'] ?? 'gv');
            if (! isset($byTime[$key])) {
                $byTime[$key] = [
                    'time' => $key,
                    'nguon' => $nguon,
                    'noi_dung' => $seg['noi_dung'],
                    'loai' => $seg['loai'],
                ];

                continue;
            }

            $keep = &$byTime[$key];
            if ($nguon === 'xe' && $keep['nguon'] !== 'xe') {
                $keep['nguon'] = 'xe';
                $keep['noi_dung'] = $seg['noi_dung'];
                $keep['loai'] = $seg['loai'];

                continue;
            }

            if ($keep['noi_dung'] === '—' && $seg['noi_dung'] !== '—') {
                $keep['noi_dung'] = $seg['noi_dung'];
            }
            if ($keep['loai'] === '—' && $seg['loai'] !== '—') {
                $keep['loai'] = $seg['loai'];
            }
        }

        return array_values($byTime);
    }

    /**
     * @return array<string, mixed>
     */
    private static function mapXeSlot(KhoaHocXeTap $row, string $norm): array
    {
        $ghiChu = trim((string) ($row->GhiChu ?? ''));

        return [
            'nguon' => 'xe',
            'ma_gv_norm' => $norm,
            'ma_gv' => trim((string) $row->MaGV),
            'ten_gv' => trim((string) ($row->TenGV ?? '')),
            'ma_kh' => trim((string) $row->MaKH),
            'ngay_bd' => Carbon::parse($row->NgayBD)->format('Y-m-d H:i:s'),
            'ngay_kt' => Carbon::parse($row->NgayKT ?? $row->NgayBD)->format('Y-m-d H:i:s'),
            'ghi_chu' => $ghiChu,
            'noi_dung' => LichXeLoaiGhiChu::noiDungFromSlot($ghiChu, null, 'xe'),
            'loai_label' => LichXeLoaiGhiChu::loaiLabelFromGhiChu($ghiChu),
        ];
    }

    private static function mergedBusyForDay(Carbon $day, Collection $daySlots): array
    {
        $intervals = [];
        $dayStart = $day->copy()->startOfDay();
        $dayEnd = $day->copy()->endOfDay();

        foreach ($daySlots as $slot) {
            try {
                $bd = Carbon::parse($slot['ngay_bd']);
                $kt = Carbon::parse($slot['ngay_kt']);
            } catch (\Throwable) {
                continue;
            }
            if ($kt->lte($bd)) {
                continue;
            }
            $clipBd = $bd->greaterThan($dayStart) ? $bd->copy() : $dayStart->copy();
            $clipKt = $kt->lessThan($dayEnd) ? $kt->copy() : $dayEnd->copy();
            if ($clipKt->lte($clipBd)) {
                continue;
            }
            $intervals[] = ['bd' => $clipBd, 'kt' => $clipKt];
        }

        usort($intervals, fn (array $a, array $b): int => $a['bd']->timestamp <=> $b['bd']->timestamp);

        $merged = [];
        foreach ($intervals as $interval) {
            if ($merged === []) {
                $merged[] = $interval;

                continue;
            }
            $last = &$merged[count($merged) - 1];
            if ($interval['bd']->lte($last['kt'])) {
                if ($interval['kt']->gt($last['kt'])) {
                    $last['kt'] = $interval['kt']->copy();
                }
            } else {
                $merged[] = $interval;
            }
        }

        return $merged;
    }

    /**
     * @param  list<array{bd: Carbon, kt: Carbon}>  $busyMerged
     * @return list<array{bd: Carbon, kt: Carbon}>
     */
    private static function freeWindowsForDay(Carbon $day, string $gioBatDau, string $gioKetThuc, array $busyMerged): array
    {
        [$h0, $m0] = array_map('intval', explode(':', $gioBatDau));
        [$h1, $m1] = array_map('intval', explode(':', $gioKetThuc));

        $windowStart = $day->copy()->setTime($h0, $m0, 0);
        $windowEnd = $day->copy()->setTime($h1, $m1, 0);
        if ($windowEnd->lte($windowStart)) {
            return [];
        }

        $gap = self::GAP_AFTER_BUSY_MINUTES;
        $free = [];
        $cursor = $windowStart->copy();

        foreach ($busyMerged as $busy) {
            $busyStart = $busy['bd']->greaterThan($windowStart) ? $busy['bd']->copy() : $windowStart->copy();
            $busyEnd = $busy['kt']->lessThan($windowEnd) ? $busy['kt']->copy() : $windowEnd->copy();
            if ($busyEnd->lte($windowStart) || $busyStart->gte($windowEnd)) {
                continue;
            }

            $freeEnd = $busyStart->copy()->subMinute();
            if ($freeEnd->gte($cursor) && $freeEnd->gte($windowStart)) {
                $end = $freeEnd->greaterThan($windowEnd) ? $windowEnd->copy() : $freeEnd;
                if ($end->gte($cursor)) {
                    $free[] = ['bd' => $cursor->copy(), 'kt' => $end];
                }
            }

            $next = $busyEnd->copy()->addMinutes($gap);
            $cursor = $next->greaterThan($cursor) ? $next : $cursor->copy();
        }

        if ($cursor->lte($windowEnd)) {
            $free[] = ['bd' => $cursor->copy(), 'kt' => $windowEnd->copy()];
        }

        return self::mergeAdjacentFree($free);
    }

    /**
     * @param  list<array{bd: Carbon, kt: Carbon}>  $free
     * @return list<array{bd: Carbon, kt: Carbon}>
     */
    private static function mergeAdjacentFree(array $free): array
    {
        if ($free === []) {
            return [];
        }
        $out = [$free[0]];
        for ($i = 1, $n = count($free); $i < $n; $i++) {
            $prev = &$out[count($out) - 1];
            $cur = $free[$i];
            if ($cur['bd']->lte($prev['kt']->copy()->addMinute())) {
                if ($cur['kt']->gt($prev['kt'])) {
                    $prev['kt'] = $cur['kt']->copy();
                }
            } else {
                $out[] = $cur;
            }
        }

        return $out;
    }

    /**
     * @param  list<array{bd: Carbon, kt: Carbon}>  $intervals
     */
    private static function formatIntervals(array $intervals): string
    {
        if ($intervals === []) {
            return '—';
        }

        $parts = [];
        foreach ($intervals as $iv) {
            $parts[] = $iv['bd']->format('H:i').'–'.$iv['kt']->format('H:i');
        }

        return implode('; ', $parts);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{
     *     ma_gv_norm: string,
     *     ma_gv: string,
     *     ten_gv: string,
     *     days: list<array{ngay: string, ngay_label: string, busy_label: string, free_label: string}>
     * }>
     */
    private static function groupRowsByGiaoVien(array $rows): array
    {
        $byNorm = [];
        foreach ($rows as $row) {
            $norm = (string) ($row['ma_gv_norm'] ?? '');
            if ($norm === '') {
                continue;
            }
            if (! isset($byNorm[$norm])) {
                $byNorm[$norm] = [
                    'ma_gv_norm' => (string) $norm,
                    'ma_gv' => (string) ($row['ma_gv'] ?? ''),
                    'ten_gv' => (string) ($row['ten_gv'] ?? $norm),
                    'days' => [],
                ];
            }
            $byNorm[$norm]['days'][] = [
                'ngay' => (string) ($row['ngay'] ?? ''),
                'ngay_label' => (string) ($row['ngay_label'] ?? ''),
                'busy_label' => (string) ($row['busy_label'] ?? ''),
                'noi_dung_label' => (string) ($row['noi_dung_label'] ?? '—'),
                'loai_label' => (string) ($row['loai_label'] ?? '—'),
                'busy_segments' => $row['busy_segments'] ?? [],
                'free_label' => (string) ($row['free_label'] ?? ''),
            ];
        }

        $groups = array_values($byNorm);
        usort($groups, fn (array $a, array $b): int => strcasecmp($a['ten_gv'], $b['ten_gv']));

        return $groups;
    }

    /**
     * @param  list<string>|string  $value
     * @return list<string>
     */
    private static function normalizeStringList(mixed $value): array
    {
        if (is_string($value)) {
            $value = $value === '' ? [] : [$value];
        }
        if (! is_array($value)) {
            return [];
        }

        $out = [];
        foreach ($value as $item) {
            $item = trim((string) $item);
            if ($item !== '') {
                $out[$item] = $item;
            }
        }

        return array_values($out);
    }

    /**
     * @param  array<string, array{ma_gv: string, ten_gv: string}>  $meta
     */
    private static function enrichTenGv(array &$meta): void
    {
        if ($meta === []) {
            return;
        }

        $byNorm = [];
        GiaoVien::query()
            ->get(['MaGV', 'HoTenDem', 'TenGV'])
            ->each(function (GiaoVien $gv) use (&$byNorm): void {
                $norm = DatPhanCongHocVienSaver::normalizeMaGiaoVien((string) $gv->MaGV);
                $ten = trim(($gv->HoTenDem ?? '').' '.($gv->TenGV ?? ''));
                if ($norm !== '' && $ten !== '') {
                    $byNorm[$norm] = $ten;
                }
            });

        foreach ($meta as &$item) {
            $norm = (string) ($item['ma_gv_norm'] ?? '');
            if ($norm !== '' && isset($byNorm[$norm])) {
                $item['ten_gv'] = $byNorm[$norm];
            }
        }
        unset($item);
    }

    /**
     * @param  list<string>  $maKhList
     * @return array{
     *     from: Carbon,
     *     to: Carbon,
     *     auto_tu: bool,
     *     auto_den: bool,
     *     khoa_tu: Carbon,
     *     khoa_den: Carbon
     * }|array{error: string}
     */
    private static function resolveScanDates(array $maKhList, string $tuNgay, string $denNgay): array
    {
        $khoaStart = null;
        $khoaEnd = null;

        KhoaHoc::query()
            ->whereIn('MaKH', $maKhList)
            ->get(['NgayKG', 'NgayBG'])
            ->each(function (KhoaHoc $khoa) use (&$khoaStart, &$khoaEnd): void {
                if ($khoa->NgayKG !== null) {
                    $d = Carbon::parse($khoa->NgayKG)->startOfDay();
                    $khoaStart = $khoaStart === null || $d->lt($khoaStart) ? $d : $khoaStart;
                }
                if ($khoa->NgayBG !== null) {
                    $d = Carbon::parse($khoa->NgayBG)->startOfDay();
                    $khoaEnd = $khoaEnd === null || $d->gt($khoaEnd) ? $d : $khoaEnd;
                }
            });

        if ($khoaStart === null) {
            return ['error' => 'Khóa đã chọn không có ngày khai giảng (NgayKG).'];
        }

        if ($khoaEnd === null) {
            $khoaEnd = Carbon::today()->startOfDay();
            if ($khoaEnd->lt($khoaStart)) {
                $khoaEnd = $khoaStart->copy();
            }
        }

        try {
            $from = $tuNgay !== ''
                ? Carbon::parse($tuNgay)->startOfDay()
                : $khoaStart->copy();
            $to = $denNgay !== ''
                ? Carbon::parse($denNgay)->startOfDay()
                : $khoaEnd->copy();
        } catch (\Throwable) {
            return ['error' => 'Từ ngày / đến ngày nhập tay không hợp lệ.'];
        }

        return [
            'from' => $from,
            'to' => $to,
            'auto_tu' => $tuNgay === '',
            'auto_den' => $denNgay === '',
            'khoa_tu' => $khoaStart,
            'khoa_den' => $khoaEnd,
        ];
    }

    /**
     * @param  array{from: Carbon, to: Carbon, auto_tu: bool, auto_den: bool, khoa_tu: Carbon, khoa_den: Carbon}  $resolved
     */
    private static function rangeNotice(array $resolved): ?string
    {
        if (! $resolved['auto_tu'] && ! $resolved['auto_den']) {
            return null;
        }

        $parts = [];
        if ($resolved['auto_tu'] || $resolved['auto_den']) {
            $parts[] = 'Khóa: '.$resolved['khoa_tu']->format('d/m/Y')
                .' – '.$resolved['khoa_den']->format('d/m/Y');
        }
        $parts[] = 'Đang quét: '.$resolved['from']->format('d/m/Y')
            .' – '.$resolved['to']->format('d/m/Y');
        if ($resolved['auto_tu'] || $resolved['auto_den']) {
            $parts[] = '(từ/đến ngày trống → lấy sớm nhất / muộn nhất theo NgayKG–NgayBG các khóa)';
        }

        return implode('. ', $parts).'.';
    }

    /**
     * @param  array{from: Carbon, to: Carbon, auto_tu: bool, auto_den: bool, khoa_tu: Carbon, khoa_den: Carbon}  $resolved
     * @return array<string, mixed>
     */
    private static function resolvedRangePayload(array $resolved): array
    {
        return [
            'tu_ngay' => $resolved['from']->toDateString(),
            'den_ngay' => $resolved['to']->toDateString(),
            'auto_tu' => $resolved['auto_tu'],
            'auto_den' => $resolved['auto_den'],
            'khoa_tu' => $resolved['khoa_tu']->toDateString(),
            'khoa_den' => $resolved['khoa_den']->toDateString(),
        ];
    }

    /** Tránh PHP ép key mảng số thuần (44007026) thành int — gây lệch so sánh với chuỗi. */
    private static function metaKeyForMaGvNorm(string $norm): string
    {
        return 'gv:'.$norm;
    }

    private static function parseTime(mixed $value, string $default): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return $default;
        }
        if (preg_match('/^\d{1,2}:\d{2}$/', $value) === 1) {
            [$h, $m] = array_map('intval', explode(':', $value));

            return sprintf('%02d:%02d', min(23, max(0, $h)), min(59, max(0, $m)));
        }

        return $default;
    }

    /**
     * @return array<string, int|bool>
     */
    private static function emptyStats(): array
    {
        return [
            'slots_loaded' => 0,
            'slots_truncated' => false,
            'gv_count' => 0,
            'day_count' => 0,
            'rows_total' => 0,
            'rows_shown' => 0,
            'rows_truncated' => false,
            'pair_count' => 0,
        ];
    }
}
