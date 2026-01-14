<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHistoryActivityTable extends Migration
{
    public function up()
    {
        Schema::create('history_activity', function (Blueprint $table) {
            $table->id('id_hist_act');
            $table->unsignedBigInteger('id_session');
            $table->string('identifier_pract');
            $table->text('aktivitas')->default('active');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('id_session')
                ->references('id_session')
                ->on('infusion_sessions')    
                ->onDelete('cascade');

            $table->foreign('identifier_pract')
                ->references('identifier_pract')
                ->on('practitioner')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('history_activity');
    }
}
