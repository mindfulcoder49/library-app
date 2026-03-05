<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('book_id');
            $table->foreignId('lender_id')->constrained('users')->cascadeOnDelete();
            $table->string('unique_key')->unique();
            $table->text('lender_comments')->nullable();
            $table->enum('status', ['pending_verification', 'available', 'loan_pending', 'checked_out', 'removed'])->default('pending_verification');
            $table->date('expected_return_date')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('removed_at')->nullable();
            $table->timestamps();

            $table->unique(['book_id', 'lender_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_items');
    }
};
