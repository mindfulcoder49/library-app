<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authors', function (Blueprint $table) {
            $table->id();
            $table->string('last_name');
            $table->string('first_name')->nullable();
            $table->timestamps();

            $table->unique(['last_name', 'first_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authors');
    }
};
