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
        Schema::create('pagespeed_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pagespeed_monitor_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('performance_score')->nullable();
            $table->tinyInteger('accessibility_score')->nullable();
            $table->tinyInteger('best_practices_score')->nullable();
            $table->tinyInteger('seo_score')->nullable();
            $table->decimal('cls', 5, 3)->nullable();
            $table->decimal('speed_index', 6, 2)->nullable();
            $table->decimal('fcp', 6, 2)->nullable();
            $table->decimal('lcp', 6, 2)->nullable();
            $table->integer('tbt')->nullable();
            $table->string('error_message')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagespeed_checks');
    }
};
