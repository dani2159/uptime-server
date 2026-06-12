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
        Schema::table('monitors', function (Blueprint $table) {
            $table->integer('retry_count')->default(1)->after('timeout');
            $table->integer('current_retries')->default(0)->after('retry_count');
            $table->decimal('uptime_24h', 5, 2)->nullable()->after('uptime_percentage');
            $table->decimal('uptime_7d', 5, 2)->nullable()->after('uptime_24h');
            $table->decimal('uptime_30d', 5, 2)->nullable()->after('uptime_7d');
            $table->date('ssl_expiry_at')->nullable()->after('uptime_30d');
            $table->boolean('ssl_valid')->nullable()->after('ssl_expiry_at');
            $table->integer('ssl_days_remaining')->nullable()->after('ssl_valid');
        });
    }

    public function down(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->dropColumn([
                'retry_count', 'current_retries',
                'uptime_24h', 'uptime_7d', 'uptime_30d',
                'ssl_expiry_at', 'ssl_valid', 'ssl_days_remaining',
            ]);
        });
    }
};
