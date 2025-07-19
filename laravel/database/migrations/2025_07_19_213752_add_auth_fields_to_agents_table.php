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
        Schema::table('agents', function (Blueprint $table) {
            $table->string('access_token')->nullable()->after('configuration');
            $table->timestamp('token_expires_at')->nullable()->after('access_token');
            $table->json('allowed_projects')->nullable()->after('token_expires_at');
            $table->json('allowed_actions')->nullable()->after('allowed_projects');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn(['access_token', 'token_expires_at', 'allowed_projects', 'allowed_actions']);
        });
    }
};
