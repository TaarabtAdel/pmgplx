<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\DatDieuKienDat;
use App\Models\DaoTao\DatDSPhien;
use App\Models\DaoTao\DatPhanCongHocVien;
use App\Models\PMGPLX\GiaoVien;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DatTheoDoiDat
{
    public const CUNG_DUONG_LICH_MAC_DINH = 'Các tuyến đường được quy định trên giấy phép xe tập lái';

    /**
     * @return array{
     *     ma_khoa_hoc: string,
     *     ngay: string,
     *     chi_cong_phien_dat: bool,
     *     ma_giao_vien: string,
     *     bien_so_xe: string
     * }
     */
    public static function parseFilters(Request $request): array
    {
        return [
            'ma_khoa_hoc' => trim((string) $request->input('ma_khoa_hoc', '')),
            'ngay' => self::normalizeDate((string) $request->input('ngay', '')),
            'chi_cong_phien_dat' => $request->boolean('chi_cong_phien_dat', true),
            'ma_giao_vien' => trim((string) $request->input('ma_giao_vien', '')),
            'bien_so_xe' => trim((string) $request->input('bien_so_xe', '')),
        ];
    }

    /**
     * Hạng B.01 / B01 (ví dụ K26B01K37) không dùng xe tự động — ẩn cột giờ/km tự động.
     */
    public static function anCotTuDong(string $maKhoaHoc, string $tenKhoaHoc = ''): bool
    {
        $haystack = mb_strtoupper($maKhoaHoc.' '.$tenKhoaHoc);
        $haystack = str_replace(['.', '-', '_', ' '], '', $haystack);

        return str_contains($haystack, 'B01');
    }

    /**
     * @return array{
     *     giao_vien_options: list<string>,
     *     bien_so_xe_options: list<string>,
     *     giao_vien_names: Collection<string, GiaoVien>
     * }
     */
    public static function courseFilterOptions(string $maKhoaHoc): array
    {
        if ($maKhoaHoc === '') {
            return [
                'giao_vien_options' => [],
                'bien_so_xe_options' => [],
                'giao_vien_names' => collect(),
            ];
        }

        $baseRows = DatPhanCongHocVien::query()
            ->where('MaKhoaHoc', $maKhoaHoc)
            ->get(['MaGiaoVien', 'BienSoXe']);

        $giaoVienOptions = $baseRows
            ->pluck('MaGiaoVien')
            ->map(fn ($value): string => trim((string) $value))
            ->filter(fn (string $value): bool => $value !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();

        $bienSoXeOptions = $baseRows
            ->pluck('BienSoXe')
            ->map(fn ($value): string => trim((string) $value))
            ->filter(fn (string $value): bool => $value !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();

        $giaoVienNames = self::loadGiaoVienNames($giaoVienOptions);

        return [
            'giao_vien_options' => $giaoVienOptions,
            'bien_so_xe_options' => $bienSoXeOptions,
            'giao_vien_names' => $giaoVienNames,
        ];
    }

    public static function hasPhanCong(string $maKhoaHoc): bool
    {
        if ($maKhoaHoc === '') {
            return false;
        }

        return DatPhanCongHocVien::query()
            ->where('MaKhoaHoc', $maKhoaHoc)
            ->exists();
    }

    /**
     * @return list<array{
     *     ma_giao_vien: string,
     *     ho_ten_giao_vien: string,
     *     bien_so_xe: string,
     *     tong_km_ngay: string,
     *     cung_duong_lich: string,
     *     cung_duong_gv: string,
     *     gio_gvth: string,
     *     km_gvth: string,
     *     students: list<array{
     *         stt: int,
     *         ma_hoc_vien: string,
     *         ho_ten: string,
     *         gio_tu_dong: string,
     *         km_may_chu: string,
     *         chay_dem: string,
     *         km_dem: string,
     *         cao_toc: string,
     *         gio_may_chu: string,
     *         tong_km_may_chu: string,
     *         gio_trong_ngay: string,
     *         km_trong_ngay: string,
     *         ngoai_phan_cong: bool
     *     }>
     * }>
     */
    public static function buildGroups(array $filters): array
    {
        $maKhoaHoc = $filters['ma_khoa_hoc'] ?? '';
        $chiCongPhienDat = (bool) ($filters['chi_cong_phien_dat'] ?? true);
        $ngay = self::normalizeDate((string) ($filters['ngay'] ?? ''));
        $maGiaoVienFilter = trim((string) ($filters['ma_giao_vien'] ?? ''));
        $bienSoXeFilter = trim((string) ($filters['bien_so_xe'] ?? ''));

        if ($maKhoaHoc === '') {
            return [];
        }

        $assignmentQuery = DatPhanCongHocVien::query()
            ->where('MaKhoaHoc', $maKhoaHoc);

        if ($maGiaoVienFilter !== '') {
            $assignmentQuery->where('MaGiaoVien', $maGiaoVienFilter);
        }

        if ($bienSoXeFilter !== '') {
            $assignmentQuery->where('BienSoXe', $bienSoXeFilter);
        }

        $assignments = $assignmentQuery
            ->orderBy('BienSoXe')
            ->orderBy('MaGiaoVien')
            ->orderBy('HoTenHocVien')
            ->orderBy('MaHocVien')
            ->get();

        if ($assignments->isEmpty()) {
            return [];
        }

        $sessions = self::loadCourseSessions($maKhoaHoc, $chiCongPhienDat);
        $scheduleRows = DatPhienLichXeMatcher::scheduleForCourse($maKhoaHoc);
        $loaiKhoaHoc = (string) $sessions
            ->pluck('LoaiKhoaHoc')
            ->map(fn ($value): string => trim((string) $value))
            ->filter()
            ->first();
        $tenKhoaHoc = (string) $sessions
            ->pluck('TenKhoaHoc')
            ->map(fn ($value): string => trim((string) $value))
            ->filter()
            ->first();
        $dieuKien = DatDieuKienDat::forHang(
            DatDieuKienDat::hangFromCourse($maKhoaHoc, $tenKhoaHoc, $loaiKhoaHoc)
        );

        /** @var Collection<string, Collection<int, DatDSPhien>> $sessionsByMaHocVien */
        $sessionsByMaHocVien = $sessions->groupBy(
            fn (DatDSPhien $session): string => trim((string) ($session->MaHocVien ?? ''))
        );

        /** @var array<string, array{gio: float, km: float}> $studentDayTotals */
        $studentDayTotals = [];
        if ($ngay !== '') {
            $daySessions = self::sessionsOnDate($sessions, $ngay);
            foreach ($daySessions->groupBy('MaHocVien') as $maHocVien => $studentSessions) {
                $maHocVien = (string) $maHocVien;
                $studentDayTotals[$maHocVien] = [
                    'gio' => self::sumThucHanhGio($studentSessions),
                    'km' => self::sumQuangDuongKm($studentSessions),
                ];
            }
        }

        $giaoVienNames = self::loadGiaoVienNames(
            $assignments->pluck('MaGiaoVien')
                ->map(fn ($value): string => trim((string) $value))
                ->filter()
                ->unique()
                ->values()
                ->all()
        );

        $substitutesByKey = DatPhanCongGiaoVienThayResolver::groupedForCourses([$maKhoaHoc]);

        $extraGv = [];
        foreach ($substitutesByKey as $substitutes) {
            foreach ($substitutes as $substitute) {
                $maGv = trim((string) ($substitute['ma_giao_vien'] ?? ''));
                if ($maGv !== '') {
                    $extraGv[$maGv] = true;
                }
            }
        }
        if ($extraGv !== []) {
            $giaoVienNames = $giaoVienNames->union(self::loadGiaoVienNames(array_keys($extraGv)));
        }

        /** @var Collection<string, Collection<int, DatPhanCongHocVien>> $byGroup */
        $byGroup = $assignments->groupBy(
            fn (DatPhanCongHocVien $assignment): string => self::assignmentGroupKey(
                (string) ($assignment->MaGiaoVien ?? ''),
                (string) ($assignment->BienSoXe ?? '')
            )
        );

        /** @var array<string, DatPhanCongHocVien> $assignmentByMaHocVien */
        $assignmentByMaHocVien = [];
        foreach ($assignments as $assignment) {
            $maHocVien = trim((string) ($assignment->MaHocVien ?? ''));
            if ($maHocVien !== '') {
                $assignmentByMaHocVien[$maHocVien] = $assignment;
            }
        }

        /** @var array<string, list<DatDSPhien>> $sessionsByGroupKey */
        $sessionsByGroupKey = [];
        foreach ($sessions as $session) {
            $maHocVien = trim((string) ($session->MaHocVien ?? ''));
            if ($maHocVien !== '' && isset($assignmentByMaHocVien[$maHocVien])) {
                $assignment = $assignmentByMaHocVien[$maHocVien];
                $groupKey = self::assignmentGroupKey(
                    (string) ($assignment->MaGiaoVien ?? ''),
                    (string) ($assignment->BienSoXe ?? '')
                );
                if (self::sessionBelongsToAssignmentGroup(
                    $session,
                    $assignment,
                    DatPhanCongGiaoVienThayResolver::substitutesForAssignment($assignment, $substitutesByKey)
                )) {
                    $sessionsByGroupKey[$groupKey][] = $session;
                }

                continue;
            }

            $groupKey = self::sessionGroupKey($session);
            $sessionsByGroupKey[$groupKey][] = $session;
        }

        $groups = [];

        foreach ($byGroup as $groupAssignments) {
            $first = $groupAssignments->first();
            if ($first === null) {
                continue;
            }

            $maGiaoVien = trim((string) ($first->MaGiaoVien ?? ''));
            $bienSoXe = trim((string) ($first->BienSoXe ?? ''));
            $groupKey = self::assignmentGroupKey($maGiaoVien, $bienSoXe);

            $assignedMaHocVien = [];
            foreach ($groupAssignments as $assignment) {
                $maHocVien = trim((string) ($assignment->MaHocVien ?? ''));
                if ($maHocVien !== '') {
                    $assignedMaHocVien[$maHocVien] = true;
                }
            }

            $groupSessions = collect($sessionsByGroupKey[$groupKey] ?? []);

            $groupGio = self::sumThucHanhGio($groupSessions);
            $groupKm = self::sumQuangDuongKm($groupSessions);
            $groupDayKm = 0.0;

            if ($ngay !== '') {
                $groupDayKm = self::sumQuangDuongKm(self::sessionsOnDate($groupSessions, $ngay));
            }

            $students = [];
            $stt = 0;

            foreach ($groupAssignments as $assignment) {
                $maHocVien = trim((string) ($assignment->MaHocVien ?? ''));
                if ($maHocVien === '') {
                    continue;
                }

                $stt++;
                $studentSessions = $sessionsByMaHocVien->get($maHocVien, collect());

                $students[] = self::buildStudentRow(
                    $maHocVien,
                    trim((string) ($assignment->HoTenHocVien ?? '')) ?: $maHocVien,
                    $studentSessions,
                    $studentDayTotals,
                    $ngay,
                    $stt,
                    false,
                    $scheduleRows,
                    $dieuKien
                );
            }

            foreach ($groupSessions->groupBy(
                fn (DatDSPhien $session): string => trim((string) ($session->MaHocVien ?? ''))
            ) as $maHocVien => $studentSessionsInGroup) {
                $maHocVien = (string) $maHocVien;
                if ($maHocVien === '' || isset($assignedMaHocVien[$maHocVien])) {
                    continue;
                }

                $stt++;
                $studentSessions = $sessionsByMaHocVien->get($maHocVien, collect());
                $sessionSample = $studentSessionsInGroup->first();
                $hoTen = trim((string) ($sessionSample->HoTenHocVien ?? ''));
                if ($hoTen === '' && $studentSessions->isNotEmpty()) {
                    $hoTen = trim((string) ($studentSessions->first()->HoTenHocVien ?? ''));
                }

                $students[] = self::buildStudentRow(
                    $maHocVien,
                    $hoTen !== '' ? $hoTen : $maHocVien,
                    $studentSessions,
                    $studentDayTotals,
                    $ngay,
                    $stt,
                    true,
                    $scheduleRows,
                    $dieuKien
                );
            }

            if ($students === []) {
                continue;
            }

            $groups[] = [
                'ma_giao_vien' => $maGiaoVien,
                'ho_ten_giao_vien' => self::formatGiaoVienTen($giaoVienNames, $maGiaoVien),
                'bien_so_xe' => $bienSoXe !== '' ? $bienSoXe : self::placeholder(),
                'tong_km_ngay' => $ngay !== '' ? self::formatKm($groupDayKm) : self::placeholder(),
                'cung_duong_lich' => self::CUNG_DUONG_LICH_MAC_DINH,
                'cung_duong_gv' => self::placeholder(),
                'gio_gvth' => self::formatGio($groupGio),
                'km_gvth' => self::formatKm($groupKm),
                'students' => $students,
            ];
        }

        usort($groups, static function (array $a, array $b): int {
            $xeA = DatPhanCongHocVienSaver::normalizeBienSo((string) ($a['bien_so_xe'] ?? ''));
            $xeB = DatPhanCongHocVienSaver::normalizeBienSo((string) ($b['bien_so_xe'] ?? ''));
            if ($xeA === self::placeholder()) {
                $xeA = '';
            }
            if ($xeB === self::placeholder()) {
                $xeB = '';
            }
            if ($xeA === '' && $xeB !== '') {
                return 1;
            }
            if ($xeB === '' && $xeA !== '') {
                return -1;
            }

            $cmp = strnatcasecmp($xeA, $xeB);
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcasecmp((string) ($a['ma_giao_vien'] ?? ''), (string) ($b['ma_giao_vien'] ?? ''));
        });

        return $groups;
    }

    public static function formatNgayHeading(string $ngay): string
    {
        if ($ngay === '') {
            return '—';
        }

        return Carbon::parse($ngay)->format('d/m/y');
    }

    public static function formatGio(float|int|string|null $hours): string
    {
        if ($hours === null || $hours === '' || (float) $hours <= 0) {
            return self::placeholder();
        }

        $totalMinutes = (int) round((float) $hours * 60);
        $h = intdiv($totalMinutes, 60);
        $m = $totalMinutes % 60;

        if ($h === 0) {
            return '0h'.$m;
        }

        if ($m === 0) {
            return $h.'h';
        }

        return $h.'h'.$m;
    }

    public static function formatKm(float|int|string|null $km): string
    {
        if ($km === null || $km === '' || (float) $km <= 0) {
            return self::placeholder();
        }

        return rtrim(rtrim(number_format((float) $km, 2, ',', ''), '0'), ',');
    }

    public static function placeholder(): string
    {
        return '—';
    }

    /**
     * Tổng giờ / KM máy chủ theo mã học viên (cùng quy tắc Theo dõi DAT, mặc định chỉ phiên đạt).
     *
     * @return array<string, array{gio: float, km: float}>
     */
    public static function studentServerTotalsByMaHocVien(string $maKhoaHoc, bool $chiCongPhienDat = true): array
    {
        $maKhoaHoc = trim($maKhoaHoc);
        if ($maKhoaHoc === '') {
            return [];
        }

        $sessions = self::loadCourseSessions($maKhoaHoc, $chiCongPhienDat);
        $totals = [];

        foreach ($sessions->groupBy(
            fn (DatDSPhien $session): string => trim((string) ($session->MaHocVien ?? ''))
        ) as $maHocVien => $studentSessions) {
            $maHocVien = trim((string) $maHocVien);
            if ($maHocVien === '') {
                continue;
            }

            $totals[$maHocVien] = [
                'gio' => self::sumThucHanhGio($studentSessions),
                'km' => self::sumQuangDuongKm($studentSessions),
            ];
        }

        return $totals;
    }

    /**
     * @return Collection<int, DatDSPhien>
     */
    private static function loadCourseSessions(string $maKhoaHoc, bool $chiCongPhienDat): Collection
    {
        $sessions = DatDSPhien::query()
            ->where('MaKhoaHoc', $maKhoaHoc)
            ->orderBy('ThoiGianBatDauPhienHoc')
            ->get([
                'Id',
                'MaHocVien',
                'HoTenHocVien',
                'MaGiaoVien',
                'BienSoXe',
                'MaKhoaHoc',
                'TenKhoaHoc',
                'LoaiKhoaHoc',
                'ThoiGianBatDauPhienHoc',
                'ThoiGianKetThucPhienHoc',
                'ThoiGianThucHanhGio',
                'QuangDuongThucHanhKm',
                'LaBanDem',
                'LaTuDong',
                'TiLeNhanDien',
            ]);

        if ($sessions->isEmpty() || ! $chiCongPhienDat) {
            return $sessions;
        }

        $violationsById = DatDSPhienKiemTra::analyze($sessions);

        return $sessions->filter(
            fn (DatDSPhien $session): bool => DatDSPhienKiemTra::datPhien($violationsById, (int) $session->Id)
        )->values();
    }

    /**
     * @param  list<string>  $maGvCodes
     * @return Collection<string, GiaoVien>
     */
    private static function loadGiaoVienNames(array $maGvCodes): Collection
    {
        if ($maGvCodes === []) {
            return collect();
        }

        return GiaoVien::query()
            ->whereIn('MaGV', $maGvCodes)
            ->get(['MaGV', 'HoTenDem', 'TenGV'])
            ->keyBy('MaGV');
    }

    /**
     * @param  Collection<string, GiaoVien>  $giaoVienNames
     */
    private static function formatGiaoVienTen(Collection $giaoVienNames, string $maGiaoVien): string
    {
        if ($maGiaoVien === '') {
            return self::placeholder();
        }

        $gv = $giaoVienNames->get($maGiaoVien);
        if ($gv === null) {
            return $maGiaoVien;
        }

        $ten = trim(trim((string) ($gv->HoTenDem ?? '')).' '.trim((string) ($gv->TenGV ?? '')));

        return $ten !== '' ? $ten : $maGiaoVien;
    }

    /**
     * @param  Collection<int, DatDSPhien>  $sessions
     * @return Collection<int, DatDSPhien>
     */
    private static function sessionsOnDate(Collection $sessions, string $ngay): Collection
    {
        return $sessions->filter(
            fn (DatDSPhien $session): bool => self::sessionDate($session) === $ngay
        )->values();
    }

    private static function sessionDate(DatDSPhien $session): ?string
    {
        $value = $session->ThoiGianBatDauPhienHoc;

        if ($value === null || $value === '') {
            return null;
        }

        try {
            return ($value instanceof Carbon ? $value : Carbon::parse($value))->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private static function assignmentGroupKey(string $maGiaoVien, string $bienSoXe): string
    {
        return DatPhanCongHocVienSaver::normalizeMaGiaoVien($maGiaoVien)
            .'|'
            .DatPhanCongHocVienSaver::normalizeBienSo($bienSoXe);
    }

    private static function sessionGroupKey(DatDSPhien $session): string
    {
        return self::assignmentGroupKey(
            (string) ($session->MaGiaoVien ?? ''),
            (string) ($session->BienSoXe ?? '')
        );
    }

    /**
     * @param  list<array{id?: int, ma_giao_vien: string, tu_ngay: string, den_ngay: string|null}>  $substitutes
     */
    private static function sessionBelongsToAssignmentGroup(
        DatDSPhien $session,
        DatPhanCongHocVien $assignment,
        array $substitutes
    ): bool {
        if (! self::sessionXeMatchesAssignment($session, $assignment)) {
            return false;
        }

        $expectedGv = DatPhanCongGiaoVienThayResolver::resolveForDate(
            $assignment,
            self::sessionDate($session),
            $substitutes
        );
        $sessionGv = DatPhanCongHocVienSaver::normalizeMaGiaoVien((string) ($session->MaGiaoVien ?? ''));

        return $sessionGv === $expectedGv['ma_giao_vien'];
    }

    private static function sessionXeMatchesAssignment(DatDSPhien $session, DatPhanCongHocVien $assignment): bool
    {
        $sessionXe = DatPhanCongHocVienSaver::normalizeBienSo((string) ($session->BienSoXe ?? ''));
        if ($sessionXe === '') {
            return true;
        }

        $allowed = array_values(array_filter([
            DatPhanCongHocVienSaver::normalizeBienSo((string) ($assignment->BienSoXe ?? '')),
            DatPhanCongHocVienSaver::normalizeBienSo((string) ($assignment->BienSoXeTuDong ?? '')),
        ], static fn (string $value): bool => $value !== ''));

        if ($allowed === []) {
            return true;
        }

        return in_array($sessionXe, $allowed, true);
    }

    /**
     * @param  array<string, array{gio: float, km: float}>  $studentDayTotals
     * @param  Collection<int, DatDSPhien>  $studentSessions
     * @return array{
     *     stt: int,
     *     ma_hoc_vien: string,
     *     ho_ten: string,
     *     gio_tu_dong: string,
     *     km_may_chu: string,
     *     chay_dem: string,
     *     km_dem: string,
     *     cao_toc: string,
     *     gio_may_chu: string,
     *     tong_km_may_chu: string,
     *     gio_trong_ngay: string,
     *     km_trong_ngay: string,
     *     ngoai_phan_cong: bool
     * }
     */
    private static function buildStudentRow(
        string $maHocVien,
        string $hoTen,
        Collection $studentSessions,
        array $studentDayTotals,
        string $ngay,
        int $stt,
        bool $ngoaiPhanCong,
        Collection $scheduleRows,
        ?DatDieuKienDat $dieuKien
    ): array {
        $dayTotals = $studentDayTotals[$maHocVien] ?? ['gio' => 0.0, 'km' => 0.0];
        $gioTuDong = self::sumThucHanhGio($studentSessions, 'LaTuDong');
        $kmTuDong = self::sumQuangDuongKm($studentSessions, 'LaTuDong');
        $nightSessions = self::sessionsMatchingGhiChu(
            $studentSessions,
            $scheduleRows,
            ['ban đêm', 'ban dem'],
            'LaBanDem'
        );
        $chayDem = self::sumThucHanhGio($nightSessions);
        $kmDem = self::sumQuangDuongKm($nightSessions);
        $caoTocSessions = self::sessionsMatchingGhiChu($studentSessions, $scheduleRows, ['cao tốc', 'cao toc']);
        $gioCaoToc = self::sumThucHanhGio($caoTocSessions);
        $gioMayChu = self::sumThucHanhGio($studentSessions);
        $tongKmMayChu = self::sumQuangDuongKm($studentSessions);

        return [
            'stt' => $stt,
            'ma_hoc_vien' => $maHocVien,
            'ho_ten' => $hoTen,
            'gio_tu_dong' => self::formatGio($gioTuDong),
            'km_may_chu' => self::formatKm($kmTuDong),
            'chay_dem' => self::formatGio($chayDem),
            'km_dem' => self::formatKm($kmDem),
            'cao_toc' => self::formatGio($gioCaoToc),
            'gio_may_chu' => self::formatGio($gioMayChu),
            'tong_km_may_chu' => self::formatKm($tongKmMayChu),
            'gio_trong_ngay' => $ngay !== '' ? self::formatGio($dayTotals['gio']) : self::placeholder(),
            'km_trong_ngay' => $ngay !== '' ? self::formatKm($dayTotals['km']) : self::placeholder(),
            'ngoai_phan_cong' => $ngoaiPhanCong,
            'dat_gio_tu_dong' => DatDieuKienDat::datNguong($gioTuDong, $dieuKien?->XeSoTuDongGio),
            'dat_gio_dem' => DatDieuKienDat::datNguong($chayDem, $dieuKien?->TapLaiBanDemGio),
            'dat_cao_toc' => DatDieuKienDat::datNguong($gioCaoToc, $dieuKien?->GioCaoTocGio),
            'dat_gio_may_chu' => DatDieuKienDat::datNguong($gioMayChu, $dieuKien?->SoGioHoc),
            'dat_tong_km_may_chu' => DatDieuKienDat::datNguong($tongKmMayChu, $dieuKien?->TongQuangDuongKm),
        ];
    }

    /**
     * @param  Collection<int, DatDSPhien>  $sessions
     * @param  Collection<int, \App\Models\PMGPLX\KhoaHocXeTap>  $scheduleRows
     * @param  list<string>  $needles
     * @return Collection<int, DatDSPhien>
     */
    private static function sessionsMatchingGhiChu(
        Collection $sessions,
        Collection $scheduleRows,
        array $needles,
        ?string $flagField = null
    ): Collection {
        return $sessions->filter(function (DatDSPhien $session) use ($scheduleRows, $needles, $flagField): bool {
            return DatPhienLichXeMatcher::matchesGhiChu($session, $scheduleRows, $needles, $flagField);
        })->values();
    }

    /**
     * @param  Collection<int, DatDSPhien>  $sessions
     */
    private static function sumThucHanhGio(Collection $sessions, ?string $flagField = null): float
    {
        return (float) $sessions->sum(function (DatDSPhien $session) use ($flagField): float {
            if ($flagField !== null && ! (bool) ($session->{$flagField} ?? false)) {
                return 0.0;
            }

            return (float) ($session->ThoiGianThucHanhGio ?? 0);
        });
    }

    /**
     * @param  Collection<int, DatDSPhien>  $sessions
     */
    private static function sumQuangDuongKm(Collection $sessions, ?string $flagField = null): float
    {
        return (float) $sessions->sum(function (DatDSPhien $session) use ($flagField): float {
            if ($flagField !== null && ! (bool) ($session->{$flagField} ?? false)) {
                return 0.0;
            }

            return (float) ($session->QuangDuongThucHanhKm ?? 0);
        });
    }

    private static function normalizeDate(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return '';
        }
    }
}
