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
        Schema::create('customer_type_colors', function (Blueprint $table) {
            $table->id();
            $table->string('customer_type');
            $table->string('color');                       
            $table->timestamps();
            $table->string('logo_path'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_type_colors');
    }
};
