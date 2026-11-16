<?php

namespace App\Support\QldtOto\KtmBaoCao;

final class KtmThiSinh
{
    /**
     * @param  array{lt: bool, mp: bool, h: bool, duong: bool, cc: bool}  $phaiThi
     */
    public function __construct(
        public int $stt,
        public string $sbd,
        public string $hoTen,
        public string $soDinhDanh,
        public string $ngaySinh,
        public string $hang,
        public string $ghiChu,
        public array $phaiThi,
        public bool $coMat,
        public string $lt,
        public string $mp,
        public string $h,
        public string $cc,
        public string $duong,
        public string $tongHop,
        public int $lanShLt,
        public int $lanShMp,
        public int $lanShH,
        public int $lanShCc,
        public int $lanShDuong,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self
    {
        return new self(
            (int) $data['stt'],
            (string) $data['sbd'],
            (string) $data['hoTen'],
            (string) $data['soDinhDanh'],
            (string) $data['ngaySinh'],
            (string) $data['hang'],
            (string) $data['ghiChu'],
            (array) $data['phaiThi'],
            (bool) $data['coMat'],
            (string) $data['lt'],
            (string) $data['mp'],
            (string) $data['h'],
            (string) $data['cc'],
            (string) $data['duong'],
            (string) $data['tongHop'],
            (int) $data['lanShLt'],
            (int) $data['lanShMp'],
            (int) $data['lanShH'],
            (int) $data['lanShCc'],
            (int) $data['lanShDuong'],
        );
    }
}