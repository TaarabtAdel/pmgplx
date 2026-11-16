<?php

namespace App\Support\QldtOto\KtmBaoCao;

final class KtmTinhToan
{
  /**
   * @param  list<KtmThiSinh>  $thiSinh
   */
  public static function tongHopNhom(string $nhan, array $thiSinh): KtmTongHop
  {
    $hoSo = count($thiSinh);
    $duThi = 0;
    $lt = $mp = $h = $cc = $duong = [0, 0, 0];
    $ketQuaDat = 0;

    foreach ($thiSinh as $ts) {
      if ($ts->coMat) {
        $duThi++;
      }
      if ($ts->tongHop === 'Đ') {
        $ketQuaDat++;
      }
      self::congCot($ts, 'lt', $lt);
      self::congCot($ts, 'mp', $mp);
      self::congCot($ts, 'h', $h);
      self::congCot($ts, 'cc', $cc);
      self::congCot($ts, 'duong', $duong);
    }

    return new KtmTongHop(
      nhan: $nhan,
      hoSo: $hoSo,
      duThi: $duThi,
      vang: max(0, $hoSo - $duThi),
      lt: new KtmCotBaSo(...$lt),
      mp: new KtmCotBaSo(...$mp),
      h: new KtmCotBaSo(...$h),
      cc: new KtmCotBaSo(...$cc),
      duong: new KtmCotBaSo(...$duong),
      ketQuaDat: $ketQuaDat,
    );
  }

  public static function tongHopNhomRong(string $nhan): KtmTongHop
  {
    $z = new KtmCotBaSo(0, 0, 0);

    return new KtmTongHop($nhan, 0, 0, 0, $z, $z, $z, $z, $z, 0);
  }

  /** @param array{0:int,1:int,2:int} $acc */
  private static function congCot(KtmThiSinh $ts, string $field, array &$acc): void
  {
    if (! $ts->phaiThi[$field] || ! $ts->coMat) {
      return;
    }
    $kq = $ts->{$field};
    if ($kq === '') {
      return;
    }
    if (str_starts_with($kq, 'V')) {
      return;
    }
    $acc[0]++;
    if ($kq === 'Đ') {
      $acc[1]++;
    } elseif ($kq === 'KĐ') {
      $acc[2]++;
    }
  }

  public static function tongCong(KtmTongHop $a, KtmTongHop $b): KtmTongHop
  {
    return new KtmTongHop(
      'Cộng',
      $a->hoSo + $b->hoSo,
      $a->duThi + $b->duThi,
      $a->vang + $b->vang,
      self::congBaSo($a->lt, $b->lt),
      self::congBaSo($a->mp, $b->mp),
      self::congBaSo($a->h, $b->h),
      self::congBaSo($a->cc, $b->cc),
      self::congBaSo($a->duong, $b->duong),
      $a->ketQuaDat + $b->ketQuaDat,
    );
  }

  private static function congBaSo(KtmCotBaSo $x, KtmCotBaSo $y): KtmCotBaSo
  {
    return new KtmCotBaSo($x->tong + $y->tong, $x->dat + $y->dat, $x->rot + $y->rot);
  }
}