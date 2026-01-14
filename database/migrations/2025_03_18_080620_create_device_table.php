<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_infusee', function (Blueprint $table) {
            $table->string('id_perangkat_infusee')->primary();
            $table->string('alamat_ip_infusee');
            $table->enum('status', ['available', 'unavailable'])->default('unavailable');
            $table->timestamp('last_ping')->nullable();
            $table->enum('status_device', ['online', 'offline'])->default('online');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_infusee');
    }
};
