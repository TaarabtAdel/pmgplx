<?php

namespace App\Models\DaoTao;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatLichThucHanhPhienBan extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'DatLichThucHanhPhienBan';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'DuAnId',
        'Ten',
        'LichJson',
        'TomTatJson',
        'KiemTraJson',
        'OChinhTayJson',
        'LaNhap',
        'NgayTao',
    ];

    protected $casts = [
        'LaNhap' => 'boolean',
        'NgayTao' => 'datetime',
    ];

    /** @return BelongsTo<DatLichThucHanhDuAn, $this> */
    public function duAn(): BelongsTo
    {
        return $this->belongsTo(DatLichThucHanhDuAn::class, 'DuAnId', 'Id');
    }

    /** @return array<string, mixed> */
    public function lich(): array
    {
        $raw = json_decode((string) ($this->LichJson ?? '{}'), true);

        return is_array($raw) ? $raw : [];
    }

    /** @return array<string, mixed> */
    public function tomTat(): array
    {
        $raw = json_decode((string) ($this->TomTatJson ?? '{}'), true);

        return is_array($raw) ? $raw : [];
    }

    /** @return array{errors: list<string>, warnings: list<string>} */
    public function kiemTra(): array
    {
        $raw = json_decode((string) ($this->KiemTraJson ?? '{}'), true);
        if (! is_array($raw)) {
            return ['errors' => [], 'warnings' => []];
        }

        return [
            'errors' => array_values($raw['errors'] ?? []),
            'warnings' => array_values($raw['warnings'] ?? []),
        ];
    }

    /** @return list<string> */
    public function oChinhTayKeys(): array
    {
        $raw = json_decode((string) ($this->OChinhTayJson ?? '[]'), true);

        return is_array($raw) ? array_values($raw) : [];
    }
}
