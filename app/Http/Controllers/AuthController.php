<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            // Wajib modul: regenerasi session ID untuk menangkal serangan Session Fixation
            $request->session()->regenerate();

            // Simpan timestamp login ke sesi server
            session(['login_timestamp' => now()->format('d M Y, H:i:s') . ' WIB']);

            // Simpan email pustakawan ke Cookie klien selama 7 hari (10080 menit)
            Cookie::queue('del_last_user_email', $request->email, 10080);

            return redirect()->intended(route('buku.index'))->with('success', 'Sesi pustakawan aktif.');
        }

        return back()->withInput($request->only('email'))->with('error', 'Kredensial login tidak valid.');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        // Bersihkan sesi dan regenerate CSRF token
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Sesi Anda telah dihapus dengan aman.');
    }
}