<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\BookItem;
use App\Models\Category;
use App\Models\Language;
use App\Models\OfficeLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CatalogFacetFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_filters_and_facets_reflect_filtered_result_set(): void
    {
        $officeA = OfficeLocation::query()->create(['name' => 'OCB']);
        $officeB = OfficeLocation::query()->create(['name' => 'CCB']);

        $lenderA = User::factory()->create(['office_location_id' => $officeA->id]);
        $lenderB = User::factory()->create(['office_location_id' => $officeB->id]);

        $catRace = Category::query()->create(['name' => 'Race', 'tier' => 1]);
        $catLeadership = Category::query()->create(['name' => 'Leadership', 'tier' => 1]);

        $langEn = Language::query()->create(['name' => 'English', 'iso_code' => 'en']);
        $langEs = Language::query()->create(['name' => 'Spanish', 'iso_code' => 'es']);

        $authorA = Author::query()->create(['first_name' => 'James', 'last_name' => 'Baldwin']);
        $authorB = Author::query()->create(['first_name' => 'Isabel', 'last_name' => 'Wilkerson']);

        $bookOne = Book::query()->create([
            'title' => 'Race Matters',
            'book_type' => 'hard_copy',
            'category_id' => $catRace->id,
            'language_id' => $langEn->id,
            'description' => 'Race book description',
        ]);
        $bookOne->authors()->sync([$authorA->id]);

        $bookTwo = Book::query()->create([
            'title' => 'Leadership Voices',
            'book_type' => 'online',
            'category_id' => $catLeadership->id,
            'language_id' => $langEs->id,
            'description' => 'Leadership book description',
        ]);
        $bookTwo->authors()->sync([$authorB->id]);

        BookItem::query()->create([
            'book_id' => $bookOne->id,
            'lender_id' => $lenderA->id,
            'unique_key' => 'A-1',
            'status' => 'available',
        ]);
        BookItem::query()->create([
            'book_id' => $bookTwo->id,
            'lender_id' => $lenderB->id,
            'unique_key' => 'B-1',
            'status' => 'checked_out',
        ]);

        $this->get(route('catalog.index', [
            'q' => 'Race',
            'category_id' => $catRace->id,
            'office_location_id' => $officeA->id,
            'language_id' => $langEn->id,
            'book_type' => 'hard_copy',
            'availability' => 'all',
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/Index')
                ->where('filters.q', 'Race')
                ->where('filters.category_id', (string) $catRace->id)
                ->where('filters.office_location_id', (string) $officeA->id)
                ->where('filters.language_id', (string) $langEn->id)
                ->where('filters.book_type', 'hard_copy')
                ->where('filters.availability', 'all')
                ->has('items.data', 1)
                ->where('items.data.0.book.title', 'Race Matters')
                ->has('facets.categories', 1)
                ->where('facets.categories.0.name', 'Race')
                ->where('facets.categories.0.total', 1)
                ->has('facets.office_locations', 1)
                ->where('facets.office_locations.0.name', 'OCB')
                ->where('facets.office_locations.0.total', 1)
                ->has('facets.languages', 1)
                ->where('facets.languages.0.name', 'English')
                ->where('facets.languages.0.total', 1)
                ->has('facets.book_types', 1)
                ->where('facets.book_types.0.book_type', 'hard_copy')
                ->where('facets.book_types.0.total', 1)
            );
    }
}
