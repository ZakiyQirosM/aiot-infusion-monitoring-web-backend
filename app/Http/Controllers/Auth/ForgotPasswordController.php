<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Practitioner;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;

class ForgotPasswordController extends Controller
{
    public function showResetForm()
    {
        return view('auth.reset_form');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'identifier_pract' => 'required'
        ]);

        $pegawai = Practitioner::where('identifier_pract', $request->identifier_pract)->first();

        if (!$pegawai) {
            return back()->withErrors(['identifier_pract' => 'No Pegawai tidak ditemukan.']);
        }

        // Enkripsi NIK
        $encryptedNik = Crypt::encryptString($pegawai->identifier_pract);
        $url = url(route('password.set.form', ['nik' => $encryptedNik]));

        $message = "Halo {$pegawai->name_pract},\nKlik link berikut untuk atur ulang password Anda:\n$url";

        Http::withHeaders([
            'Authorization' => env('FONNTE_TOKEN'),
        ])->post('https://api.fonnte.com/send', [
            'target' => $pegawai->no_wa,
            'message' => $message,
        ]);

        return back()->with('success', 'Link reset password telah dikirim ke WhatsApp.');
    }

    public function showNewPasswordForm(Request $request)
    {
        try {
            $decryptedNik = Crypt::decryptString($request->query('nik'));
            $pegawai = Practitioner::where('identifier_pract', $decryptedNik)->firstOrFail();

            return view('auth.reset_new_password', compact('pegawai'));

        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['link' => 'Link tidak valid atau telah kadaluarsa.']);
        }
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'identifier_pract' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);

        $pegawai = Practitioner::where('identifier_pract', $request->identifier_pract)->first();

        if (!$pegawai) {
            return redirect()->route('login')->withErrors(['identifier_pract' => 'No Pegawai tidak ditemukan.']);
        }

        $pegawai->password = Hash::make($request->password);
        $pegawai->save();

        return redirect()->route('login')->with('success', 'Password berhasil direset. Silakan login.');
    }
}
