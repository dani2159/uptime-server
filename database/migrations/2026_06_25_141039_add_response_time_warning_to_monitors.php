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
            $table->unsignedInteger('response_time_warning')->nullable()->after('timeout')->comment('ms threshold for slow alert');
            $table->boolean('last_is_slow')->default(false)->after('response_time_warning');
        });
    }

    public function down(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->dropColumn(['response_time_warning', 'last_is_slow']);
        });
    }
};
