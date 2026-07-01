<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        \DB::statement("ALTER TABLE monitors MODIFY type ENUM('http','ping','keyword','tcp','push','dns','whois','docker','cron','database') NOT NULL DEFAULT 'http'");
    }

    public function down(): void
    {
        \DB::statement("ALTER TABLE monitors MODIFY type ENUM('http','ping','keyword','tcp','push','dns') NOT NULL DEFAULT 'http'");
    }
};
