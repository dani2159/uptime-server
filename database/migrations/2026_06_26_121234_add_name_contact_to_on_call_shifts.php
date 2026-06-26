<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('on_call_shifts', function (Blueprint $table) {
            $table->string('name', 100)->nullable()->after('schedule_id');
            $table->string('contact_info', 255)->nullable()->after('channel_id');
        });

        // backfill name from label
        DB::statement('UPDATE on_call_shifts SET name = label WHERE name IS NULL');
    }

    public function down(): void
    {
        Schema::table('on_call_shifts', function (Blueprint $table) {
            $table->dropColumn(['name', 'contact_info']);
        });
    }
};
