<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    protected $table = 'pasien_infusee';

    protected $primaryKey = 'identifier';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'identifier',
        'name',
        'gender',
        'age',
        'location',
    ];

    public function infusionSessions()
    {
        return $this->hasMany(InfusionSession::class, 'identifier', 'identifier');
    }

    public function device()
    {
        return $this->hasMany(Device::class, 'identifier', 'identifier');
    }

    public $timestamps = false;

}
