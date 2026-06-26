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
        Schema::table('sla_contracts', function (Blueprint $table) {
            $table->date('period_start')->nullable()->after('target_uptime');
            $table->date('period_end')->nullable()->after('period_start');
            $table->integer('downtime_budget_min')->nullable()->after('period_end');
            $table->text('notes')->nullable()->after('downtime_budget_min');
        });
    }

    public function down(): void
    {
        Schema::table('sla_contracts', function (Blueprint $table) {
            $table->dropColumn(['period_start', 'period_end', 'downtime_budget_min', 'notes']);
        });
    }
};
