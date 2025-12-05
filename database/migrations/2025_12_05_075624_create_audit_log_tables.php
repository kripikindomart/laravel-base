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
        // Activity Logs (User actions & model changes)
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->nullableMorphs('subject', 'subject');
            $table->nullableMorphs('causer', 'causer');
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
            $table->index('log_name');
        });

        // Service Logs (API calls & service interactions)
        Schema::create('service_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('service_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('service_name');
            $table->enum('service_type', ['internal', 'external']);
            $table->string('method', 10);
            $table->string('endpoint', 500);
            $table->json('request_headers')->nullable();
            $table->json('request_body')->nullable();
            $table->integer('response_status')->nullable();
            $table->json('response_headers')->nullable();
            $table->json('response_body')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at');

            $table->index(['tenant_id', 'created_at']);
            $table->index(['service_id', 'created_at']);
            $table->index('response_status');
        });

        // Error Logs (Application errors & exceptions)
        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('error_type');
            $table->text('error_message');
            $table->string('error_code', 50)->nullable();
            $table->string('file', 500)->nullable();
            $table->integer('line')->nullable();
            $table->text('trace')->nullable();
            $table->json('context')->nullable();
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('created_at');

            $table->index(['tenant_id', 'severity', 'created_at']);
            $table->index('is_resolved');
            $table->index('error_type');
        });

        // Service Health Logs (Service monitoring & uptime)
        Schema::create('service_health_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['up', 'down', 'degraded'])->default('up');
            $table->integer('response_time_ms')->nullable();
            $table->decimal('cpu_usage', 5, 2)->nullable();
            $table->decimal('memory_usage', 5, 2)->nullable();
            $table->decimal('error_rate', 5, 2)->nullable();
            $table->json('details')->nullable();
            $table->timestamp('checked_at');

            $table->index(['service_id', 'checked_at']);
            $table->index(['tenant_id', 'status']);
        });

        // Login Logs (Authentication & security)
        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('email');
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->enum('status', ['success', 'failed', 'blocked'])->default('failed');
            $table->string('failure_reason')->nullable();
            $table->json('location')->nullable(); // country, city, etc.
            $table->timestamp('created_at');

            $table->index(['tenant_id', 'status', 'created_at']);
            $table->index(['ip_address', 'created_at']);
            $table->index('user_id');
        });

        // Super Admin Logs (Super admin actions)
        Schema::create('super_admin_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->string('action');
            $table->foreignId('target_tenant_id')->nullable()->constrained('tenants')->onDelete('set null');
            $table->text('description');
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at');

            $table->index(['admin_id', 'created_at']);
            $table->index(['target_tenant_id', 'created_at']);
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('super_admin_logs');
        Schema::dropIfExists('login_logs');
        Schema::dropIfExists('service_health_logs');
        Schema::dropIfExists('error_logs');
        Schema::dropIfExists('service_logs');
        Schema::dropIfExists('activity_logs');
    }
};
