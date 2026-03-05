<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookItem;
use App\Models\Loan;
use App\Models\User;
use App\Models\WaitingListEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LibraryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_defaults_to_all_books_and_can_filter_available_only(): void
    {
        $lender = User::factory()->create(['name' => 'Lender User']);

        $availableItem = $this->makeBookItem($lender->id, 'available');
        $checkedOutItem = $this->makeBookItem($lender->id, 'checked_out');

        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/Index')
                ->where('filters.availability', 'all')
                ->has('items.data', 2)
            );

        $this->get(route('catalog.index', ['availability' => 'available']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/Index')
                ->where('filters.availability', 'available')
                ->has('items.data', 1)
                ->where('items.data.0.id', $availableItem->id)
            );

        $this->assertNotSame($availableItem->id, $checkedOutItem->id);
    }

    public function test_user_can_join_waitlist_cancel_and_rejoin_for_same_book(): void
    {
        $lender = User::factory()->create(['name' => 'Lender User', 'is_lender' => true]);
        $borrower = User::factory()->create(['name' => 'Borrower User', 'is_borrower' => true]);

        $item = $this->makeBookItem($lender->id, 'checked_out');

        $this->actingAs($borrower)
            ->post(route('loans.store', $item->id))
            ->assertSessionHas('success');

        $entry = WaitingListEntry::query()
            ->where('book_id', $item->book_id)
            ->where('user_id', $borrower->id)
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame('waiting', $entry->status);
        $this->assertSame(1, (int) $entry->position);

        $this->actingAs($borrower)
            ->patch(route('waitlist.cancel', $entry->id))
            ->assertSessionHas('success');

        $this->assertSame('cancelled', $entry->fresh()->status);

        $this->actingAs($borrower)
            ->post(route('loans.store', $item->id))
            ->assertSessionHas('success');

        $entry->refresh();
        $this->assertSame('waiting', $entry->status);
        $this->assertSame(1, (int) $entry->position);
        $this->assertNull($entry->notified_at);
    }

    public function test_return_promotes_next_waitlisted_user_into_requested_loan(): void
    {
        $lender = User::factory()->create(['name' => 'Lender User', 'is_lender' => true]);
        $currentBorrower = User::factory()->create(['name' => 'Current Borrower', 'is_borrower' => true]);
        $waiterOne = User::factory()->create(['name' => 'Waiter One', 'is_borrower' => true]);
        $waiterTwo = User::factory()->create(['name' => 'Waiter Two', 'is_borrower' => true]);

        $item = $this->makeBookItem($lender->id, 'checked_out');

        $activeLoan = Loan::query()->create([
            'book_item_id' => $item->id,
            'lender_id' => $lender->id,
            'borrower_id' => $currentBorrower->id,
            'status' => 'borrowed',
            'requested_at' => now()->subDays(5),
            'approved_at' => now()->subDays(4),
            'shared_at' => now()->subDays(4),
            'borrowed_at' => now()->subDays(4),
        ]);

        $entryOne = WaitingListEntry::query()->create([
            'book_id' => $item->book_id,
            'book_item_id' => $item->id,
            'user_id' => $waiterOne->id,
            'status' => 'waiting',
            'position' => 1,
        ]);

        $entryTwo = WaitingListEntry::query()->create([
            'book_id' => $item->book_id,
            'book_item_id' => $item->id,
            'user_id' => $waiterTwo->id,
            'status' => 'waiting',
            'position' => 2,
        ]);

        $this->actingAs($currentBorrower)
            ->patch(route('loans.return', $activeLoan->id))
            ->assertSessionHas('success');

        $this->assertSame('returned', $activeLoan->fresh()->status);
        $this->assertSame('loan_pending', $item->fresh()->status);

        $promotedLoan = Loan::query()
            ->where('book_item_id', $item->id)
            ->where('borrower_id', $waiterOne->id)
            ->where('status', 'requested')
            ->first();

        $this->assertNotNull($promotedLoan);
        $this->assertSame('Auto-created from waitlist queue.', $promotedLoan->notes);

        $this->assertSame('fulfilled', $entryOne->fresh()->status);
        $this->assertSame('waiting', $entryTwo->fresh()->status);
        $this->assertSame(1, (int) $entryTwo->fresh()->position);
    }

    public function test_admin_can_filter_system_wide_loan_history(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'is_administrator' => true,
            'is_site_owner' => true,
        ]);
        $lender = User::factory()->create(['name' => 'Lender User', 'first_name' => 'Lender', 'last_name' => 'One']);
        $borrowerA = User::factory()->create(['name' => 'Borrower Alpha', 'first_name' => 'Borrower', 'last_name' => 'Alpha']);
        $borrowerB = User::factory()->create(['name' => 'Borrower Beta', 'first_name' => 'Borrower', 'last_name' => 'Beta']);

        $itemA = $this->makeBookItem($lender->id, 'available', 'Requested Title');
        $itemB = $this->makeBookItem($lender->id, 'available', 'Returned Title');

        Loan::query()->create([
            'book_item_id' => $itemA->id,
            'lender_id' => $lender->id,
            'borrower_id' => $borrowerA->id,
            'status' => 'requested',
            'requested_at' => '2026-03-01 10:00:00',
        ]);

        Loan::query()->create([
            'book_item_id' => $itemB->id,
            'lender_id' => $lender->id,
            'borrower_id' => $borrowerB->id,
            'status' => 'returned',
            'requested_at' => '2026-02-15 10:00:00',
            'returned_at' => '2026-02-20 10:00:00',
        ]);

        $this->actingAs($admin)
            ->get(route('loans.requests', [
                'status' => 'returned',
                'borrower_id' => $borrowerB->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Loans/Requests')
                ->where('isGlobalView', true)
                ->has('loans', 1)
                ->where('loans.0.status', 'returned')
                ->where('loans.0.borrower_id', $borrowerB->id)
            );

        $this->actingAs($admin)
            ->get(route('loans.borrowed', [
                'q' => 'Requested Title',
                'date_from' => '2026-03-01',
                'date_to' => '2026-03-02',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Loans/Borrowed')
                ->where('isGlobalView', true)
                ->has('loans', 1)
                ->where('loans.0.status', 'requested')
                ->where('loans.0.book_item.book.title', fn (string $value) => str_contains($value, 'Requested Title'))
            );
    }

    private function makeBookItem(int $lenderId, string $status, string $title = 'Test Book'): BookItem
    {
        $book = Book::query()->create([
            'title' => $title.' '.fake()->unique()->lexify('????'),
            'isbn10' => fake()->unique()->numerify('##########'),
            'isbn13' => fake()->unique()->numerify('#############'),
            'book_type' => 'hard_copy',
        ]);

        return BookItem::query()->create([
            'book_id' => $book->id,
            'lender_id' => $lenderId,
            'unique_key' => fake()->unique()->regexify('[0-9]{6}-[0-9]{10}'),
            'status' => $status,
        ]);
    }
}
