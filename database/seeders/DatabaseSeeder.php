<?php

namespace Database\Seeders;

use App\Models\OfficeLocation;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(ReferenceDataSeeder::class);

        $defaultOffice = OfficeLocation::query()->first();

        User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Library Admin',
                'first_name' => 'Library',
                'last_name' => 'Admin',
                'employee_id' => 'EMP-1000',
                'office_location_id' => $defaultOffice?->id,
                'is_lender' => true,
                'is_borrower' => true,
                'agree_lender_guidelines' => true,
                'agree_borrower_guidelines' => true,
                'is_administrator' => true,
                'is_site_owner' => true,
                'password' => Hash::make('password'),
            ]
        );
    }
}
