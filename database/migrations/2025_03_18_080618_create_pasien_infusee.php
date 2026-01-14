<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pasien_infusee', function (Blueprint $table) {
            $table->string('identifier')->primary();
            $table->string('name');
            $table->unsignedTinyInteger('age');
            $table->string('gender', 10);
            $table->string('location');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pasien_infusee');
    }
};
