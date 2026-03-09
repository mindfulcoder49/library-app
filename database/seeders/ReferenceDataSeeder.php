<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use App\Models\OfficeLocation;
use Illuminate\Database\Seeder;

class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $us = Country::query()->firstOrCreate(['name' => 'United States'], ['iso2' => 'US']);
        $uk = Country::query()->firstOrCreate(['name' => 'United Kingdom'], ['iso2' => 'GB']);
        $in = Country::query()->firstOrCreate(['name' => 'India'], ['iso2' => 'IN']);

        $boston = City::query()->firstOrCreate(['country_id' => $us->id, 'name' => 'Boston']);
        $newYork = City::query()->firstOrCreate(['country_id' => $us->id, 'name' => 'New York']);
        $london = City::query()->firstOrCreate(['country_id' => $uk->id, 'name' => 'London']);
        $bangalore = City::query()->firstOrCreate(['country_id' => $in->id, 'name' => 'Bangalore']);

        $offices = [
            ['name' => 'Boston', 'country_id' => $us->id, 'city_id' => $boston->id, 'is_virtual' => false],
            ['name' => 'New York', 'country_id' => $us->id, 'city_id' => $newYork->id, 'is_virtual' => false],
            ['name' => 'London', 'country_id' => $uk->id, 'city_id' => $london->id, 'is_virtual' => false],
            ['name' => 'Bangalore', 'country_id' => $in->id, 'city_id' => $bangalore->id, 'is_virtual' => false],
            ['name' => 'Degreed', 'country_id' => null, 'city_id' => null, 'is_virtual' => true],
        ];

        foreach ($offices as $office) {
            OfficeLocation::query()->firstOrCreate(['name' => $office['name']], $office + ['is_active' => true]);
        }

    }
}
