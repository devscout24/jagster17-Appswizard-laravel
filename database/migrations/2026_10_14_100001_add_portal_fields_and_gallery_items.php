<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fields required by the contractor & customer portal screens (Settings,
     * Business Profile, Banner, Website, Invoice line items) plus the gallery table.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('notification_preferences')->nullable()->after('status');
        });

        Schema::table('business_profiles', function (Blueprint $table) {
            $table->foreignId('primary_category_id')->nullable()->after('business_type')->constrained('categories')->nullOnDelete();
            $table->string('address')->nullable()->after('tax_id');
            $table->string('logo')->nullable()->after('zip_code');
            $table->string('license_document')->nullable()->after('license_number');
            $table->string('insurance_company')->nullable()->after('license_document');
            $table->string('insurance_policy_number')->nullable()->after('insurance_company');
            $table->boolean('emergency_service')->default(false)->after('insurance_policy_number');
            $table->string('banner_image')->nullable()->after('cover_photo');
            $table->string('banner_headline')->nullable()->after('banner_image');
            $table->string('slug')->nullable()->unique()->after('business_name');
            $table->string('custom_domain')->nullable()->unique()->after('website_url');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->json('items')->nullable()->after('platform_fee');
            $table->string('bill_to_address')->nullable()->after('items');
            $table->string('payment_method')->nullable()->after('paid_at');
            $table->timestamp('approved_at')->nullable()->after('payment_method');
            $table->timestamp('sent_at')->nullable()->after('approved_at');
        });

        Schema::create('gallery_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('users')->cascadeOnDelete();
            $table->string('image');
            $table->string('caption')->nullable();
            $table->enum('category', ['before_after', 'project_photos', 'team', 'equipment'])->default('project_photos');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['business_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gallery_items');

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['items', 'bill_to_address', 'payment_method', 'approved_at', 'sent_at']);
        });

        Schema::table('business_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('primary_category_id');
            $table->dropUnique(['slug']);
            $table->dropUnique(['custom_domain']);
            $table->dropColumn([
                'address', 'logo', 'license_document', 'insurance_company', 'insurance_policy_number',
                'emergency_service', 'banner_image', 'banner_headline', 'slug', 'custom_domain',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('notification_preferences');
        });
    }
};
