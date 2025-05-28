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
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('agent_id')->unique(); // Unique agent identifier
            $table->string('name'); // Human-readable name
            $table->string('type')->nullable(); // Agent type (claude-3.5-sonnet, gpt-4, etc.)
            $table->string('access_token', 500); // Access token for authentication
            $table->json('permissions')->nullable(); // Agent permissions
            $table->json('allowed_projects')->nullable(); // Project IDs this agent can access
            $table->json('allowed_actions')->nullable(); // Actions this agent can perform
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->timestamp('last_active_at')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->index('agent_id');
            $table->index(['user_id', 'status']);
            $table->index('status');
            $table->index('last_active_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
