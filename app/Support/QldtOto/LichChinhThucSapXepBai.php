<?php

namespace App\Support\QldtOto;

use App\Models\QldtOto\LichChinhThuc;
use App\Models\QldtOto\LichChinhThucBuoi;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Kéo lại thứ tự bài trong từng cột (cặp · ca).
 * Ngày / GV / xe của slot giữ nguyên; nội dung + giờ + số bài được xếp lại theo thứ tự mới.
 */
final class LichChinhThucSapXepBai
{
    /**
     * @param  array<string, list<int|string>>  $thuTuTheoCot  key = "{CapThuTu}|{Ca}" → list buoi Id theo thứ tự mới
     */
    public static function apDung(LichChinhThuc $lich, array $thuTuTheoCot): void
    {
        $buois = $lich->buois()->get()->keyBy('Id');
        if ($buois->isEmpty()) {
            throw ValidationException::withMessages(['thu_tu' => 'Lịch chưa có buổi để sắp xếp.']);
        }

        $theoCot = $buois->groupBy(fn (LichChinhThucBuoi $b): string => $b->CapThuTu.'|'.$b->Ca);

        DB::connection('sqlsrv_manhlinh')->transaction(function () use ($thuTuTheoCot, $theoCot, $buois): void {
            foreach ($theoCot as $cotKey => $slots) {
                $slots = $slots->sortBy(fn (LichChinhThucBuoi $b) => $b->Ngay->format('Y-m-d').'|'.sprintf('%04d', $b->ThuTuBai))->values();
                $idsMoi = array_values(array_map('intval', (array) ($thuTuTheoCot[$cotKey] ?? [])));

                if ($idsMoi === []) {
                    // Không gửi cột này → giữ nguyên thứ tự hiện tại.
                    continue;
                }

                $idsCu = $slots->pluck('Id')->map(fn ($id) => (int) $id)->all();
                sort($idsCu);
                $check = $idsMoi;
                sort($check);
                if ($check !== $idsCu) {
                    throw ValidationException::withMessages([
                        'thu_tu' => 'Thứ tự bài cột '.$cotKey.' không khớp danh sách buổi hiện có.',
                    ]);
                }

                $noiDungTheoId = [];
                foreach ($idsMoi as $id) {
                    /** @var LichChinhThucBuoi $src */
                    $src = $buois->get($id);
                    $noiDungTheoId[] = [
                        'NoiDung' => $src->NoiDung,
                        'GioBatDau' => $src->GioBatDau,
                        'GioKetThuc' => $src->GioKetThuc,
                        'SoGio' => $src->SoGio,
                        'NgaySo' => $src->NgaySo,
                    ];
                }

                foreach ($slots as $i => $slot) {
                    $payload = $noiDungTheoId[$i];
                    $slot->fill([
                        'NoiDung' => $payload['NoiDung'],
                        'GioBatDau' => $payload['GioBatDau'],
                        'GioKetThuc' => $payload['GioKetThuc'],
                        'SoGio' => $payload['SoGio'],
                        'NgaySo' => $i + 1,
                        'ThuTuBai' => $i + 1,
                    ])->save();
                }
            }
        });

        $lich->forceFill(['NgayCapNhat' => Carbon::now()])->save();
    }
}
