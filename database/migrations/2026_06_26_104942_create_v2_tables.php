<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // API Tokens
        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->string('abilities')->default('read'); // read|write|admin
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // Monitor Dependencies
        Schema::create('monitor_dependencies', function (Blueprint $table) {
            $table->unsignedBigInteger('monitor_id');
            $table->unsignedBigInteger('depends_on_id');
            $table->primary(['monitor_id', 'depends_on_id']);
            $table->foreign('monitor_id')->references('id')->on('monitors')->onDelete('cascade');
            $table->foreign('depends_on_id')->references('id')->on('monitors')->onDelete('cascade');
        });

        // On-Call Schedules
        Schema::create('on_call_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('on_call_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('on_call_schedules')->onDelete('cascade');
            $table->foreignId('channel_id')->constrained('notification_channels')->onDelete('cascade');
            $table->string('label')->nullable();
            $table->unsignedTinyInteger('day_of_week')->nullable(); // 0=Sun,1=Mon..6=Sat; null=every day
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();
        });

        // SLA Contracts
        Schema::create('sla_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->decimal('target_uptime', 5, 2)->default(99.90); // e.g. 99.90 %
            $table->unsignedInteger('max_downtime_seconds')->nullable(); // budget per period
            $table->string('period', 20)->default('monthly'); // monthly|weekly|yearly
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Incident Post-Mortems
        Schema::create('incident_post_mortems', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->onDelete('cascade');
            $table->string('title')->nullable();
            $table->text('timeline')->nullable();
            $table->text('root_cause')->nullable();
            $table->text('impact')->nullable();
            $table->text('action_items')->nullable();
            $table->string('severity', 20)->default('medium');
            $table->string('author')->nullable();
            $table->timestamps();
        });

        // Webhook Inbound Events
        Schema::create('webhook_inbound_events', function (Blueprint $table) {
            $table->id();
            $table->string('source', 50)->nullable(); // grafana|zabbix|prometheus|custom
            $table->string('token', 32)->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->json('last_payload')->nullable();
            $table->string('last_status', 20)->nullable();
            $table->timestamp('last_received_at')->nullable();
            $table->unsignedBigInteger('monitor_id')->nullable(); // attach to monitor if desired
            $table->timestamps();
        });

        // Monitor Templates
        Schema::create('monitor_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category', 50)->nullable(); // database|web|api|health|satusehat
            $table->string('icon', 50)->nullable();
            $table->json('config'); // full monitor config as JSON
            $table->boolean('is_builtin')->default(false);
            $table->timestamps();
        });

        // Business Hours (global)
        Schema::create('business_hours', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('day_of_week'); // 0=Sun..6=Sat
            $table->time('open_time')->default('08:00:00');
            $table->time('close_time')->default('17:00:00');
            $table->boolean('is_working_day')->default(true);
            $table->timestamps();
        });

        // Chatbot Acknowledgements
        Schema::create('alert_acknowledgements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->onDelete('cascade');
            $table->string('acked_by')->nullable();
            $table->string('channel_type', 20)->nullable(); // telegram|whatsapp
            $table->text('note')->nullable();
            $table->timestamp('acked_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_acknowledgements');
        Schema::dropIfExists('business_hours');
        Schema::dropIfExists('monitor_templates');
        Schema::dropIfExists('webhook_inbound_events');
        Schema::dropIfExists('incident_post_mortems');
        Schema::dropIfExists('sla_contracts');
        Schema::dropIfExists('on_call_shifts');
        Schema::dropIfExists('on_call_schedules');
        Schema::dropIfExists('monitor_dependencies');
        Schema::dropIfExists('api_tokens');
    }
};
