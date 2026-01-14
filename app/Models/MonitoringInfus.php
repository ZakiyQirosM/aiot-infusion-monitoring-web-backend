<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitoringInfus extends Model
{
    use HasFactory;

    protected $table = 'table_monitoring_infus';
    public $timestamps = false;

    protected $fillable = [
        'id_session',
        'berat_total',
        'berat_sekarang',
        'tpm_sensor',
        'tpm_prediksi',
        'created_at',
        'waktu',
        'wa_notif_sent',
    ];
    
    public function infusionsession()
    {
        return $this->belongsTo(InfusionSession::class, 'id_session', 'id_session');
    }

}

