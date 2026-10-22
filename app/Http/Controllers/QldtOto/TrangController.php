<?php

namespace App\Http\Controllers\QldtOto;

use App\Http\Controllers\Controller;
use App\Support\MenuQldt;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Các màn QLĐT Ô TÔ chưa có chức năng: hiển thị trang trắng theo mục menu.
 * Khi làm màn thật, đổi route tương ứng trong routes/qldtoto.php sang controller mới.
 */
class TrangController extends Controller
{
    public function index(): View
    {
        return view('QldtOto.index', [
            'tabs' => MenuQldt::tabs(),
        ]);
    }

    public function show(Request $request): View
    {
        $slug = (string) $request->route('trang');
        $trang = MenuQldt::timTrang($slug);
        abort_if($trang === null, 404);

        return view('QldtOto.trang', [
            'item' => $trang['item'],
            'duongDan' => $trang['duong_dan'],
        ]);
    }
}
