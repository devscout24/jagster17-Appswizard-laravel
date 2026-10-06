<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Customers a contractor bills; may or may not have a ValorHub account (user_id)
        Schema::create('contractor_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('billing_address')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'name']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('contractor_customer_id')->nullable()->after('customer_id')
                ->constrained('contractor_customers')->nullOnDelete();
            $table->string('po_number')->nullable()->after('invoice_number');
            $table->decimal('subtotal', 10, 2)->nullable()->after('po_number');
            $table->decimal('discount_percent', 5, 2)->default(0)->after('subtotal');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('discount_percent');
            $table->string('payment_terms')->nullable()->after('due_at');
            $table->text('footer')->nullable()->after('notes');
            $table->json('tags')->nullable()->after('footer');
            $table->json('attachments')->nullable()->after('tags');
            $table->boolean('require_terms_acceptance')->default(false)->after('attachments');
            $table->string('payment_method')->nullable()->after('paid_at');
            $table->timestamp('sent_at')->nullable()->after('payment_method');
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->text('details')->nullable();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('amount', 10, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contractor_customer_id');
            $table->dropColumn([
                'po_number', 'subtotal', 'discount_percent', 'discount_amount', 'payment_terms',
                'footer', 'tags', 'attachments', 'require_terms_acceptance', 'payment_method', 'sent_at',
            ]);
        });

        Schema::dropIfExists('contractor_customers');
    }
};
