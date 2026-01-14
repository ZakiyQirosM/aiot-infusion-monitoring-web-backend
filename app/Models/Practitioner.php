<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Practitioner extends Authenticatable
{
    protected $table = 'practitioner';
    protected $primaryKey = 'identifier_pract';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'identifier_pract', 
        'name_pract', 
        'role_pract',
        'password', 
        'no_wa',
        'last_login_at',
        'last_activity_at',
    ];
    protected $hidden = ['password'];

    public function activities()
    {
        return $this->hasMany(HistoryActivity::class, 'identifier_pract', 'identifier_pract');
    }

    public function infusionSessions()
    {
        return $this->hasMany(InfusionSession::class, 'identifier_pract', 'identifier_pract');
    }
}


