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
        // Services table (Global)
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', ['internal', 'external'])->default('external');
            $table->string('endpoint')->nullable();
            $table->enum('auth_type', ['none', 'bearer', 'basic', 'oauth', 'api_key'])->default('none');
            $table->text('description')->nullable();
            $table->json('default_config')->nullable();
            $table->json('headers')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('timeout')->default(30); // seconds
            $table->integer('retry_attempts')->default(3);
            $table->timestamps();

            $table->index('type');
            $table->index('is_active');
        });

        // Service configs per tenant
        Schema::create('service_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->json('config')->nullable();
            $table->json('credentials')->nullable(); // encrypted
            $table->boolean('is_enabled')->default(true);
            $table->integer('rate_limit')->nullable(); // requests per minute
            $table->timestamps();

            $table->unique(['tenant_id', 'service_id']);
            $table->index('is_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_configs');
        Schema::dropIfExists('services');
    }
};
