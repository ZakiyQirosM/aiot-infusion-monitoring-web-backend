<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EndpointSetting extends Model
{
    protected $fillable = [
        'service',
        'base_url',
        'api_key',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Ambil endpoint aktif berdasarkan service
     */
    public static function active(string $service): ?self
    {
        return self::where('service', $service)
            ->where('is_active', true)
            ->first();
    }
}
