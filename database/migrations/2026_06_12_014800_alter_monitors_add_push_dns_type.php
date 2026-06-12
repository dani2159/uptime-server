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
        \DB::statement("ALTER TABLE monitors MODIFY type ENUM('http','ping','keyword','tcp','push','dns') NOT NULL DEFAULT 'http'");

        Schema::table('monitors', function (Blueprint $table) {
            // Push: token unik untuk menerima heartbeat dari cron/script eksternal
            $table->string('push_token', 64)->nullable()->unique()->after('tcp_port');
            $table->timestamp('last_push_at')->nullable()->after('push_token');
            // DNS: expected value hasil resolve
            $table->string('dns_resolve_type', 10)->default('A')->after('last_push_at'); // A, AAAA, CNAME, MX
            $table->string('dns_expected_value')->nullable()->after('dns_resolve_type');
        });
    }

    public function down(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->dropColumn(['push_token', 'last_push_at', 'dns_resolve_type', 'dns_expected_value']);
        });
        \DB::statement("ALTER TABLE monitors MODIFY type ENUM('http','ping','keyword','tcp') NOT NULL DEFAULT 'http'");
    }
};
