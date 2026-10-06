<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Invoices can be billed to a contractor_customer who has no ValorHub account
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable(false)->change();
        });
    }
};
