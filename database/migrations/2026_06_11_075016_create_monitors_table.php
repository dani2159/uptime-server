<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->enum('type', ['http', 'ping'])->default('http');
            $table->boolean('is_active')->default(true);
            $table->integer('check_interval')->default(5);
            $table->integer('timeout')->default(10);
            $table->string('expected_status', 10)->default('200');
            $table->enum('last_status', ['up', 'down', 'pending'])->default('pending');
            $table->integer('last_response_time')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_down_at')->nullable();
            $table->decimal('uptime_percentage', 5, 2)->default(100.00);
            $table->json('notification_channels')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitors');
    }
};
