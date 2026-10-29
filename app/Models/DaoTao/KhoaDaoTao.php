<?php

namespace App\Models\DaoTao;

use App\Models\PMGPLX\KhoaHoc;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KhoaDaoTao extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'KhoaDaoTao';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'MaKhoa',
        'TenKhoa',
        'HangDaoTao',
        'NgayKhaiGiang',
        'NgayBeGiang',
        'TrangThai',
        'GhiChu',
        'NgayTao',
        'NgayCapNhat',
    ];

    protected $casts = [
        'NgayKhaiGiang' => 'date',
        'NgayBeGiang' => 'date',
        'NgayTao' => 'datetime',
        'NgayCapNhat' => 'datetime',
    ];

    public function phanCong(): HasMany
    {
        return $this->hasMany(PhanCongDaoTao::class, 'KhoaDaoTaoId');
    }

    public static function findOrCreateByTenKhoa(string $tenKhoa): self
    {
        $tenKhoa = self::normalizeTenKhoa($tenKhoa);

        return static::query()->firstOrCreate(
            ['TenKhoa' => $tenKhoa],
            [
                'HangDaoTao' => self::guessHangDaoTao($tenKhoa),
                'TrangThai' => 'Đang đào tạo',
                'NgayTao' => now(),
            ]
        );
    }

    public static function normalizeTenKhoa(string $tenKhoa): string
    {
        return strtoupper(trim($tenKhoa));
    }

    /**
     * Tra khoá MANHLINH khi chọn khoá PMGPLX — TenKhoa trên sổ phân công thường là mã ngắn
     * (vd. C1K04, K56, 4 ký tự cuối của tên khoá), không phải nguyên TenKH.
     */
    public static function findForPmKhoaHoc(KhoaHoc $khoa): ?self
    {
        $maKh = trim((string) $khoa->MaKH);
        if ($maKh !== '') {
            $byMa = static::query()->where('MaKhoa', $maKh)->first();
            if ($byMa !== null) {
                return $byMa;
            }
        }

        foreach (self::tenKhoaLookupCandidatesFromPm($khoa) as $ten) {
            $found = static::query()->where('TenKhoa', $ten)->first();
            if ($found !== null) {
                return $found;
            }
        }

        $compact = self::compactTenKhoa((string) ($khoa->TenKH ?? ''));
        if ($compact === '') {
            return null;
        }

        return static::query()
            ->get(['Id', 'TenKhoa'])
            ->filter(function (self $row) use ($compact): bool {
                $stored = self::normalizeTenKhoa((string) $row->TenKhoa);
                if ($stored === '' || mb_strlen($stored) < 2) {
                    return false;
                }

                return str_ends_with($compact, $stored);
            })
            ->sortByDesc(fn (self $row): int => mb_strlen(self::normalizeTenKhoa((string) $row->TenKhoa)))
            ->first();
    }

    /**
     * @return list<string> TenKhoa đã normalize, ưu tiên khớp cụ thể trước
     */
    public static function tenKhoaLookupCandidatesFromPm(KhoaHoc $khoa): array
    {
        $tenKh = trim((string) ($khoa->TenKH ?? ''));
        $compact = self::compactTenKhoa($tenKh);
        $ordered = [];

        if ($tenKh !== '') {
            $ordered[] = self::normalizeTenKhoa($tenKh);
        }

        if (preg_match('/(B01|C1|B1|B)K(\d+)/i', $tenKh, $m)) {
            $ordered[] = self::normalizeTenKhoa(strtoupper($m[1]).'K'.$m[2]);
        }

        if (preg_match('/BK(\d+)/i', $tenKh, $m)) {
            $ordered[] = 'K'.$m[1];
            $ordered[] = 'BK'.$m[1];
        }

        if (preg_match('/K(\d+)\s*$/i', $tenKh, $m)) {
            $ordered[] = 'K'.$m[1];
        }

        if ($compact !== '' && mb_strlen($compact) >= 4) {
            $ordered[] = mb_substr($compact, -4);
        }

        $maKh = trim((string) $khoa->MaKH);
        if ($maKh !== '') {
            $ordered[] = self::normalizeTenKhoa($maKh);
        }

        $unique = [];
        foreach ($ordered as $value) {
            $value = self::normalizeTenKhoa($value);
            if ($value !== '' && ! in_array($value, $unique, true)) {
                $unique[] = $value;
            }
        }

        return $unique;
    }

    private static function compactTenKhoa(string $ten): string
    {
        $ten = self::normalizeTenKhoa($ten);

        return preg_replace('/\s+/u', '', $ten) ?? $ten;
    }

    private static function guessHangDaoTao(string $tenKhoa): ?string
    {
        if (preg_match('/^([A-Z]\d?)/', $tenKhoa, $m)) {
            return $m[1];
        }

        return null;
    }
}
