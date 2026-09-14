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
        Schema::create('business_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('business_name');
            $table->string('owner_name')->nullable();
            $table->string('business_type')->nullable(); // LLC, Sole Proprietor, Corporation, Partnership
            $table->string('phone_number')->nullable();
            $table->string('tax_id')->nullable(); // EIN / SSN
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip_code', 20)->nullable();
            $table->string('cover_photo')->nullable();
            $table->unsignedTinyInteger('years_experience')->nullable();
            $table->date('member_since')->nullable();
            $table->boolean('is_elite')->default(false);
            $table->boolean('is_veteran_owned')->default(false);
            $table->boolean('is_id_verified')->default(false);
            $table->boolean('is_available_today')->default(false);
            $table->boolean('is_license_verified')->default(false);
            $table->string('license_number')->nullable();
            $table->string('business_hours')->nullable();
            $table->unsignedInteger('service_radius')->nullable(); // radius in miles
            $table->json('languages')->nullable();
            $table->json('service_areas')->nullable();
            $table->json('gallery_images')->nullable();
            $table->json('video_urls')->nullable();
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->decimal('avg_rating', 3, 2)->default(0.00);
            $table->unsignedInteger('review_count')->default(0);
            $table->text('bio')->nullable();
            $table->string('website_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->unsignedTinyInteger('onboarding_step')->default(1);
            $table->boolean('onboarding_completed')->default(false);
            $table->timestamp('terms_accepted_at')->nullable();
            $table->timestamp('id_me_verified_at')->nullable();
            $table->json('id_me_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_profiles');
    }
};
