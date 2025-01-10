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
        Schema::create('order_types', function (Blueprint $table) {
            $table->id();
            $table->string('code_prefix')->unique();
            $table->string('icon_path');
            $table->timestamps();
        });

        // Seed initial data
        DB::table('order_types')->insert([
            ['code_prefix' => 'LAC', 'icon_path' => '/Logo_img/microscope.png'],
            ['code_prefix' => 'RAC', 'icon_path' => '/Logo_img/radiation.png'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_types');
    }
};
