<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('isbn10', 10)->nullable()->index();
            $table->string('isbn13', 13)->nullable()->index();
            $table->string('title');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('language_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('book_type', ['hard_copy', 'online'])->default('hard_copy');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['title', 'book_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
