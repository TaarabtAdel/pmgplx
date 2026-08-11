<?php

namespace App\Http\Controllers\PMGPLX;

use App\Http\Controllers\Controller;

use Illuminate\View\View;

class TrangChuController extends Controller
{
    public function index(): View
    {
        return view('PMGPLX.trang-chu');
    }
}
