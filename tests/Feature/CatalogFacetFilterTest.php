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
            'category_1_id' => $catRace->id,
            'office_location_id' => $officeA->id,
            'language_id' => $langEn->id,
            'book_type' => 'hard_copy',
            'availability' => 'all',
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/Index')
                ->where('filters.q', 'Race')
                ->where('filters.category_1_id', (string) $catRace->id)
                ->where('filters.office_location_id', (string) $officeA->id)
                ->where('filters.language_id', (string) $langEn->id)
                ->where('filters.book_type', 'hard_copy')
                ->where('filters.availability', 'all')
                ->has('items.data', 1)
                ->where('items.data.0.book.title', 'Race Matters')
                ->has('facets.category_tier_1', 1)
                ->where('facets.category_tier_1.0.name', 'Race')
                ->where('facets.category_tier_1.0.total', 1)
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

    public function test_category_tier_facets_include_distinct_nodes_per_tier(): void
    {
        $office = OfficeLocation::query()->create(['name' => 'OCB']);
        $lender = User::factory()->create(['office_location_id' => $office->id]);

        $root = Category::query()->create(['name' => 'Root', 'tier' => 1]);
        $socialJusticeA = Category::query()->create(['name' => 'Social Justice', 'parent_id' => $root->id, 'tier' => 2]);
        $otherRoot = Category::query()->create(['name' => 'Other', 'tier' => 1]);
        $socialJusticeB = Category::query()->create(['name' => 'Social Justice', 'parent_id' => $otherRoot->id, 'tier' => 2]);
        $raceA = Category::query()->create(['name' => 'Race', 'parent_id' => $socialJusticeA->id, 'tier' => 3]);
        $raceB = Category::query()->create(['name' => 'Race', 'parent_id' => $socialJusticeB->id, 'tier' => 3]);

        $bookA = Book::query()->create([
            'title' => 'Book A',
            'book_type' => 'hard_copy',
            'category_id' => $raceA->id,
        ]);
        $bookB = Book::query()->create([
            'title' => 'Book B',
            'book_type' => 'hard_copy',
            'category_id' => $raceB->id,
        ]);

        BookItem::query()->create([
            'book_id' => $bookA->id,
            'lender_id' => $lender->id,
            'unique_key' => 'SJ-A',
            'status' => 'available',
        ]);
        BookItem::query()->create([
            'book_id' => $bookB->id,
            'lender_id' => $lender->id,
            'unique_key' => 'SJ-B',
            'status' => 'available',
        ]);

        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/Index')
                ->where('facets.category_tier_2', function ($facets): bool {
                    $facetArray = is_array($facets) ? $facets : $facets->toArray();
                    $socialJustice = array_values(array_filter(
                        $facetArray,
                        fn (array $facet) => mb_strtolower($facet['name']) === 'social justice'
                    ));

                    return count($socialJustice) === 1 && (int) $socialJustice[0]['total'] === 2;
                })
                ->where('facets.category_tier_3', function ($facets): bool {
                    $facetArray = is_array($facets) ? $facets : $facets->toArray();
                    $race = array_values(array_filter(
                        $facetArray,
                        fn (array $facet) => mb_strtolower($facet['name']) === 'race'
                    ));

                    return count($race) === 1 && (int) $race[0]['total'] === 2;
                })
            );
    }

    public function test_category_tier_3_name_filter_matches_deduped_facet_name(): void
    {
        $office = OfficeLocation::query()->create(['name' => 'OCB']);
        $lender = User::factory()->create(['office_location_id' => $office->id]);

        $root = Category::query()->create(['name' => 'Root', 'tier' => 1]);
        $socialJusticeA = Category::query()->create(['name' => 'Social Justice', 'parent_id' => $root->id, 'tier' => 2]);
        $otherRoot = Category::query()->create(['name' => 'Other', 'tier' => 1]);
        $socialJusticeB = Category::query()->create(['name' => 'Social Justice', 'parent_id' => $otherRoot->id, 'tier' => 2]);
        $africanAmericanA = Category::query()->create(['name' => 'African American', 'parent_id' => $socialJusticeA->id, 'tier' => 3]);
        $africanAmericanB = Category::query()->create(['name' => 'African American', 'parent_id' => $socialJusticeB->id, 'tier' => 3]);

        $bookA = Book::query()->create([
            'title' => 'Book A',
            'book_type' => 'hard_copy',
            'category_id' => $africanAmericanA->id,
        ]);
        $bookB = Book::query()->create([
            'title' => 'Book B',
            'book_type' => 'hard_copy',
            'category_id' => $africanAmericanB->id,
        ]);

        BookItem::query()->create([
            'book_id' => $bookA->id,
            'lender_id' => $lender->id,
            'unique_key' => 'AA-A',
            'status' => 'available',
        ]);
        BookItem::query()->create([
            'book_id' => $bookB->id,
            'lender_id' => $lender->id,
            'unique_key' => 'AA-B',
            'status' => 'available',
        ]);

        $this->get(route('catalog.index', ['category_3' => 'african american']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/Index')
                ->where('filters.category_3', 'african american')
                ->has('items.data', 2)
            );
    }
}
