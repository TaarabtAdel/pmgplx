<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\KhoaDaoTao;
use App\Models\PMGPLX\DmMonHoc;
use App\Models\PMGPLX\GiaoVien;
use App\Models\PMGPLX\KhoaHoc;
use App\Models\PMGPLX\KhoaHocGiaoVien;
use App\Models\PMGPLX\KhoaHocXeTap;
use App\Models\PMGPLX\XeTap;
use App\Support\PMGPLX\LichGvMonHoc;
use App\Support\PMGPLX\LoaiGiaoVien;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PhanCongTuLichPmgplxQuery
{
    /** @var list<string>|null */
    private static ?array $bienSoXeHangB11Cache = null;

    public static function normalizeHangGplxXe(?string $hang): string
    {
        return strtoupper(str_replace([' ', '.', '-'], '', trim((string) $hang)));
    }

    public static function isHangGplxB11(?string $hang): bool
    {
        return self::normalizeHangGplxXe($hang) === 'B11';
    }

    /**
     * Biển số xe hạng B11 (danh mục PMGPLX) — loại khỏi tổng hợp lịch xe TH.
     *
     * @return list<string>
     */
    public static function bienSoXeHangB11(): array
    {
        if (self::$bienSoXeHangB11Cache !== null) {
            return self::$bienSoXeHangB11Cache;
        }

        self::$bienSoXeHangB11Cache = XeTap::query()
            ->get(['BienSoXe', 'HangGPLXXe'])
            ->filter(fn (XeTap $xe): bool => self::isHangGplxB11($xe->HangGPLXXe ?? null))
            ->pluck('BienSoXe')
            ->map(fn ($v): string => trim((string) $v))
            ->filter(fn (string $v): bool => $v !== '')
            ->values()
            ->all();

        return self::$bienSoXeHangB11Cache;
    }

    public static function applyExcludeXeHangB11($query, string $bienSoColumn = 'BienSoXe'): void
    {
        $exclude = self::bienSoXeHangB11();
        if ($exclude !== []) {
            $query->whereNotIn($bienSoColumn, $exclude);
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, KhoaHoc>
     */
    public static function filterKhoaHocOptions()
    {
        return KhoaHoc::query()->orderBy('TenKH')->get(['MaKH', 'TenKH']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, GiaoVien>
     */
    public static function filterGiaoVienOptions()
    {
        return GiaoVien::query()->orderBy('TenGV')->orderBy('MaGV')->get(['MaGV', 'HoTenDem', 'TenGV']);
    }

    /**
     * @return \Illuminate\Support\Collection<int, string>
     */
    public static function filterBienSoXeOptions(bool $excludeHangB11 = true)
    {
        $query = XeTap::query()->orderBy('BienSoXe');

        if ($excludeHangB11) {
            self::applyExcludeXeHangB11($query);
        }

        return $query->pluck('BienSoXe');
    }

    /**
     * @param  array{
     *     ma_kh?: list<string>,
     *     ma_gv?: string,
     *     bien_so_xe?: string,
     *     loai?: string,
     * }  $filters
     * @return list<array{
     *     ma_kh: string,
     *     ten_khoa: string,
     *     ho_ten_gv: string,
     *     ma_gv: string,
     *     tu_ngay: ?Carbon,
     *     den_ngay: ?Carbon,
     *     bien_so: string,
     *     loai_giang_day: ?string,
     *     noi_dung: string,
     *     nguon: string,
     *     nguon_id: int|string,
     * }>
     */
    public function rows(array $filters): array
    {
        $loai = $filters['loai'] ?? 'tat_ca';
        if (! in_array($loai, ['tat_ca', 'ly_thuyet', 'thuc_hanh'], true)) {
            $loai = 'tat_ca';
        }

        $tenKhByMa = KhoaHoc::query()->pluck('TenKH', 'MaKH');
        $monMap = DmMonHoc::query()->pluck('TenMH', 'MaMH');

        $rows = [];

        if ($loai === 'tat_ca' || $loai === 'ly_thuyet') {
            $rows = array_merge($rows, $this->rowsFromLichGiaoVien($filters, $tenKhByMa, $monMap));
        }

        if ($loai === 'tat_ca' || $loai === 'thuc_hanh') {
            $rows = array_merge($rows, $this->rowsFromLichXeTap($filters, $tenKhByMa));
        }

        usort($rows, function (array $a, array $b): int {
            $ta = $a['tu_ngay']?->format('Y-m-d') ?? '';
            $tb = $b['tu_ngay']?->format('Y-m-d') ?? '';
            $cmp = strcmp($ta, $tb);
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcmp($a['ten_khoa'], $b['ten_khoa']);
        });

        return $rows;
    }

    /**
     * Tổng hợp theo khoá + biển số xe — GV TH dùng chung xe trong khoá (lịch xe).
     *
     * @param  array{
     *     ma_kh?: list<string>,
     *     ma_gv?: string,
     *     bien_so_xe?: string,
     * }  $filters
     * @return list<array{
     *     ma_kh: string,
     *     ten_khoa: string,
     *     bien_so: string,
     *     tu_ngay: ?Carbon,
     *     den_ngay: ?Carbon,
     *     giao_viens: list<array{ma_gv: string, ho_ten: string}>,
     * }>
     */
    public function aggregateByKhoaXe(array $filters): array
    {
        $rows = $this->rows(array_merge($filters, ['loai' => 'thuc_hanh']));

        /** @var array<string, array<string, mixed>> $groups */
        $groups = [];

        foreach ($rows as $row) {
            $maKh = trim((string) ($row['ma_kh'] ?? ''));
            $tenKhoa = trim((string) ($row['ten_khoa'] ?? ''));
            $bien = trim((string) ($row['bien_so'] ?? ''));

            if ($maKh === '' && $tenKhoa === '') {
                continue;
            }

            $key = ($maKh !== '' ? $maKh : $tenKhoa)."\0".$bien;

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'ma_kh' => $maKh,
                    'ten_khoa' => $tenKhoa !== '' ? $tenKhoa : $maKh,
                    'bien_so' => $bien,
                    'tu_ngay' => null,
                    'den_ngay' => null,
                    'giao_viens' => [],
                ];
            }

            $g = &$groups[$key];

            $tu = $row['tu_ngay'] ?? null;
            $den = $row['den_ngay'] ?? null;
            if ($tu instanceof Carbon) {
                $g['tu_ngay'] = $g['tu_ngay'] === null || $tu->lt($g['tu_ngay']) ? $tu : $g['tu_ngay'];
            }
            if ($den instanceof Carbon) {
                $g['den_ngay'] = $g['den_ngay'] === null || $den->gt($g['den_ngay']) ? $den : $g['den_ngay'];
            }

            $maGv = trim((string) ($row['ma_gv'] ?? ''));
            $hoTen = trim((string) ($row['ho_ten_gv'] ?? ''));
            if ($maGv !== '' || $hoTen !== '') {
                $gvKey = $maGv !== '' ? $maGv : mb_strtolower($hoTen);
                if (! isset($g['giao_viens'][$gvKey])) {
                    $g['giao_viens'][$gvKey] = [
                        'ma_gv' => $maGv,
                        'ho_ten' => $hoTen !== '' ? $hoTen : $maGv,
                    ];
                }
            }

            unset($g);
        }

        $result = [];
        foreach ($groups as $group) {
            $gvs = array_values($group['giao_viens']);
            usort($gvs, function (array $a, array $b): int {
                $cmp = strcasecmp($a['ho_ten'], $b['ho_ten']);
                if ($cmp !== 0) {
                    return $cmp;
                }

                return strcmp($a['ma_gv'], $b['ma_gv']);
            });

            $result[] = [
                'ma_kh' => (string) $group['ma_kh'],
                'ten_khoa' => (string) $group['ten_khoa'],
                'bien_so' => (string) $group['bien_so'],
                'tu_ngay' => $group['tu_ngay'],
                'den_ngay' => $group['den_ngay'],
                'giao_viens' => $gvs,
            ];
        }

        usort($result, function (array $a, array $b): int {
            $cmp = strcmp($a['ten_khoa'], $b['ten_khoa']);
            if ($cmp !== 0) {
                return $cmp;
            }

            $cmp = strnatcasecmp($a['bien_so'], $b['bien_so']);
            if ($cmp !== 0) {
                return $cmp;
            }

            $ta = $a['tu_ngay'] instanceof Carbon ? $a['tu_ngay']->format('Y-m-d') : '';

            return strcmp($ta, $b['tu_ngay'] instanceof Carbon ? $b['tu_ngay']->format('Y-m-d') : '');
        });

        return $result;
    }

    public static function giaoVienColumnLetter(int $index): string
    {
        if ($index < 0) {
            return '';
        }

        $label = '';
        $n = $index;
        do {
            $label = chr(65 + ($n % 26)).$label;
            $n = intdiv($n, 26) - 1;
        } while ($n >= 0);

        return $label;
    }

    /**
     * @return list<string>
     */
    public function maKhForKhoaDaoTaoId(int $khoaDaoTaoId): array
    {
        $khoa = KhoaDaoTao::query()->find($khoaDaoTaoId);
        if ($khoa === null) {
            return [];
        }

        return self::maKhCandidatesForKhoa($khoa);
    }

    /**
     * @return list<string>
     */
    public static function maKhCandidatesForKhoa(KhoaDaoTao $khoa): array
    {
        $candidates = [];
        $maKhoa = trim((string) ($khoa->MaKhoa ?? ''));
        if ($maKhoa !== '') {
            $candidates[] = $maKhoa;
        }

        $tenNorm = KhoaDaoTao::normalizeTenKhoa((string) $khoa->TenKhoa);
        if ($tenNorm !== '') {
            $candidates[] = $tenNorm;
            $fromTen = KhoaHoc::query()
                ->get(['MaKH', 'TenKH'])
                ->filter(function (KhoaHoc $kh) use ($tenNorm): bool {
                    return KhoaDaoTao::normalizeTenKhoa((string) $kh->TenKH) === $tenNorm;
                })
                ->pluck('MaKH')
                ->all();
            $candidates = array_merge($candidates, $fromTen);
        }

        return array_values(array_unique(array_filter($candidates, fn (string $v): bool => $v !== '')));
    }

    /**
     * @param  array{ma_kh?: list<string>, ma_gv?: string, bien_so_xe?: string}  $filters
     * @param  Collection<string, string>  $tenKhByMa
     * @param  Collection<int|string, string>  $monMap
     * @return list<array<string, mixed>>
     */
    private function rowsFromLichGiaoVien(array $filters, Collection $tenKhByMa, Collection $monMap): array
    {
        $query = KhoaHocGiaoVien::query()
            ->whereNotNull('NgayBD')
            ->where('LoaiGV', 'LT');

        $this->applyMaKhFilter($query, 'MaKH', $filters['ma_kh'] ?? []);

        if (($filters['ma_gv'] ?? '') !== '') {
            $query->where('MaGV', $filters['ma_gv']);
        }

        if (($filters['bien_so_xe'] ?? '') !== '') {
            $query->where('BienSoXe', $filters['bien_so_xe']);
        }

        $rows = [];
        foreach ($query->orderBy('NgayBD')->orderBy('MaLichLV')->cursor() as $row) {
            $loaiGiangDay = self::loaiGiangDayFromLoaiGv((string) ($row->LoaiGV ?? ''));
            $mon = LichGvMonHoc::displayLabel($row->TenMonHoc, $row->MaMonHoc, $monMap);
            $noiDung = trim(LoaiGiaoVien::label($row->LoaiGV));
            if ($mon !== '') {
                $noiDung = $noiDung !== '' ? $noiDung.' · '.$mon : $mon;
            }

            $maKh = trim((string) ($row->MaKH ?? ''));
            $rows[] = [
                'ma_kh' => $maKh,
                'ten_khoa' => self::tenKhoaLabel($maKh, $tenKhByMa),
                'ho_ten_gv' => trim((string) ($row->TenGV ?? '')),
                'ma_gv' => trim((string) ($row->MaGV ?? '')),
                'tu_ngay' => $row->NgayBD ? Carbon::parse($row->NgayBD) : null,
                'den_ngay' => $row->NgayKT ? Carbon::parse($row->NgayKT) : null,
                'bien_so' => trim((string) ($row->BienSoXe ?? '')),
                'loai_giang_day' => $loaiGiangDay,
                'noi_dung' => $noiDung !== '' ? $noiDung : '—',
                'nguon' => 'lich_gv',
                'nguon_id' => (int) $row->MaLichLV,
            ];
        }

        return $rows;
    }

    /**
     * @param  array{ma_kh?: list<string>, ma_gv?: string, bien_so_xe?: string}  $filters
     * @param  Collection<string, string>  $tenKhByMa
     * @return list<array<string, mixed>>
     */
    private function rowsFromLichXeTap(array $filters, Collection $tenKhByMa): array
    {
        $query = KhoaHocXeTap::query()
            ->where('IsKhoaHocXeTap', 0)
            ->whereNotNull('NgayBD');

        $this->applyMaKhFilter($query, 'MaKH', $filters['ma_kh'] ?? []);

        if (($filters['ma_gv'] ?? '') !== '') {
            $query->where('MaGV', $filters['ma_gv']);
        }

        if (($filters['bien_so_xe'] ?? '') !== '') {
            $query->where('BienSoXe', $filters['bien_so_xe']);
        }

        self::applyExcludeXeHangB11($query);

        $rows = [];
        foreach ($query->orderBy('NgayBD')->orderBy('MaLichSD')->cursor() as $row) {
            $ghiChu = trim((string) ($row->GhiChu ?? ''));
            $noiDung = 'Thực hành';
            if ($ghiChu !== '') {
                $noiDung .= ' · '.$ghiChu;
            }

            $maKh = trim((string) ($row->MaKH ?? ''));
            $rows[] = [
                'ma_kh' => $maKh,
                'ten_khoa' => self::tenKhoaLabel($maKh, $tenKhByMa),
                'ho_ten_gv' => trim((string) ($row->TenGV ?? '')),
                'ma_gv' => trim((string) ($row->MaGV ?? '')),
                'tu_ngay' => $row->NgayBD ? Carbon::parse($row->NgayBD) : null,
                'den_ngay' => $row->NgayKT ? Carbon::parse($row->NgayKT) : null,
                'bien_so' => trim((string) ($row->BienSoXe ?? '')),
                'loai_giang_day' => 'thuc_hanh',
                'noi_dung' => $noiDung,
                'nguon' => 'lich_xe',
                'nguon_id' => (int) $row->MaLichSD,
            ];
        }

        return $rows;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<KhoaHocGiaoVien>|\Illuminate\Database\Eloquent\Builder<KhoaHocXeTap>  $query
     * @param  list<string>  $maKhList
     */
    private function applyMaKhFilter($query, string $column, array $maKhList): void
    {
        if ($maKhList === []) {
            return;
        }

        $query->whereIn($column, $maKhList);
    }

    private static function tenKhoaLabel(string $maKh, Collection $tenKhByMa): string
    {
        if ($maKh === '') {
            return '—';
        }

        $ten = trim((string) ($tenKhByMa->get($maKh) ?? ''));

        return $ten !== '' ? KhoaDaoTao::normalizeTenKhoa($ten) : $maKh;
    }

    public static function loaiGiangDayFromLoaiGv(string $loaiGv): ?string
    {
        return match (strtoupper(trim($loaiGv))) {
            'LT' => 'ly_thuyet',
            'TH' => 'thuc_hanh',
            'AL' => null,
            default => null,
        };
    }
}
