<?php

namespace App\Support\QldtOto\KtmBaoCao;

final class KtmTongHop
{
    public function __construct(
        public string $nhan,
        public int $hoSo,
        public int $duThi,
        public int $vang,
        public KtmCotBaSo $lt,
        public KtmCotBaSo $mp,
        public KtmCotBaSo $h,
        public KtmCotBaSo $cc,
        public KtmCotBaSo $duong,
        public int $ketQuaDat,
    ) {}

    public function tyLeDat(string $mauSo = 'du_thi'): float
    {
        $m = $mauSo === 'ho_so' ? $this->hoSo : $this->duThi;

        return $m > 0 ? round($this->ketQuaDat / $m * 1000) / 10 : 0.0;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'nhan' => $this->nhan,
            'hoSo' => $this->hoSo,
            'duThi' => $this->duThi,
            'vang' => $this->vang,
            'lt' => ['tong' => $this->lt->tong, 'dat' => $this->lt->dat, 'rot' => $this->lt->rot],
            'mp' => ['tong' => $this->mp->tong, 'dat' => $this->mp->dat, 'rot' => $this->mp->rot],
            'h' => ['tong' => $this->h->tong, 'dat' => $this->h->dat, 'rot' => $this->h->rot],
            'cc' => ['tong' => $this->cc->tong, 'dat' => $this->cc->dat, 'rot' => $this->cc->rot],
            'duong' => ['tong' => $this->duong->tong, 'dat' => $this->duong->dat, 'rot' => $this->duong->rot],
            'ketQuaDat' => $this->ketQuaDat,
        ];
    }

    /** @param  array<string, mixed>  $data */
    public static function fromArray(array $data): self
    {
        $ba = fn (string $k) => new KtmCotBaSo(
            (int) ($data[$k]['tong'] ?? 0),
            (int) ($data[$k]['dat'] ?? 0),
            (int) ($data[$k]['rot'] ?? 0),
        );

        return new self(
            (string) ($data['nhan'] ?? ''),
            (int) ($data['hoSo'] ?? 0),
            (int) ($data['duThi'] ?? 0),
            (int) ($data['vang'] ?? 0),
            $ba('lt'),
            $ba('mp'),
            $ba('h'),
            $ba('cc'),
            $ba('duong'),
            (int) ($data['ketQuaDat'] ?? 0),
        );
    }
}