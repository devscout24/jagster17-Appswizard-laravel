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
        Schema::create('veteran_nominations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nominator_name');
            $table->string('nominator_email');
            $table->string('nominator_phone')->nullable();
            $table->string('nominee_name');
            $table->string('nominee_branch')->nullable();
            $table->string('nominee_city')->nullable();
            $table->string('nominee_state')->nullable();
            $table->string('project_needed');
            $table->text('story_details');
            $table->enum('status', ['pending', 'reviewing', 'approved', 'rejected'])->default('pending');
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('veteran_nominations');
    }
};
