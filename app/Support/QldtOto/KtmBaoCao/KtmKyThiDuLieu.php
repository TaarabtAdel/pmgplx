<?php

namespace App\Support\QldtOto\KtmBaoCao;

/** Dữ liệu chuẩn một kỳ KTM sau khi đọc file nhập. */
final class KtmKyThiDuLieu
{
    /**
     * @param  list<KtmThiSinh>  $thiSinh
     * @param  list<string>  $canhBao
     * @param  array<string, KtmTongHop>|null  $tongHopTheoHang  nhóm hạng (từ báo cáo chi tiết)
     */
    public function __construct(
        public string $tenKhoa,
        public string $ngayThi,
        public int $tongHoSo,
        public int $g7TuFile,
        public array $thiSinh,
        public KtmTongHop $tongHopBTTml,
        public KtmTongHop $tongHopKhac,
        public array $canhBao = [],
        public ?array $tongHopTheoHang = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'tenKhoa' => $this->tenKhoa,
            'ngayThi' => $this->ngayThi,
            'tongHoSo' => $this->tongHoSo,
            'g7TuFile' => $this->g7TuFile,
            'thiSinh' => array_map(fn (KtmThiSinh $t) => $t->toArray(), $this->thiSinh),
            'tongHopBTTml' => $this->tongHopBTTml->toArray(),
            'tongHopKhac' => $this->tongHopKhac->toArray(),
            'canhBao' => $this->canhBao,
        ];
    }

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self
    {
        $thiSinh = array_map(fn (array $t) => KtmThiSinh::fromArray($t), $data['thiSinh'] ?? []);

        return new self(
            (string) $data['tenKhoa'],
            (string) $data['ngayThi'],
            (int) $data['tongHoSo'],
            (int) ($data['g7TuFile'] ?? $data['tongHoSo'] ?? 0),
            $thiSinh,
            KtmTongHop::fromArray($data['tongHopBTTml'] ?? []),
            KtmTongHop::fromArray($data['tongHopKhac'] ?? []),
            (array) ($data['canhBao'] ?? []),
            null,
        );
    }
}