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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable()->unique();
            $table->string('tagline')->nullable();
            $table->string('badge')->nullable(); // e.g. 'MOST POPULAR'
            $table->decimal('monthly_price', 10, 2);
            $table->decimal('annual_price', 10, 2)->nullable();
            $table->json('features');
            $table->unsignedInteger('service_limit')->nullable(); // null = unlimited
            $table->unsignedInteger('gallery_limit')->nullable();
            $table->unsignedInteger('video_limit')->nullable();
            $table->boolean('is_popular')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
