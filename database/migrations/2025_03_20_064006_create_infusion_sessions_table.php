<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('infusion_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('id_session')->primary()->autoIncrement();
            $table->string('identifier_pract');
            $table->string('identifier');
            $table->string('id_perangkat_infusee')->nullable();
            $table->integer('durasi_infus_jam');
            $table->timestamp('timestamp_infus')->nullable();
            $table->timestamps();
            $table->string('status_sesi_infus')->default('active');
        
            $table->foreign('identifier_pract')
                ->references('identifier_pract')
                ->on('practitioner')
                ->onDelete('cascade');
                
            $table->foreign('identifier')
                ->references('identifier')
                ->on('pasien_infusee')    
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('id_perangkat_infusee')
                ->references('id_perangkat_infusee')
                ->on('device_infusee')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('infusion_sessions');
    }
};
