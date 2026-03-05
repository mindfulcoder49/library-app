<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookItem;
use App\Models\Loan;
use App\Models\User;
use App\Models\WaitingListEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanWaitlistEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_borrower_with_existing_requested_loan_cannot_also_join_waitlist_for_same_item(): void
    {
        $lender = User::factory()->create(['is_lender' => true]);
        $borrower = User::factory()->create(['is_borrower' => true]);
        $item = $this->makeBookItem($lender->id, 'loan_pending');

        Loan::query()->create([
            'book_item_id' => $item->id,
            'lender_id' => $lender->id,
            'borrower_id' => $borrower->id,
            'status' => 'requested',
            'requested_at' => now(),
        ]);

        $this->actingAs($borrower)
            ->post(route('loans.store', $item->id))
            ->assertSessionHas('warning');

        $this->assertDatabaseCount('waiting_list_entries', 0);
    }

    public function test_waitlist_promotion_does_not_duplicate_requested_loan_for_same_user(): void
    {
        $lender = User::factory()->create(['is_lender' => true]);
        $currentBorrower = User::factory()->create(['is_borrower' => true]);
        $waiter = User::factory()->create(['is_borrower' => true]);

        $item = $this->makeBookItem($lender->id, 'checked_out');

        $activeLoan = Loan::query()->create([
            'book_item_id' => $item->id,
            'lender_id' => $lender->id,
            'borrower_id' => $currentBorrower->id,
            'status' => 'borrowed',
            'requested_at' => now()->subDay(),
            'borrowed_at' => now()->subHours(12),
        ]);

        WaitingListEntry::query()->create([
            'book_id' => $item->book_id,
            'book_item_id' => $item->id,
            'user_id' => $waiter->id,
            'status' => 'waiting',
            'position' => 1,
        ]);

        Loan::query()->create([
            'book_item_id' => $item->id,
            'lender_id' => $lender->id,
            'borrower_id' => $waiter->id,
            'status' => 'requested',
            'requested_at' => now()->subHours(2),
        ]);

        $this->actingAs($currentBorrower)
            ->patch(route('loans.return', $activeLoan->id))
            ->assertSessionHas('success');

        $requestedCount = Loan::query()
            ->where('book_item_id', $item->id)
            ->where('borrower_id', $waiter->id)
            ->where('status', 'requested')
            ->count();

        $this->assertSame(1, $requestedCount);
    }

    private function makeBookItem(int $lenderId, string $status): BookItem
    {
        $book = Book::query()->create([
            'title' => 'Waitlist Edge '.fake()->unique()->lexify('????'),
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

