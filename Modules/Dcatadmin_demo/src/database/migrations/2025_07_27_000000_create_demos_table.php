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
        Schema::create('dcatadmin2demo_demos', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('名称');
            $table->text('description')->nullable()->comment('描述');
            $table->boolean('status')->default(true)->comment('状态');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dcatadmin2demo_demos');
    }
};