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
        Schema::create('pagespeed_monitors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->enum('strategy', ['mobile', 'desktop'])->default('mobile');
            $table->integer('interval_minutes')->default(60);
            $table->boolean('is_active')->default(true);
            $table->string('api_key')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagespeed_monitors');
    }
};
