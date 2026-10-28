<?php

namespace App\Support\DaoTao;

use App\Models\DaoTao\DatDSPhien;
use App\Models\PMGPLX\KhoaHoc;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class DatKhoaHocSelectOptions
{
    /**
     * @return Collection<int, object{MaKhoaHoc: string, TenKhoaHoc: string}>
     */
    public static function fromDatDSPhien(): Collection
    {
        return self::datDSPhienQuery()->get();
    }

    /**
     * Toàn bộ khoá trên PMGPLX (bảng KhoaHoc — cùng nguồn màn /pmgplx/danh-muc/khoa-hoc).
     *
     * @return Collection<int, object{MaKhoaHoc: string, TenKhoaHoc: string}>
     */
    public static function fromPmgplx(): Collection
    {
        $options = KhoaHoc::query()
            ->whereNotNull('MaKH')
            ->where('MaKH', '!=', '')
            ->orderByDesc('TenKH')
            ->orderByDesc('MaKH')
            ->get(['MaKH', 'TenKH'])
            ->map(static fn (KhoaHoc $kh): object => (object) [
                'MaKhoaHoc' => (string) $kh->MaKH,
                'TenKhoaHoc' => trim((string) ($kh->TenKH ?? '')),
            ]);

        return self::sortAlphabetical($options);
    }

    /**
     * @return Builder<DatDSPhien>
     */
    public static function datDSPhienQuery(): Builder
    {
        return DatDSPhien::query()
            ->selectRaw('MaKhoaHoc, MAX(TenKhoaHoc) as TenKhoaHoc')
            ->whereNotNull('MaKhoaHoc')
            ->where('MaKhoaHoc', '!=', '')
            ->groupBy('MaKhoaHoc')
            ->orderByRaw('MAX(TenKhoaHoc) COLLATE Vietnamese_CI_AS ASC')
            ->orderBy('MaKhoaHoc');
    }

    /**
     * @param  Collection<int, object{MaKhoaHoc?: string, TenKhoaHoc?: string}>  $options
     * @return Collection<int, object{MaKhoaHoc?: string, TenKhoaHoc?: string}>
     */
    public static function sortAlphabetical(Collection $options): Collection
    {
        return $options->sortBy(function (object $kh): string {
            $ten = trim((string) ($kh->TenKhoaHoc ?? ''));
            $ma = trim((string) ($kh->MaKhoaHoc ?? ''));
            $primary = $ten !== '' ? $ten : $ma;

            return mb_strtolower($primary, 'UTF-8')."\0".$ma;
        }, SORT_STRING)->values();
    }
}
