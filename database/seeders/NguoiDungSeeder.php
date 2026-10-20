<?php

namespace Database\Seeders;

use App\Models\HeThong\NguoiDung;
use Illuminate\Database\Seeder;

class NguoiDungSeeder extends Seeder
{
    public function run(): void
    {
        $nguoiDung = NguoiDung::query()->firstOrNew(['SoDienThoai' => 'quantri-ml']);
        if (! $nguoiDung->exists) {
            $nguoiDung->NgayTao = now();
        }
        $nguoiDung->fill([
            'Ten' => 'Quản trị Mạnh Linh',
            'MatKhau' => 'ML2026@',
            'NgayCapNhat' => now(),
        ]);
        $nguoiDung->save();

        $nguoiDung->ganVaiTro(array_keys(NguoiDung::VAI_TRO));
    }
}
