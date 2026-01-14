<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\InfusionSession;
use App\Models\EndpointSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class RegisterController extends Controller
{
    public function index()
    {
        return view('register.index');
    }

    public function search(Request $request)
    {
        $identifier = $request->input('identifier');

        if (!$identifier) {
            return response()->json([
                'error' => 'Identifier harus diisi'
            ], 422);
        }

        // 1. Cari di database lokal
        $patient = Patient::where('identifier', $identifier)->first();

        if ($patient) {
            return response()->json([
                'name' => $patient->name,
                'age' => Carbon::parse($patient->age)->age . ' tahun',
                'gender' => $patient->gender,
                'location' => $patient->location
            ]);
        }

        // 2. Ambil dari RME
        $rmeEndpoint = EndpointSetting::where('service', 'rme')
            ->where('is_active', true)
            ->first();

        if (!$rmeEndpoint) {
            abort(503, 'RME service tidak tersedia');
        }

        $response = Http::timeout(5)
            ->withHeaders([
                'Authorization' => 'Bearer ' . trim($rmeEndpoint->api_key),
                'Accept'        => 'application/json',
            ])
            ->get(
                rtrim($rmeEndpoint->base_url, '/') . "/fhir/Patient/{$identifier}"
            );

        if ($response->status() === 401) {
            abort(401, 'Unauthorized ke RME');
        }

        if ($response->failed()) {
            return response()->json([
                'error' => 'Data pasien tidak ditemukan'
            ], 404);
        }


        $rmePatient = $response->json();

        $birthDate = Carbon::parse($rmePatient['birthDate']);
        $age = $birthDate->age;

        // 3. Simpan ke database lokal
        $patient = Patient::create([
            'identifier' => $rmePatient['identifier'][0]['value'],
            'name' => $rmePatient['name'][0]['text'] ?? '-',
            'gender' => $rmePatient['gender'] ?? 'unknown',
            'age' => $age, // INTEGER
            'location' => $rmePatient['location']['display'] ?? '-',
        ]);


        return response()->json([
            'name' => $patient->name,
            'age' => $patient->age . ' tahun',
            'gender' => $patient->gender,
            'location' => $patient->location
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'identifier' => 'required|string',
            'durasi' => 'required|integer|min:1',
        ]);

        $patient = Patient::where('identifier', $data['identifier'])->first();

        if (!$patient) {
            return back()->withErrors([
                'error' => 'Pasien tidak ditemukan. Silakan lakukan pencarian terlebih dahulu.'
            ]);
        }

        // Cek sesi aktif pasien
        $infusAktif = InfusionSession::where('identifier', $patient->identifier)
            ->where('status_sesi_infus', 'active')
            ->first();

        if ($infusAktif) {
            return back()->withErrors([
                'error' => 'Pasien sudah memiliki sesi infus yang aktif.'
            ]);
        }

        // Cek sesi lain yang belum selesai (device belum dipilih)
        $infusBelumSelesai = InfusionSession::whereNull('id_perangkat_infusee')->first();

        if ($infusBelumSelesai) {
            return back()->withErrors([
                'error' => 'Masih terdapat registrasi infus yang belum dipasangkan device.'
            ]);
        }

        // Simpan sesi infus
        InfusionSession::create([
            'identifier_pract' => auth()->guard('pegawai')->user()->identifier_pract,
            'identifier' => $patient->identifier,
            'durasi_infus_jam' => (int) $data['durasi'],
            'status_sesi_infus' => 'active',
        ]);

        return redirect()
            ->route('devices.index')
            ->with('success', 'Data infus berhasil disimpan.');
    }
}
