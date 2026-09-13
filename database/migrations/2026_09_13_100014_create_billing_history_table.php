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
        Schema::create('billing_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->string('plan_name');
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['paid', 'failed'])->default('paid');
            $table->string('receipt_url')->nullable();
            $table->date('billed_at');
            $table->timestamps();

            $table->index(['subscription_id', 'billed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_history');
    }
};
