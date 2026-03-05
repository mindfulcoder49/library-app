<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookItem;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanTransitionGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_lender_cannot_approve_loan_that_is_not_requested(): void
    {
        [$lender, $borrower, $item, $loan] = $this->makeLoan('approved');

        $this->actingAs($lender)
            ->patch(route('loans.approve', $loan->id))
            ->assertStatus(422);

        $this->assertSame('approved', $loan->fresh()->status);
    }

    public function test_lender_cannot_reject_loan_that_is_not_requested(): void
    {
        [$lender, $borrower, $item, $loan] = $this->makeLoan('borrowed', 'checked_out');

        $this->actingAs($lender)
            ->patch(route('loans.reject', $loan->id))
            ->assertStatus(422);

        $this->assertSame('borrowed', $loan->fresh()->status);
        $this->assertSame('checked_out', $item->fresh()->status);
    }

    public function test_lender_cannot_share_loan_that_is_not_approved(): void
    {
        [$lender, $borrower, $item, $loan] = $this->makeLoan('requested', 'loan_pending');

        $this->actingAs($lender)
            ->patch(route('loans.share', $loan->id))
            ->assertStatus(422);

        $this->assertSame('requested', $loan->fresh()->status);
        $this->assertSame('loan_pending', $item->fresh()->status);
    }

    public function test_participant_cannot_cancel_loan_that_is_not_requested_or_approved(): void
    {
        [$lender, $borrower, $item, $loan] = $this->makeLoan('borrowed', 'checked_out');

        $this->actingAs($borrower)
            ->patch(route('loans.cancel', $loan->id))
            ->assertStatus(422);

        $this->assertSame('borrowed', $loan->fresh()->status);
        $this->assertSame('checked_out', $item->fresh()->status);
    }

    public function test_participant_cannot_return_loan_that_is_not_borrowed(): void
    {
        [$lender, $borrower, $item, $loan] = $this->makeLoan('approved', 'loan_pending');

        $this->actingAs($borrower)
            ->patch(route('loans.return', $loan->id))
            ->assertStatus(422);

        $this->assertSame('approved', $loan->fresh()->status);
        $this->assertSame('loan_pending', $item->fresh()->status);
    }

    private function makeLoan(string $loanStatus, string $itemStatus = 'loan_pending'): array
    {
        $lender = User::factory()->create(['is_lender' => true]);
        $borrower = User::factory()->create(['is_borrower' => true]);

        $book = Book::query()->create([
            'title' => 'Transition Test '.fake()->unique()->lexify('????'),
            'book_type' => 'hard_copy',
        ]);

        $item = BookItem::query()->create([
            'book_id' => $book->id,
            'lender_id' => $lender->id,
            'unique_key' => fake()->unique()->regexify('[0-9]{6}-[0-9]{10}'),
            'status' => $itemStatus,
        ]);

        $loan = Loan::query()->create([
            'book_item_id' => $item->id,
            'lender_id' => $lender->id,
            'borrower_id' => $borrower->id,
            'status' => $loanStatus,
            'requested_at' => now()->subDay(),
        ]);

        return [$lender, $borrower, $item, $loan];
    }
}

