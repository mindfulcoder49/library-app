<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('id');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('employee_id')->nullable()->unique()->after('email');
            $table->foreignId('office_location_id')->nullable()->after('employee_id')->constrained()->nullOnDelete();
            $table->boolean('is_lender')->default(false)->after('office_location_id');
            $table->boolean('is_borrower')->default(true)->after('is_lender');
            $table->boolean('agree_lender_guidelines')->default(false)->after('is_borrower');
            $table->boolean('agree_borrower_guidelines')->default(false)->after('agree_lender_guidelines');
            $table->boolean('is_administrator')->default(false)->after('agree_borrower_guidelines');
            $table->boolean('is_site_owner')->default(false)->after('is_administrator');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('office_location_id');
            $table->dropColumn([
                'first_name',
                'last_name',
                'employee_id',
                'is_lender',
                'is_borrower',
                'agree_lender_guidelines',
                'agree_borrower_guidelines',
                'is_administrator',
                'is_site_owner',
            ]);
        });
    }
};
