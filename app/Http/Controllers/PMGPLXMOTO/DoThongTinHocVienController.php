<?php

namespace App\Http\Controllers\PMGPLXMOTO;

use App\Http\Controllers\Controller;
use App\Models\DaoTao\HocVienDoThongTin;
use App\Support\PMGPLXMOTO\DoThongTinGiayTo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * «Dò thông tin» học viên từ ảnh CCCD / GPLX.
 * Máy tính mở modal → tạo phiên → hiện mã QR; điện thoại quét QR mở trang chụp (routes/do-thong-tin-cong-khai.php),
 * kết quả đổ về máy tính qua phiên. Mở thẳng trên điện thoại (đã đăng nhập) thì modal tự chụp và dò luôn.
 */
class DoThongTinHocVienController extends Controller
{
    /** Dò một ảnh từ modal (đã đăng nhập). */
    public function ocr(Request $request): JsonResponse
    {
        [$mat, $base64] = $this->anhHopLe($request);
        if ($base64 === null) {
            return response()->json(['message' => 'Ảnh không hợp lệ.'], 422);
        }

        return $this->doVaTraVe($mat, $base64, null);
    }

    /** Dò một ảnh từ trang điện thoại — lưu kết quả vào phiên để máy tính nhận. */
    public function ocrDienThoai(Request $request, string $token): JsonResponse
    {
        if (DoThongTinGiayTo::phien($token) === null) {
            return response()->json(['message' => 'Phiên dò đã hết hạn — quét lại mã QR trên máy tính.'], 410);
        }
        [$mat, $base64] = $this->anhHopLe($request);
        if ($base64 === null) {
            return response()->json(['message' => 'Ảnh không hợp lệ.'], 422);
        }

        return $this->doVaTraVe($mat, $base64, $token);
    }

    /** Tạo phiên + đường dẫn (tương đối) cho điện thoại — modal ghép với địa chỉ máy chủ để vẽ mã QR. */
    public function taoPhien(): JsonResponse
    {
        $token = DoThongTinGiayTo::taoPhien();

        return response()->json([
            'token' => $token,
            'duong_dan' => route('do-thong-tin.dien-thoai', $token, false),
            'goc_cau_hinh' => rtrim((string) config('services.google_vision.public_url'), '/'),
        ]);
    }

    /** Máy tính hỏi kết quả điện thoại đã gửi về. */
    public function xemPhien(string $token): JsonResponse
    {
        $phien = DoThongTinGiayTo::phien($token);
        if ($phien === null) {
            return response()->json(['message' => 'Phiên dò đã hết hạn.'], 410);
        }

        return response()->json($phien);
    }

    /** Trang chụp trên điện thoại (mở từ mã QR). */
    public function dienThoai(string $token): View
    {
        return view('PMGPLXMOTO.danh-muc.do-thong-tin-dien-thoai', [
            'token' => $token,
            'conHan' => DoThongTinGiayTo::phien($token) !== null,
        ]);
    }

    /** Tìm học viên PMGPLX khớp kết quả dò để so sánh. */
    public function timHocVien(Request $request): JsonResponse
    {
        return response()->json([
            'hoc_vien' => DoThongTinGiayTo::timHocVien(
                (string) $request->query('so_cccd', ''),
                (string) $request->query('ho_ten', ''),
                (string) $request->query('ngay_sinh', ''),
                (string) $request->query('ma_dk', ''),
            ),
        ]);
    }

    /** Lưu kết quả đối chiếu (Khớp / Không khớp từng trường) của một học viên vào MANHLINH. */
    public function luu(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ma_dk' => ['required', 'string', 'max:50'],
            'ket_luan' => ['required', Rule::in(array_keys(HocVienDoThongTin::NHAN_KET_LUAN))],
            'ket_qua' => ['required', 'array', 'min:1'],
            'ket_qua.*' => ['nullable', 'string', 'max:300'],
        ], [
            'ket_luan.required' => 'Chọn kết luận OK hoặc Cần chỉnh.',
            'ket_luan.in' => 'Chọn kết luận OK hoặc Cần chỉnh.',
            'ket_qua.required' => 'Chưa có kết quả dò để đối chiếu.',
        ]);

        try {
            $ban = DoThongTinGiayTo::luu($data['ma_dk'], $data['ket_qua'], $data['ket_luan'], auth()->id());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $trangThai = $ban->trangThai();
        $lech = count(array_filter($trangThai, fn (bool $k) => ! $k));

        return response()->json([
            'message' => 'Đã lưu '.$data['ma_dk'].': '.HocVienDoThongTin::NHAN_KET_LUAN[$ban->KetLuan]
                .' ('.(count($trangThai) - $lech).' khớp, '.$lech.' không khớp).',
            'da_luu' => $trangThai,
            'ket_luan' => $ban->KetLuan,
        ]);
    }

    /** @return array{0: string, 1: string|null} [mặt giấy tờ, base64 hoặc null nếu ảnh không hợp lệ] */
    private function anhHopLe(Request $request): array
    {
        $data = $request->validate([
            'mat' => ['required', Rule::in(array_keys(DoThongTinGiayTo::MAT))],
            'image' => ['required', 'string', 'max:'.(12 * 1024 * 1024)],
        ]);

        $base64 = preg_replace('/^data:image\/[\w+.\-]+;base64,/', '', $data['image']) ?? '';
        $hopLe = $base64 !== '' && preg_match('/^[A-Za-z0-9+\/=\s]+$/', $base64) === 1;

        return [$data['mat'], $hopLe ? $base64 : null];
    }

    private function doVaTraVe(string $mat, string $base64, ?string $token): JsonResponse
    {
        try {
            $ketQua = DoThongTinGiayTo::doAnh($mat, $base64);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Không nhận dạng được: '.$e->getMessage()], 502);
        }

        if ($token !== null) {
            DoThongTinGiayTo::luuVaoPhien($token, $ketQua);
        }

        return response()->json($ketQua);
    }
}
