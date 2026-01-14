<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('table_monitoring_infus', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_session');
            $table->float('berat_total');
            $table->float('berat_sekarang')->nullable();
            $table->integer('tpm_sensor');
            $table->float('tpm_prediksi')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('waktu')->useCurrent()->useCurrentOnUpdate();
            $table->boolean('wa_notif_sent')->default(false);

            $table->foreign('id_session')
                  ->references('id_session')
                  ->on('infusion_sessions')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_monitoring_infus');
    }
};

