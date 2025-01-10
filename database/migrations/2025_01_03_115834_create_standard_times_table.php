<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('standard_times', function (Blueprint $table) {
            $table->id();
            $table->string('unit')->unique();
            $table->integer('standard_time')->comment('Time in minutes');
            
        });

        // Seed initial data
        DB::table('standard_times')->insert([
            ['keterangan' => 'TungguKeperawatan', 'standard_time' => 5],
            ['keterangan' => 'TungguFarmasi', 'standard_time' => 30],
            ['keterangan' => 'CS', 'standard_time' => 20],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('standard_times');
    }
};
