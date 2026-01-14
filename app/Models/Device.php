<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    protected $table = 'device_infusee';
    protected $primaryKey = 'id_perangkat_infusee';
    
    // TAMBAHKAN DUA BARIS INI
    public $incrementing = false; 
    protected $keyType = 'string';
    
    public $timestamps = false; // Sudah benar

    protected $fillable = [
        'id_perangkat_infusee',
        'alamat_ip_infusee',
        'status',
        'last_ping',
        'status_device',
    ];

    protected $casts = [
        'id_perangkat_infusee' => 'string',
    ];

    public function infusionSessions()
    {
        return $this->hasMany(InfusionSession::class, 'id_perangkat_infusee', 'id_perangkat_infusee');
    }
}