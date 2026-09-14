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
        Schema::create('quote_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->foreignId('business_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('project_title')->nullable();
            $table->text('description')->nullable();
            $table->json('attachments')->nullable();
            $table->string('budget_range')->nullable();
            $table->decimal('budget_min', 10, 2)->nullable();
            $table->decimal('budget_max', 10, 2)->nullable();
            $table->enum('timeline', ['asap', 'within_1_week', 'within_1_month', 'flexible'])->nullable();
            $table->string('street_address')->nullable();
            $table->string('zip_code', 20)->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->decimal('quote_amount', 10, 2)->nullable();
            $table->decimal('labor_cost', 10, 2)->nullable();
            $table->decimal('materials_cost', 10, 2)->nullable();
            $table->decimal('tax_cost', 10, 2)->nullable();
            $table->string('estimated_duration')->nullable();
            $table->text('contractor_notes')->nullable();
            $table->enum('status', ['new', 'pending', 'quoted', 'accepted', 'declined', 'rejected', 'expired'])->default('new');
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamps();

            $table->index(['business_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index('reference_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_requests');
    }
};
