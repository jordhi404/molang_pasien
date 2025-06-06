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
        Schema::connection('pgsql')->create('ip_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address')->unique();
            $table->string('unit');
            $table->timestamps();
        });

        // Seed initial data
        DB::table('ip_mappings')->insert([
            ['ip_address' => '10.100.18.154', 'unit' => 'TEKNOLOGI INFORMASI'],
            ['ip_address' => '10.100.18.25', 'unit' => 'RUANG ASA'],
            ['ip_address' => '127.0.0.1', 'unit' => 'TEKNOLOGI INFORMASI']
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ip_mappings');
    }
};
