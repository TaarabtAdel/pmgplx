<?php

namespace App\Models\DaoTao;

use App\Support\SatHach\XmlSatHachParser;
use Illuminate\Database\Eloquent\Model;

class SatHachBienBan extends Model
{
    protected $connection = 'sqlsrv_manhlinh';

    protected $table = 'SatHachBienBan';

    protected $primaryKey = 'Id';

    public $incrementing = true;

    public $timestamps = false;

    protected $fillable = [
        'MaKySH',
        'MaGiaoDich',
        'NgaySH',
        'SoQD',
        'SoTT',
        'MaDK',
        'HoVaTen',
        'NgaySinh',
        'SoCMT',
        'SoHoChieu',
        'NgayCapHC',
        'NoiCapHC',
        'SoBaoDanh',
        'HangGPLX',
        'DiemLtDat',
        'DiemHinhDat',
        'DiemDuongDat',
        'NhanXetLt',
        'NhanXetHinh',
        'NhanXetDuong',
        'KetQuaSH',
        'NgayKy',
        'ThangKy',
        'NamKy',
        'AnhChanDung',
        'FileNguon',
        'NgayNhap',
    ];

    /**
     * @return array<string, mixed>
     */
    public function toDocxRow(): array
    {
        return [
            'so_tt' => $this->SoTT,
            'ma_dk' => $this->MaDK,
            'ho_va_ten' => $this->HoVaTen,
            'ngay_sinh' => $this->NgaySinh,
            'so_cmt' => $this->SoCMT,
            'so_ho_chieu' => $this->SoHoChieu,
            'ngay_cap_hc' => $this->NgayCapHC,
            'noi_cap_hc' => $this->NoiCapHC,
            'so_bao_danh' => $this->SoBaoDanh,
            'hang_gplx' => $this->HangGPLX,
            'diem_lt_toida' => XmlSatHachParser::diemLtToiDa((string) ($this->HangGPLX ?? '')),
            'diem_lt_dat' => $this->DiemLtDat ?: '-',
            'diem_hinh_dat' => $this->DiemHinhDat ?: '-',
            'diem_duong_dat' => $this->DiemDuongDat ?: '-',
            'nhan_xet_lt' => $this->NhanXetLt,
            'nhan_xet_hinh' => $this->NhanXetHinh,
            'nhan_xet_duong' => $this->NhanXetDuong,
            'ket_qua_text' => XmlSatHachParser::ketQuaText((string) ($this->KetQuaSH ?? '')),
            'ngay_ky' => $this->NgayKy,
            'thang_ky' => $this->ThangKy,
            'nam_ky' => $this->NamKy,
            'anh_chan_dung_b64' => $this->AnhChanDung,
        ];
    }
}
