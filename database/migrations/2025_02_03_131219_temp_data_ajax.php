<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('temp_data_ajax', function (Blueprint $table) {
            $table->id();
            $table->string('ServiceUnitName');
            $table->string('BedCode');
            $table->string('MedicalNo');
            $table->string('PatientName');
            $table->string('CustomerType');
            $table->string('RencanaPulang');
            $table->string('CatRencanaPulang')->nullable();
            $table->string('Keperawatan')->nullable();
            $table->string('TungguJangdik')->nullable();
            $table->string('TungguFarmasi')->nullable();
            $table->string('Keterangan');
            $table->datetime('Billing')->nullable();
            $table->datetime('Bayar')->nullable();
            $table->datetime('BolehPulang')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temp_data_ajax');
    }
};
