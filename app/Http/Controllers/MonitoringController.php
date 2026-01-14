<?php

namespace App\Http\Controllers;

use App\Models\MonitoringInfus;
use App\Models\InfusionSession;
use Illuminate\Http\Request;
use App\Models\EndpointSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MonitoringController extends Controller
{
    public function store(Request $request)
{
    $validated = $request->validate([
        'id_session' => 'required|string',
        'berat' => 'required|numeric',
        'tpm_sensor' => 'required|numeric',
    ]);

    $id_session = $validated['id_session'];
    $berat = $validated['berat'];
    $tpm_sensor = $validated['tpm_sensor'];

    try {
        $existing = MonitoringInfus::where('id_session', $id_session)->first();

        if (!$existing) {
            // CREATE
            $monitoringData = MonitoringInfus::create([
                'id_session' => $id_session,
                'berat_total' => $berat,
                'berat_sekarang' => $berat,
                'tpm_sensor' => $tpm_sensor,
                'tpm_prediksi' => 0,
                'waktu' => now(),
                'created_at' => now(),
            ]);
        } else {
            if ($existing->berat_total <= 0) {
                $existing->berat_total = $berat;
            }
            $berat_sekarang = min($berat, $existing->berat_total);

            $existing->update([
                'berat_sekarang' => $berat_sekarang,
                'berat_total' => $existing->berat_total,
                'tpm_sensor' => $tpm_sensor,
                'waktu' => now()
            ]);

            $monitoringData = $existing;
        }

        if ($monitoringData->tpm_prediksi == 0) {
            $prediction = $this->getPrediction($id_session);

            if (isset($prediction['tpm_prediksi'])) {
                $monitoringData->tpm_prediksi = $prediction['tpm_prediksi'];
                $monitoringData->save();
            }
        } else {
            $prediction = ['message' => 'Prediksi sudah dilakukan'];
        }

        $berat_total = $monitoringData->berat_total > 0 ? $monitoringData->berat_total : 1;
        $persentase = round(($monitoringData->berat_sekarang / $berat_total) * 100, 2);

        $session = InfusionSession::with(['patient', 'pegawai'])->find($id_session);
        $patient = $session?->patient;
        $pegawai = $session?->pegawai;

        if ($persentase <= 10 && $patient && $pegawai && !$monitoringData->wa_notif_sent) {
            $this->sendWhatsAppAlert(
                $pegawai->no_wa,
                'Sistem',
                $patient->name,
                $patient->location,
                $persentase
            );

            $monitoringData->wa_notif_sent = true;
            $monitoringData->save();
        }

        return response()->json([
            'success' => true,
            'message' => $existing ? 'Data monitoring diperbarui' : 'Data monitoring awal disimpan',
            'data' => $monitoringData,
            'prediction' => $prediction
        ]);

    } catch (\Exception $e) {
        Log::error('Monitoring store error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        return response()->json([
            'error' => 'Gagal menyimpan data monitoring'
        ], 500);
    }
}

public function storeInternal($id_session, $berat = null, $tpm_sensor = null)
{
    try {
        $session = InfusionSession::find($id_session);
        if (!$session) {
            Log::error('MonitoringController: Session tidak ditemukan', ['id_session' => $id_session]);
            return ['success' => false, 'error' => 'Session tidak valid'];
        }

        $berat_total = $berat ?? 0;
        $berat = $berat ?? $berat_total;
        $tpm_sensor = $tpm_sensor ?? 0;

        $existing = MonitoringInfus::where('id_session', $id_session)->first();

        if (!$existing) {
            $monitoringData = MonitoringInfus::create([
                'id_session' => $id_session,
                'berat_total' => $berat_total,
                'berat_sekarang' => $berat_total,
                'tpm_sensor' => $tpm_sensor,
                'tpm_prediksi' => 0,
                'waktu' => now(),
                'created_at' => now(),
            ]);
        } else {
            if ($existing->berat_total <= 0) {
                $existing->berat_total = $berat_total;
            }
        
            $berat_sekarang = min($berat_total, $existing->berat_total);
        
            $existing->update([
                'berat_sekarang' => $berat_sekarang,
                'berat_total' => $existing->berat_total,
                'tpm_sensor' => $tpm_sensor,
                'waktu' => now()
            ]);
        
            $monitoringData = $existing;
        }

        if ($monitoringData->tpm_prediksi == 0) {
            $prediction = $this->getPrediction($id_session);

            if (isset($prediction['tpm_prediksi'])) {
                $monitoringData->tpm_prediksi = $prediction['tpm_prediksi'];
                $monitoringData->save();
            }
        } else {
            $prediction = ['message' => 'Prediksi sudah dilakukan'];
        }

        Log::info('Monitoring awal berhasil disimpan', [
            'id_session' => $id_session,
            'berat_total' => $berat_total,
            'tpm_sensor' => $tpm_sensor
        ]);

        return [
            'success' => true,
            'message' => $existing ? 'Monitoring diperbarui' : 'Monitoring awal dibuat',
            'data' => $monitoringData,
            'prediction' => $prediction
        ];

    } catch (\Exception $e) {
        Log::error('Gagal menyimpan data monitoring', [
            'error' => $e->getMessage(),
            'session' => $id_session,
            'trace' => $e->getTraceAsString()
        ]);
        return ['success' => false, 'error' => 'Gagal menyimpan data monitoring: ' . $e->getMessage()];
    }
}


    protected function getPrediction($id_session)
    {
        try {
            $mlEndpoint = EndpointSetting::active('ai');

            if (!$mlEndpoint) {
                abort(503, 'AI service tidak tersedia');
            }

            $response = Http::timeout(5)
                ->when($mlEndpoint->api_key, fn ($http) =>
                    $http->withToken($mlEndpoint->api_key)
                )
                ->post($mlEndpoint->base_url.'/prediksi-dari-db', [
                    'id_session' => (string) $id_session,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Prediksi gagal', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return ['error' => 'Service prediksi sementara tidak tersedia'];

        } catch (\Exception $e) {
            Log::error('Koneksi ke service prediksi gagal', [
                'error' => $e->getMessage(),
                'session' => $id_session
            ]);
            return ['error' => 'Koneksi ke service prediksi gagal'];
        }
    }

    protected function sendWhatsAppAlert($no_wa, $sender, $name, $location, $persentase)
    {
        try {
            $message = "⚠️ Infus pasien *$name* di ruang *$location* tersisa hanya *$persentase%*. Segera cek!";

            Http::withHeaders([
                'Authorization' => env('FONNTE_TOKEN'),
            ])->post('https://api.fonnte.com/send', [
                'target' => $no_wa,
                'message' => $message,
            ]);

            Log::info("Notifikasi WA berhasil dikirim ke $no_wa");
        } catch (\Exception $e) {
            Log::error('Gagal mengirim WA alert', ['error' => $e->getMessage()]);
        }
    }


}
