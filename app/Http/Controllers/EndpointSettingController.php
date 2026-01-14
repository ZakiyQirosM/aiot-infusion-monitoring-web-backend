<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EndpointSetting;
use Illuminate\Http\Request;

class EndpointSettingController extends Controller
{
    public function index()
    {
        $endpoints = EndpointSetting::all();
        return view('endpoint-setting.index', compact('endpoints'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'service' => 'required|in:rme,ai',
            'base_url' => 'required|string',
            'api_key' => 'nullable|string',
        ]);

        EndpointSetting::create($request->all());

        return back()->with('success', 'Endpoint berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $endpoint = EndpointSetting::findOrFail($id);

        // Tambahkan validasi sederhana
        $request->validate([
            'base_url' => 'required|string',
            'api_key'  => 'nullable|string',
        ]);

        $endpoint->update([
            'base_url'  => $request->base_url,
            'api_key'   => $request->api_key,
            // is_active akan bernilai true jika checkbox dicentang, false jika tidak
            'is_active' => $request->has('is_active'),
        ]);

        return back()->with('success', 'Endpoint berhasil diperbarui');
    }

    public function destroy($id)
    {
        EndpointSetting::findOrFail($id)->delete();
        return back()->with('success', 'Endpoint dihapus');
    }
}
