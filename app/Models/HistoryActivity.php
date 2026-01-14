<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoryActivity extends Model
{
    protected $table = 'history_activity';
    protected $primaryKey = 'id_hist_act';
    public $timestamps = false;

    protected $fillable = [
        'id_session', 'identifier_pract', 'aktivitas', 'created_at'
    ];

    public function pegawai()
    {
        return $this->belongsTo(Practitioner::class, 'identifier_pract', 'identifier_pract');
    }

    public function session()
    {
        return $this->belongsTo(InfusionSession::class, 'id_session', 'id_session');
    }
}
