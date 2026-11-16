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
    /** Khóa tra TenKH — MaKH lịch xe đôi khi khác hoa/thường so với bảng KhoaHoc. */
    public static function maKhLookupKey(string $maKh): string
    {
        return strtoupper(trim($maKh));
    }

    /**
     * @return Collection<string, string> map MaKH (uppercase) → TenKH
     */
    public static function tenKhByMaKhCollection(): Collection
    {
        return KhoaHoc::query()
            ->get(['MaKH', 'TenKH'])
            ->mapWithKeys(fn (KhoaHoc $kh): array => [
                self::maKhLookupKey((string) $kh->MaKH) => trim((string) $kh->TenKH),
            ]);
    }

    /**
     * Tạm thời: khoá tự động khi tên khoá (TenKH PMGPLX) có chuỗi B01.
     */
    public static function isKhoaHocTuDong(string $tenKh): bool
    {
        $tenKh = trim($tenKh);
        if ($tenKh === '') {
            return false;
        }

        return str_contains(mb_strtoupper($tenKh), 'B01');
    }

    /** Xe số tự động theo danh mục PMGPLX (HangGPLXXe có B11). */
    public static function isXeTapTuDong(?string $bienSo): bool
    {
        $normalized = DatXeSoTuDong::normalizeBienSo($bienSo);
        if ($normalized === '') {
            return false;
        }

        return in_array($normalized, DatXeSoTuDong::bienSoTuDong(), true);
    }

    /**
     * Khoá không phải B01: bỏ dòng lịch gắn xe tự động (vẫn hiện xe sàn).
     */
    public static function shouldIncludeLichXeTapRow(string $tenKh, ?string $bienSo): bool
    {
        if (self::isKhoaHocTuDong($tenKh)) {
            return true;
        }

        return ! self::isXeTapTuDong($bienSo);
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
    public static function filterBienSoXeOptions()
    {
        return XeTap::query()->orderBy('BienSoXe')->pluck('BienSoXe');
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

        $tenKhByMa = self::tenKhByMaKhCollection();
        $monMap = DmMonHoc::query()->pluck('TenMH', 'MaMH');

        $rows = [];

        if ($loai === 'tat_ca' || $loai === 'ly_thuyet') {
            $rows = array_merge($rows, $this->rowsFromLichGiaoVien($filters, $tenKhByMa, $monMap));
        }

        if ($loai === 'tat_ca' || $loai === 'thuc_hanh') {
            $rows = array_merge($rows, $this->rowsFromLichXeTap($filters, $tenKhByMa, applyXeTuDongFilter: false));
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
     * Tổng hợp theo từ ngày → đến ngày của lịch dạy (NgayBD–NgayKT), không lấy TG khoá.
     *
     * Có lọc ma_gv: 1 dòng / (khoá · xe) với khoảng BD–KT của GV đó;
     * GV B = thầy khác chung xe có lịch chồng khoảng đó.
     *
     * Không lọc: mỗi GV trên xe tạo khoảng từ–đến riêng; thầy chung khoảng → cùng dòng (khử trùng).
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
        $tenKhByMa = self::tenKhByMaKhCollection();
        $filterMaGv = trim((string) ($filters['ma_gv'] ?? ''));
        $anchorNorm = $filterMaGv !== ''
            ? DatPhanCongHocVienSaver::normalizeMaGiaoVien($filterMaGv)
            : '';

        // Toàn bộ lịch xe (mọi GV) trong phạm vi lọc khoá/xe — để tìm người chung xe.
        $filtersAllGv = $filters;
        unset($filtersAllGv['ma_gv']);
        $allRows = $this->rowsFromLichXeTap($filtersAllGv, $tenKhByMa, applyXeTuDongFilter: true);

        /** @var array<string, array{ma_kh: string, ten_khoa: string, bien_so: string, by_gv: array<string, array{ma_gv: string, ho_ten: string, tu: Carbon, den: Carbon}>}> $byKhoaXe */
        $byKhoaXe = [];

        foreach ($allRows as $row) {
            $maKh = trim((string) ($row['ma_kh'] ?? ''));
            $tenKhoa = trim((string) ($row['ten_khoa'] ?? ''));
            $bien = trim((string) ($row['bien_so'] ?? ''));
            $maGv = trim((string) ($row['ma_gv'] ?? ''));
            $hoTen = trim((string) ($row['ho_ten_gv'] ?? ''));
            $tu = $row['tu_ngay'] ?? null;
            $den = $row['den_ngay'] ?? null;

            if (($maKh === '' && $tenKhoa === '') || ($maGv === '' && $hoTen === '') || ! $tu instanceof Carbon) {
                continue;
            }
            if (! $den instanceof Carbon) {
                $den = $tu->copy();
            }
            if ($den->lt($tu)) {
                $den = $tu->copy();
            }

            $xeKey = ($maKh !== '' ? $maKh : $tenKhoa)."\0".$bien;
            $gvKey = $maGv !== '' ? DatPhanCongHocVienSaver::normalizeMaGiaoVien($maGv) : mb_strtolower($hoTen);

            if (! isset($byKhoaXe[$xeKey])) {
                $byKhoaXe[$xeKey] = [
                    'ma_kh' => $maKh,
                    'ten_khoa' => $tenKhoa !== '' ? $tenKhoa : $maKh,
                    'bien_so' => $bien,
                    'by_gv' => [],
                ];
            }

            if (! isset($byKhoaXe[$xeKey]['by_gv'][$gvKey])) {
                $byKhoaXe[$xeKey]['by_gv'][$gvKey] = [
                    'ma_gv' => $maGv,
                    'ho_ten' => $hoTen !== '' ? $hoTen : $maGv,
                    'tu' => $tu->copy(),
                    'den' => $den->copy(),
                ];
            } else {
                $slot = &$byKhoaXe[$xeKey]['by_gv'][$gvKey];
                if ($tu->lt($slot['tu'])) {
                    $slot['tu'] = $tu->copy();
                }
                if ($den->gt($slot['den'])) {
                    $slot['den'] = $den->copy();
                }
                if (($slot['ho_ten'] ?? '') === '' && $hoTen !== '') {
                    $slot['ho_ten'] = $hoTen;
                }
                unset($slot);
            }
        }

        $result = [];
        $seen = [];

        foreach ($byKhoaXe as $bucket) {
            $anchors = $bucket['by_gv'];
            if ($anchorNorm !== '') {
                if (! isset($anchors[$anchorNorm])) {
                    continue;
                }
                $anchors = [$anchorNorm => $anchors[$anchorNorm]];
            }

            foreach ($anchors as $anchor) {
                $tu = $anchor['tu'];
                $den = $anchor['den'];

                $gvs = [];
                foreach ($bucket['by_gv'] as $gv) {
                    // Chung xe trong khoảng từ–đến của GV neo: lịch chồng [tu, den].
                    if ($gv['den']->lt($tu) || $gv['tu']->gt($den)) {
                        continue;
                    }
                    $gvs[] = [
                        'ma_gv' => $gv['ma_gv'],
                        'ho_ten' => $gv['ho_ten'],
                    ];
                }

                $ordered = self::orderGiaoViensAbForCapXe(
                    $gvs,
                    $anchorNorm !== '' ? $anchorNorm : DatPhanCongHocVienSaver::normalizeMaGiaoVien($anchor['ma_gv'])
                );

                // Khử trùng: cùng khoá·xe·từ–đến·cùng tập GV chỉ 1 dòng.
                $fpParts = [];
                foreach ($ordered as $gv) {
                    $fpParts[] = ($gv['ma_gv'] ?? '') !== ''
                        ? DatPhanCongHocVienSaver::normalizeMaGiaoVien((string) $gv['ma_gv'])
                        : mb_strtolower((string) ($gv['ho_ten'] ?? ''));
                }
                sort($fpParts);
                $fp = $bucket['ma_kh']."\0".$bucket['bien_so']."\0"
                    .$tu->format('Y-m-d H:i:s')."\0".$den->format('Y-m-d H:i:s')."\0"
                    .implode('|', $fpParts);
                if (isset($seen[$fp])) {
                    continue;
                }
                $seen[$fp] = true;

                $result[] = [
                    'ma_kh' => (string) $bucket['ma_kh'],
                    'ten_khoa' => (string) $bucket['ten_khoa'],
                    'bien_so' => (string) $bucket['bien_so'],
                    'tu_ngay' => $tu->copy(),
                    'den_ngay' => $den->copy(),
                    'giao_viens' => $ordered,
                ];
            }
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
            $ta = $a['tu_ngay'] instanceof Carbon ? $a['tu_ngay']->format('Y-m-d H:i:s') : '';

            return strcmp($ta, $b['tu_ngay'] instanceof Carbon ? $b['tu_ngay']->format('Y-m-d H:i:s') : '');
        });

        return $result;
    }

    /**
     * Cột A = GV neo (khi lọc ma_gv); cột B = GV còn lại cùng xe; không lọc → sắp theo tên.
     *
     * @param  list<array{ma_gv: string, ho_ten: string}>  $gvs
     * @return list<array{ma_gv: string, ho_ten: string}>
     */
    private static function orderGiaoViensAbForCapXe(array $gvs, ?string $anchorMaGvNorm): array
    {
        $sortByName = static function (array $a, array $b): int {
            $cmp = strcasecmp($a['ho_ten'], $b['ho_ten']);
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcmp($a['ma_gv'], $b['ma_gv']);
        };

        if ($anchorMaGvNorm === null || $anchorMaGvNorm === '') {
            usort($gvs, $sortByName);

            return $gvs;
        }

        $anchor = null;
        $others = [];
        foreach ($gvs as $gv) {
            $norm = DatPhanCongHocVienSaver::normalizeMaGiaoVien((string) ($gv['ma_gv'] ?? ''));
            if ($norm === $anchorMaGvNorm) {
                $anchor = $gv;
            } else {
                $others[] = $gv;
            }
        }

        usort($others, $sortByName);

        if ($anchor !== null) {
            return array_merge([$anchor], $others);
        }

        usort($gvs, $sortByName);

        return $gvs;
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

            $maKhRaw = trim((string) ($row->MaKH ?? ''));
            $maKhKey = self::maKhLookupKey($maKhRaw);
            $rows[] = [
                'ma_kh' => $maKhKey !== '' ? $maKhKey : $maKhRaw,
                'ten_khoa' => self::tenKhoaLabel($maKhKey !== '' ? $maKhKey : $maKhRaw, $tenKhByMa),
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
    private function rowsFromLichXeTap(array $filters, Collection $tenKhByMa, bool $applyXeTuDongFilter = false): array
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

        $rows = [];
        foreach ($query->orderBy('NgayBD')->orderBy('MaLichSD')->cursor() as $row) {
            $ghiChu = trim((string) ($row->GhiChu ?? ''));
            $noiDung = 'Thực hành';
            if ($ghiChu !== '') {
                $noiDung .= ' · '.$ghiChu;
            }

            $maKhRaw = trim((string) ($row->MaKH ?? ''));
            $maKhKey = self::maKhLookupKey($maKhRaw);
            $tenKhRaw = trim((string) ($tenKhByMa->get($maKhKey) ?? ''));
            $bienSo = trim((string) ($row->BienSoXe ?? ''));

            if ($applyXeTuDongFilter && ! self::shouldIncludeLichXeTapRow($tenKhRaw, $bienSo)) {
                continue;
            }

            $rows[] = [
                'ma_kh' => $maKhKey !== '' ? $maKhKey : $maKhRaw,
                'ten_khoa' => self::tenKhoaLabel($maKhKey !== '' ? $maKhKey : $maKhRaw, $tenKhByMa),
                'ho_ten_gv' => trim((string) ($row->TenGV ?? '')),
                'ma_gv' => trim((string) ($row->MaGV ?? '')),
                'tu_ngay' => $row->NgayBD ? Carbon::parse($row->NgayBD) : null,
                'den_ngay' => $row->NgayKT ? Carbon::parse($row->NgayKT) : null,
                'bien_so' => $bienSo,
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

        $ten = trim((string) ($tenKhByMa->get(self::maKhLookupKey($maKh)) ?? ''));

        return $ten !== '' ? KhoaDaoTao::normalizeTenKhoa($ten) : self::maKhLookupKey($maKh);
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
