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
        Schema::create('bed_cleaning_records', function (Blueprint $table) {
            $table->id();
            $table->string('BedCode');
            $table->string('ServiceUnitName');
            $table->timestamp('LastUnoccupiedDate');
            $table->timestamp('BedUnoccupiedInReality')->nullable();
            $table->timestamp('ExpectedDoneCleaning')->nullable();
            $table->timestamp('DoneCleaningInReality')->nullable();
            $table->integer('CleaningDuration')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bed_cleaning_records');
    }
};
