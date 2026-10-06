<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // e.g. {"quotes": true, "messages": true, "payments": true}
            $table->json('notification_preferences')->nullable()->after('avatar');
        });

        Schema::table('business_profiles', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('business_name');
            $table->string('logo')->nullable()->after('cover_photo');
            $table->string('tagline')->nullable()->after('bio');
            $table->string('business_category')->nullable()->after('business_type');
            $table->string('business_email')->nullable()->after('phone_number');
            $table->string('street_address')->nullable()->after('tax_id');
            $table->string('insurance_company')->nullable()->after('license_number');
            $table->string('insurance_policy_number')->nullable()->after('insurance_company');
            $table->boolean('offers_emergency_service')->default(false)->after('is_available_today');
            $table->string('custom_domain')->nullable()->unique()->after('website_url');
            $table->string('youtube_url')->nullable()->after('twitter_url');
            $table->string('tiktok_url')->nullable()->after('youtube_url');
            $table->string('invoice_title')->default('INVOICE')->after('tiktok_url');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('notification_preferences'));

        Schema::table('business_profiles', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropUnique(['custom_domain']);
            $table->dropColumn([
                'slug', 'logo', 'tagline', 'business_category', 'business_email', 'street_address',
                'insurance_company', 'insurance_policy_number', 'offers_emergency_service',
                'custom_domain', 'youtube_url', 'tiktok_url', 'invoice_title',
            ]);
        });
    }
};
