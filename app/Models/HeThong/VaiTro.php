<?php

namespace App\Models\HeThong;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/** Danh mục vai trò (Hệ Thống → Vai trò). Admin (LaHeThong) luôn đủ quyền, không xoá được. */
class VaiTro extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'VaiTro';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    public const ADMIN = 'admin';

    protected $fillable = [
        'MaVaiTro',
        'TenVaiTro',
        'ThuTu',
        'LaHeThong',
        'NgayTao',
    ];

    protected $casts = [
        'ThuTu' => 'integer',
        'LaHeThong' => 'boolean',
        'NgayTao' => 'datetime',
    ];

    /** @var array<string, string>|null */
    private static ?array $cache = null;

    /**
     * Mã => tên, theo thứ tự hiển thị (cache trong 1 request). Bảng chưa tạo thì dùng danh sách mặc định cũ.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        if (! Schema::connection('sqlsrv_manhlinh')->hasTable('VaiTro')) {
            return self::$cache = NguoiDung::VAI_TRO_MAC_DINH;
        }

        return self::$cache = static::query()
            ->orderBy('ThuTu')
            ->orderBy('Id')
            ->pluck('TenVaiTro', 'MaVaiTro')
            ->map(fn ($t) => (string) $t)
            ->all();
    }

    public static function xoaCache(): void
    {
        self::$cache = null;
    }
}
