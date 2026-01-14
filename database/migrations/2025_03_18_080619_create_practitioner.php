<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('practitioner', function (Blueprint $table) {
            $table->string('identifier_pract')->primary();
            $table->string('name_pract');
            $table->string('role_pract')->default('perawat');
            $table->string('password');
            $table->string('no_wa');
            $table->timestamps();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practitioner');
    }
};
