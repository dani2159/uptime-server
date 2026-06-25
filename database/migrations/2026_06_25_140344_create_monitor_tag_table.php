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
        Schema::create('monitor_tag', function (Blueprint $table) {
            $table->unsignedBigInteger('monitor_id');
            $table->unsignedBigInteger('tag_id');
            $table->primary(['monitor_id', 'tag_id']);
            $table->foreign('monitor_id')->references('id')->on('monitors')->cascadeOnDelete();
            $table->foreign('tag_id')->references('id')->on('tags')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitor_tag');
    }
};
