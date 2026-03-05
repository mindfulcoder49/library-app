<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Language;
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

        foreach ([
            ['name' => 'English', 'iso_code' => 'en'],
            ['name' => 'Spanish', 'iso_code' => 'es'],
            ['name' => 'French', 'iso_code' => 'fr'],
            ['name' => 'Portuguese', 'iso_code' => 'pt'],
            ['name' => 'Hindi', 'iso_code' => 'hi'],
        ] as $language) {
            Language::query()->firstOrCreate(['name' => $language['name']], $language);
        }

        $dei = Category::query()->firstOrCreate(['name' => 'Diversity, Equity and Inclusion', 'parent_id' => null], ['tier' => 1]);
        $leadership = Category::query()->firstOrCreate(['name' => 'Leadership', 'parent_id' => null], ['tier' => 1]);

        $inclusion = Category::query()->firstOrCreate(['name' => 'Inclusion', 'parent_id' => $dei->id], ['tier' => 2]);
        $equity = Category::query()->firstOrCreate(['name' => 'Equity', 'parent_id' => $dei->id], ['tier' => 2]);
        $allyship = Category::query()->firstOrCreate(['name' => 'Allyship', 'parent_id' => $dei->id], ['tier' => 2]);

        Category::query()->firstOrCreate(['name' => 'Race and Identity', 'parent_id' => $inclusion->id], ['tier' => 3]);
        Category::query()->firstOrCreate(['name' => 'Gender Equity', 'parent_id' => $equity->id], ['tier' => 3]);
        Category::query()->firstOrCreate(['name' => 'Inclusive Leadership', 'parent_id' => $leadership->id], ['tier' => 2]);
        Category::query()->firstOrCreate(['name' => 'Cross-Cultural Communication', 'parent_id' => $allyship->id], ['tier' => 3]);
    }
}
