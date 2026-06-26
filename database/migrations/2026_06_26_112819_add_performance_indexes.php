<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->index('is_active',   'idx_monitors_is_active');
            $table->index('last_status', 'idx_monitors_last_status');
            $table->index(['is_active', 'last_status'], 'idx_monitors_active_status');
            $table->index('type',        'idx_monitors_type');
        });

        Schema::table('monitor_logs', function (Blueprint $table) {
            $table->index(['monitor_id', 'checked_at'], 'idx_logs_monitor_checked');
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->index(['monitor_id', 'status'], 'idx_incidents_monitor_status');
        });
    }

    public function down(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->dropIndex('idx_monitors_is_active');
            $table->dropIndex('idx_monitors_last_status');
            $table->dropIndex('idx_monitors_active_status');
            $table->dropIndex('idx_monitors_type');
        });
        Schema::table('monitor_logs', function (Blueprint $table) {
            $table->dropIndex('idx_logs_monitor_checked');
        });
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropIndex('idx_incidents_monitor_status');
        });
    }
};
