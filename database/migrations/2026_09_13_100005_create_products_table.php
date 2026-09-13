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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->json('gallery_images')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('unit')->nullable();
            $table->boolean('is_elite_tier')->default(false);
            $table->boolean('is_trending')->default(false);
            $table->boolean('in_stock')->default(true);
            $table->unsignedInteger('stock_quantity')->default(10);
            $table->string('sku')->nullable();
            $table->string('warranty')->nullable();
            $table->string('shipping_info')->nullable();
            $table->string('external_url')->nullable();
            $table->decimal('rating', 3, 2)->default(0.00);
            $table->unsignedInteger('review_count')->default(0);
            $table->json('features')->nullable();
            $table->enum('status', ['active', 'off'])->default('active');
            $table->timestamps();

            $table->index(['business_id', 'status']);
            $table->index('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
