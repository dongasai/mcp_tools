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
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('identifier')->unique();
            $table->enum('status', ['active', 'inactive', 'suspended', 'pending'])->default('pending');
            $table->text('description')->nullable();
            $table->json('capabilities')->nullable();
            $table->json('configuration')->nullable();
            $table->string('access_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->json('allowed_projects')->nullable();
            $table->json('allowed_actions')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index('identifier');
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
