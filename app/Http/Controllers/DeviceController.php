<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\InfusionSession;
use App\Models\MonitoringInfus;
use App\Models\Patient;
use App\Models\HistoryActivity;
use App\Http\Controllers\MonitoringController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;


class DeviceController extends Controller
{
    public function ping(Request $request)
    {
        $request->validate([
            'id_perangkat_infusee' => 'required|string',
            'alamat_ip_infusee' => 'required|ip',
        ]);

        $device = Device::where('id_perangkat_infusee', $request->id_perangkat_infusee)->first();

        if ($device) {
            $device->update([
                'alamat_ip_infusee' => $request->alamat_ip_infusee,
                'last_ping' => now(),
                'status_device' => 'online',
            ]);
        } else {
            $device = Device::create([
                'id_perangkat_infusee' => $request->id_perangkat_infusee,
                'alamat_ip_infusee' => $request->alamat_ip_infusee,
                'status' => 'available',
                'last_ping' => now(),
                'status_device' => 'online',
            ]);
        }

        return response()->json([
            'message' => 'Ping diterima.',
            'device' => $device
        ]);
    }

    public function shutdown(Request $request)
    {
        try {
            $deviceId = $request->input('id_perangkat_infusee');
        
            if (!$deviceId) {
                return response()->json(['message' => 'id_perangkat_infusee required'], 400);
            }

            $device = Device::where('id_perangkat_infusee', $deviceId)->first();
            
            if (!$device) {
                return response()->json(['message' => 'Device not found'], 404);
            }

            $device->status_device = 'offline';
            $device->save();

            return response()->json(['message' => 'Device status updated to offline'], 200);
            
        } catch (\Exception $e) {
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }

    public function getPatientData()
    {
        $infusionSession = session()->get('infusion_session');

        if ($infusionSession) {
            $patient = Patient::where('identifier', $infusionSession['identifier'])->first();
            if ($patient) {
                return [
                    'identifier' => $patient->identifier,
                    'name' => $patient->name,
                    'age' => $patient->age,
                    'location' => $patient->location,
                    'durasi_infus_jam' => $infusionSession['durasi_infus_jam'],
                ];
            }
        }

        return null;
    }

    public function index()
    {
        session()->forget('skip_autoload');

        $infusionSession = session()->get('infusion_session');

        if (!$infusionSession) {
            $infusionSession = InfusionSession::with('device', 'patient')
                ->whereNull('id_perangkat_infusee')
                ->orderBy('timestamp_infus', 'desc')
                ->first();

            if ($infusionSession) {
                $infusionSession->load('patient');
                session()->put('infusion_session', $infusionSession);
            }
        }

        $patientData = $this->getPatientData();

        $usedDeviceIds = InfusionSession::whereNotNull('id_perangkat_infusee')
            ->whereHas('device', function ($query) {
                $query->where('status', 'unavailable');
            })
            ->pluck('id_perangkat_infusee')
            ->filter();

        $devices = Device::select('id_perangkat_infusee', 'alamat_ip_infusee', 'status', 'status_device')
            ->where('status', 'available')
            ->where('status_device', 'online')
            ->get();

        return view('devices.index', compact('devices', 'infusionSession', 'patientData'));
    }

    public function assign(Request $request)
    {
        $data = $request->validate([
            'id_perangkat_infusee' => 'required|string|exists:device_infusee,id_perangkat_infusee',
        ]);

        try {
            $infusion = InfusionSession::with('patient')
                ->whereNotNull('id_session')
                ->whereNull('id_perangkat_infusee')
                ->orderBy('timestamp_infus', 'desc')
                ->firstOrFail();

            $device = Device::where('id_perangkat_infusee', $data['id_perangkat_infusee'])
                ->where('status', 'available')
                ->where('status_device', 'online')
                ->firstOrFail();

            \DB::beginTransaction();

            // Update infusion session
            $infusion->update([
                'id_perangkat_infusee' => $data['id_perangkat_infusee'],
                'updated_at' => now(),
                'status_sesi_infus' => 'active',
            ]);

            // Update device status
            $affectedRows = Device::where('id_perangkat_infusee', $data['id_perangkat_infusee'])
                ->update(['status' => 'unavailable']);

            if ($affectedRows === 0) {
                throw new \Exception('Gagal memperbarui status perangkat');
            }

            // 1. Kirim perintah startMonitoring ke ESP32
            $iotResponse = Http::timeout(10)
                ->post('http://'.$device->alamat_ip_infusee.':5005/start-monitoring', [
                    'id_session' => (string)$infusion->id_session,
                    'command' => 'start'
                ]);

            if (!$iotResponse->successful()) {
                throw new \Exception('Gagal memulai monitoring di perangkat: ' . $iotResponse->body());
            }

            // 2. Buat record monitoring awal
            $monitoringController = new MonitoringController();
            $monitoringResult = $monitoringController->storeInternal(
                $infusion->id_session,
                $infusion->berat_awal ?? 0,
                0
            );

            if (isset($monitoringResult['error'])) {
                throw new \Exception('Gagal membuat catatan monitoring awal: ' . $monitoringResult['error']);
            }

            // 3. Catat aktivitas
            HistoryActivity::create([
                'id_session' => $infusion->id_session,
                'identifier_pract' => auth()->guard('pegawai')->user()->identifier_pract,
                'aktivitas' => 'Memulai sesi infus',
            ]);

            \DB::commit();

            session()->forget('infusion_session');

            return response()->json([
                'success' => true,
                'message' => 'Perangkat berhasil dipilih dan monitoring dimulai!',
                'id_perangkat_infusee' => $data['id_perangkat_infusee'],
                'iot_response' => $iotResponse->json()
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \DB::rollBack();
            return response()->json([
                'error' => 'Data tidak ditemukan: ' . $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'error' => 'Gagal memulai sesi infus: ' . $e->getMessage()
            ], 500);
        }
    }

    public function clear($id_session)
    {
        $session = InfusionSession::where('id_session', $id_session)->first();

        if (!$session) {
            return redirect()->back()->with('error', 'Data tidak ditemukan.');
        }

        $session->delete();

        if (session()->get('infusion_session.id_session') == $id_session) {
            session()->forget('infusion_session');
        }

        return redirect()->route('devices.index')->with('success', 'Data pasien berhasil dihapus.');
    }

}
