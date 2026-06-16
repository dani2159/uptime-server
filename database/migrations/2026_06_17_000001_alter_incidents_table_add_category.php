<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE incidents DROP FOREIGN KEY incidents_monitor_id_foreign');
        DB::statement('ALTER TABLE incidents MODIFY monitor_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE incidents ADD CONSTRAINT incidents_monitor_id_foreign FOREIGN KEY (monitor_id) REFERENCES monitors(id) ON DELETE SET NULL');

        Schema::table('incidents', function (Blueprint $table) {
            $table->string('category', 20)->default('monitor_downtime')->after('monitor_id');
            $table->string('severity', 10)->default('medium')->after('category');
            $table->string('title')->nullable()->after('severity');
            $table->string('reporter_name')->nullable()->after('note');
            $table->string('reporter_contact')->nullable()->after('reporter_name');
            $table->index('category');
        });

        DB::table('incidents')->update(['category' => 'monitor_downtime']);
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropColumn(['category', 'severity', 'title', 'reporter_name', 'reporter_contact']);
        });

        DB::statement('ALTER TABLE incidents DROP FOREIGN KEY incidents_monitor_id_foreign');
        DB::statement('ALTER TABLE incidents MODIFY monitor_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE incidents ADD CONSTRAINT incidents_monitor_id_foreign FOREIGN KEY (monitor_id) REFERENCES monitors(id) ON DELETE CASCADE');
    }
};
