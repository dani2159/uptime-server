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
        // Ubah enum type agar mendukung tipe baru
        \DB::statement("ALTER TABLE monitors MODIFY type ENUM('http','ping','keyword','tcp') NOT NULL DEFAULT 'http'");

        Schema::table('monitors', function (Blueprint $table) {
            // Keyword yang dicari di body response (untuk tipe keyword)
            $table->string('keyword')->nullable()->after('expected_status');
            // Host:port untuk tipe tcp (misal "192.168.1.1:3306")
            $table->string('tcp_host')->nullable()->after('keyword');
            $table->integer('tcp_port')->nullable()->after('tcp_host');
        });
    }

    public function down(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->dropColumn(['keyword', 'tcp_host', 'tcp_port']);
        });
        \DB::statement("ALTER TABLE monitors MODIFY type ENUM('http','ping') NOT NULL DEFAULT 'http'");
    }
};
