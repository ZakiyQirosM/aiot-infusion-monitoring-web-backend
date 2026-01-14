<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Patient;
use App\Models\MonitoringInfus;
use App\Models\InfusionSession;
use App\Models\Device;
use App\Models\HistoryActivity;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class InfuseeController extends Controller
{
    public function getLatestInfus()
    {
        $monitoringData = MonitoringInfus::whereHas('infusionsession', function ($query) {
            $query->where('status_sesi_infus', 'active');
        })->with(['infusionsession.patient'])->get();

        $infusees = $monitoringData->map(function ($dinfus) {
            $session = $dinfus->infusionsession;
            $patient = $session?->patient;

            $berat_total = $dinfus->berat_total ?? 1;
            $berat_sekarang = $dinfus->berat_sekarang ?? 0;
            
            $persentase = ($berat_total <= 0) ? 0 : round(($berat_sekarang / $berat_total) * 100, 2);                     

            $tpm = $dinfus->tpm_sensor ?? 0;
            $reference = $dinfus->tpm_prediksi ?? 0;
            
            if ($reference == 0) {
                $status = 'normal';
                $bgColor = '#00cc44';
                $icon = 'fas fa-circle-check';
            } else {
                if ($tpm < ($reference * 0.50)) {
                    $status = 'slow';
                    $bgColor = '#ff3333';
                    $icon = 'fa fa-arrow-down';
                } elseif ($tpm > ($reference * 1.20)) {
                    $status = 'fast';
                    $bgColor = '#ff3333';
                    $icon = 'fa fa-arrow-up';
                } else {
                    $status = 'normal';
                    $bgColor = '#00cc44';
                    $icon = 'fas fa-circle-check';
                }
            }

            return [
                'id_session' => $dinfus->id_session,
                'berat_sekarang' => $berat_sekarang,
                'berat_total' => $berat_total,
                'persentase_infus' => $persentase,
                'tpm_sensor' => $dinfus->tpm_sensor,
                'tpm_prediksi' => $dinfus->tpm_prediksi,
                'bgColor' => $bgColor,
                'icon' => $icon,
                'status' => $status
            ];
        });

        return response()->json($infusees);
    }

    public function index()
    {
        $layout = auth('pegawai')->check() ? 'layouts.main' : 'layouts.guest';

        $monitoringData = MonitoringInfus::whereHas('infusionsession', function ($query) {
            $query->where('status_sesi_infus', 'active');
        })->with(['infusionsession.patient'])->get();

        $infusees = $monitoringData->map(function ($dinfus) {
            $session = $dinfus->infusionsession;
            $patient = $session?->patient;

            $berat_total = $dinfus->berat_total ?? 1;
            $berat_sekarang = $dinfus->berat_sekarang ?? 0;

            if ($berat_total <= 0) {
                $persentase = 0;
            } else {
                $persentase = round(($berat_sekarang / $berat_total) * 100, 2);
            }
            $tpm = $dinfus->tpm_sensor ?? 0;
            $reference = $dinfus->tpm_prediksi ?? 0;
            
            if ($reference == 0) {
                $status = 'normal';
                $bgColor = '#00cc44';
                $icon = 'fas fa-circle-check';
            } else {
                if ($tpm < ($reference * 0.50)) {
                    $status = 'slow';
                    $bgColor = '#ff3333';
                    $icon = 'fa fa-arrow-down';
                } elseif ($tpm > ($reference * 1.20)) {
                    $status = 'fast';
                    $bgColor = '#ff3333';
                    $icon = 'fa fa-arrow-up';
                } else {
                    $status = 'normal';
                    $bgColor = '#00cc44';
                    $icon = 'fas fa-circle-check';
                }
            }

            return [
                'id_session' => $dinfus->id_session ?? '-',
                'name' => $patient->name ?? '-',
                'identifier' => $patient->identifier ?? '-',
                'location' => $patient->location ?? '-',
                'id_perangkat_infusee' => $session->id_perangkat_infusee ?? '-',
                'berat_total' => $berat_sekarang,
                'tpm_sensor' => $dinfus->tpm_sensor ?? '-',
                'durasi_infus_jam' => $session->durasi_infus_jam ?? 0,
                'tpm_prediksi' => $dinfus->tpm_prediksi ?? 0,
                'persentase_infus' => $persentase,
                'created_at' => $dinfus->created_at ?? '-',
                'status_sesi_infus' => $dinfus->status_sesi_infus ?? '-',
                'color' => $this->getColorBasedOnPercentage($persentase ?? 0),
                'bgColor' => $bgColor,
                'icon' => $icon,
                'status' => $status,
                'timestamp_infus' => $session->updated_at?->setTimezone('Asia/Jakarta')->format('c'),
            ];
        });

        return view('infusee.index', compact('infusees', 'layout'));
    }

    private function getColorBasedOnPercentage($value)
    {
        if ($value >= 80) return '#00cc44'; // Hijau
        if ($value >= 60) return '#ffcc00'; // Kuning
        if ($value >= 40) return '#ff9900'; // Oranye
        if ($value >= 11) return '#ff3333'; // Merah
        return '#000000'; // Hitam
    }

    public function endSession($id_session)
    {
        $session = InfusionSession::findOrFail($id_session);

        $session->update([
            'status_sesi_infus' => 'ended'
        ]);

        $device = Device::where('id_perangkat_infusee', $session->id_perangkat_infusee)->first();
        if ($device) {
            $device->update(['status' => 'available']);

            $iotResponse = Http::timeout(10)
                ->post('http://' . $device->alamat_ip_infusee . '/stop-monitoring', [
                    'id_session' => (string)$session->id_session,
                    'command' => 'stop'
                ]);

            if (!$iotResponse->successful()) {
                throw new \Exception('Gagal menghentikan monitoring di perangkat: ' . $iotResponse->body());
            }
        }

        HistoryActivity::create([
            'id_session' => $session->id_session,
            'identifier_pract' => Auth::user()->identifier_pract,
            'aktivitas' => 'Mengakhiri sesi infus',
        ]);

        return redirect()->route('infusee.index')->with('success');
    }

    public function checkSessionStatus(Request $request)
    {
        $request->validate([
            'id_perangkat_infusee' => 'required|string'
        ]);

        $deviceId = $request->input('id_perangkat_infusee');
        
        $session = InfusionSession::where('id_perangkat_infusee', $deviceId)
                    ->where('status_sesi_infus', 'active')
                    ->orderBy('timestamp_infus', 'desc')
                    ->first();

        if ($session) {
            return response()->json([
                'status' => 'success',
                'status_sesi_infus' => 'active',
                'id_session' => $session->id_session,
                'patient_id' => $session->no_reg_pasien,
                'started_at' => $session->timestamp_infus
            ]);
        }

        return response()->json([
            'status' => 'success',
            'status_sesi_infus' => 'ended',
            'message' => 'No active session found'
        ]);
    }
}
