<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InfusionSession extends Model
{
    use HasFactory;

    protected $table = 'infusion_sessions';

    protected $primaryKey = 'id_session';

    protected $fillable = [
        'identifier_pract',
        'identifier',
        'id_perangkat_infusee',
        'durasi_infus_jam',
        'timestamp_infus',
        'status_sesi_infus',
    ];

    protected $dates = ['timestamp_infus'];

    public function patient()
    {
        return $this->hasOne(Patient::class, 'identifier', 'identifier');
    }

    public function device()
    {
        return $this->hasMany(Device::class, 'id_perangkat_infusee', 'id_perangkat_infusee');
    }

    public function MonitoringInfus()
    {
        return $this->hasOne(MonitoringInfus::class, 'id_session', 'id_session');
    }

    public function activities()
    {
        return $this->hasMany(HistoryActivity::class, 'id_session', 'id_session');
    }

    public function pegawai()
    {
        return $this->belongsTo(Practitioner::class, 'identifier_pract', 'identifier_pract');
    }

}
