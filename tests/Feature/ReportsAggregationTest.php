<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\BookItem;
use App\Models\Category;
use App\Models\Loan;
use App\Models\OfficeLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReportsAggregationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_groupings_include_expected_counts_for_loaned_available_and_catalog(): void
    {
        $officeA = OfficeLocation::query()->create(['name' => 'OCB']);
        $officeB = OfficeLocation::query()->create(['name' => 'CCB']);

        $lenderA = User::factory()->create([
            'first_name' => 'Lender',
            'last_name' => 'A',
            'office_location_id' => $officeA->id,
            'is_lender' => true,
        ]);
        $lenderB = User::factory()->create([
            'first_name' => 'Lender',
            'last_name' => 'B',
            'office_location_id' => $officeB->id,
            'is_lender' => true,
        ]);
        $borrower = User::factory()->create([
            'first_name' => 'Borrower',
            'last_name' => 'One',
            'office_location_id' => $officeA->id,
            'is_borrower' => true,
        ]);

        $categoryRace = Category::query()->create(['name' => 'Race and Identity', 'tier' => 1]);
        $categoryLeadership = Category::query()->create(['name' => 'Leadership', 'tier' => 1]);

        $authorA = Author::query()->create(['first_name' => 'James', 'last_name' => 'Baldwin']);
        $authorB = Author::query()->create(['first_name' => 'Ijeoma', 'last_name' => 'Oluo']);

        $bookLoaned = Book::query()->create([
            'title' => 'Loaned Book',
            'book_type' => 'hard_copy',
            'category_id' => $categoryRace->id,
        ]);
        $bookLoaned->authors()->sync([$authorA->id]);

        $bookAvailable = Book::query()->create([
            'title' => 'Available Book',
            'book_type' => 'hard_copy',
            'category_id' => $categoryLeadership->id,
        ]);
        $bookAvailable->authors()->sync([$authorB->id]);

        $loanedItem = BookItem::query()->create([
            'book_id' => $bookLoaned->id,
            'lender_id' => $lenderA->id,
            'unique_key' => 'LEND-A-1',
            'status' => 'checked_out',
        ]);

        $availableItem = BookItem::query()->create([
            'book_id' => $bookAvailable->id,
            'lender_id' => $lenderB->id,
            'unique_key' => 'LEND-B-1',
            'status' => 'available',
        ]);

        BookItem::query()->create([
            'book_id' => $bookAvailable->id,
            'lender_id' => $lenderA->id,
            'unique_key' => 'LEND-A-REMOVED',
            'status' => 'removed',
        ]);

        Loan::query()->create([
            'book_item_id' => $loanedItem->id,
            'lender_id' => $lenderA->id,
            'borrower_id' => $borrower->id,
            'status' => 'borrowed',
            'requested_at' => now()->subDays(2),
            'borrowed_at' => now()->subDay(),
        ]);

        $this->actingAs($lenderA)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/Index')
                ->where('reports.loaned.byOfficeLocation.OCB', 1)
                ->where('reports.loaned.byCategory.Race and Identity', 1)
                ->where('reports.loaned.byTitle.Loaned Book', 1)
                ->where('reports.available.byOfficeLocation.CCB', 1)
                ->where('reports.available.byCategory.Leadership', 1)
                ->where('reports.available.byTitle.Available Book', 1)
                ->where('reports.catalog.byOfficeLocation.OCB', 1)
                ->where('reports.catalog.byOfficeLocation.CCB', 1)
            );

        $this->assertNotNull($availableItem);
    }
}

