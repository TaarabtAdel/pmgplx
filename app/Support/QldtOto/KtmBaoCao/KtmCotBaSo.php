<?php

namespace App\Support\QldtOto\KtmBaoCao;

final class KtmCotBaSo
{
    public function __construct(
        public int $tong,
        public int $dat,
        public int $rot,
    ) {}
}