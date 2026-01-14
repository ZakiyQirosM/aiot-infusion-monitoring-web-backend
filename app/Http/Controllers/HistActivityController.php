<?php

namespace App\Http\Controllers;

use App\Models\HistoryActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HistActivityController extends Controller
{
    public function index(Request $request)
    {
        $sort = $request->get('sort', 'desc');
        $search = $request->get('search', '');

        $logs = HistoryActivity::with(['pegawai', 'session.patient'])
            ->where(function ($query) use ($search) {
                $query->whereHas('pegawai', function ($query) use ($search) {
                    $query->where('name_pract', 'like', '%' . $search . '%');
                })
                ->orWhereHas('session.patient', function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('created_at', $sort)
            ->paginate(20);

        return view('activity.index', compact('logs'));
    }
}
