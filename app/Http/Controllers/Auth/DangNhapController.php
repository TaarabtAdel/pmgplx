<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DangNhapController extends Controller
{
    public function create(): View
    {
        return view('auth.dang-nhap');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'so_dien_thoai' => preg_replace('/\s+/', '', (string) $request->input('so_dien_thoai')),
        ]);

        $validated = $request->validate([
            'so_dien_thoai' => ['required', 'string', 'max:20'],
            'mat_khau' => ['required', 'string', 'max:100'],
        ], [
            'so_dien_thoai.required' => 'Nhập số điện thoại.',
            'mat_khau.required' => 'Nhập mật khẩu.',
        ]);

        $ok = Auth::attempt([
            'SoDienThoai' => $validated['so_dien_thoai'],
            'password' => $validated['mat_khau'],
        ]);

        if (! $ok) {
            throw ValidationException::withMessages([
                'so_dien_thoai' => 'Số điện thoại hoặc mật khẩu không đúng.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Đã đăng xuất.');
    }
}
