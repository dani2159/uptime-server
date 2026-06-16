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
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_id')->constrained()->cascadeOnDelete();
            $table->dateTime('started_at');
            $table->dateTime('resolved_at')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->text('note')->nullable();
            $table->boolean('is_manual')->default(false);
            $table->timestamps();

            $table->index(['monitor_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
