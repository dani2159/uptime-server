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
        Schema::dropIfExists('api_health_pages');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('api_health_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(true);
            $table->json('service_keys')->nullable();
            $table->timestamps();
        });
    }
};
