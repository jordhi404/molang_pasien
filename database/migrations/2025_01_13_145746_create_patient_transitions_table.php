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
        Schema::create('patient_transitions', function (Blueprint $table) {
            $table->id();
            $table->string('MedicalNo');
            $table->string('PatientName');
            $table->string('ServiceUnitName');
            $table->string('CustomerType');
            $table->string('ChargeClassName');
            $table->string('RencanaPulang');
            $table->timestamp('Keperawatan')->nullable();
            $table->timestamp('Farmasi')->nullable();
            $table->timestamp('Kasir')->nullable();
            $table->datetime('SelesaiBilling')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_transitions');
    }
};
