<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookItem;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationGuardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_pending_verification_queue_or_admin_actions(): void
    {
        $user = User::factory()->create([
            'is_administrator' => false,
            'is_site_owner' => false,
        ]);
        $lender = User::factory()->create(['is_lender' => true]);
        $item = $this->makeBookItem($lender->id, 'pending_verification');

        $this->actingAs($user)->get(route('books.pending-verification'))->assertForbidden();
        $this->actingAs($user)->patch(route('books.verify', $item->id))->assertForbidden();
        $this->actingAs($user)->post(route('books.verify-bulk'), ['book_item_ids' => [$item->id]])->assertForbidden();
        $this->actingAs($user)->patch(route('books.mark-pending', $item->id))->assertForbidden();
    }

    public function test_non_owner_non_admin_cannot_remove_or_reshelve_another_users_book(): void
    {
        $owner = User::factory()->create(['is_lender' => true]);
        $otherUser = User::factory()->create();
        $item = $this->makeBookItem($owner->id, 'removed');

        $this->actingAs($otherUser)->patch(route('books.remove', $item->id))->assertForbidden();
        $this->actingAs($otherUser)->patch(route('books.reshelve', $item->id))->assertForbidden();
    }

    public function test_non_lender_cannot_approve_reject_or_share_others_loan(): void
    {
        $lender = User::factory()->create(['is_lender' => true]);
        $borrower = User::factory()->create(['is_borrower' => true]);
        $otherUser = User::factory()->create();
        $item = $this->makeBookItem($lender->id, 'loan_pending');

        $loan = Loan::query()->create([
            'book_item_id' => $item->id,
            'lender_id' => $lender->id,
            'borrower_id' => $borrower->id,
            'status' => 'requested',
            'requested_at' => now(),
        ]);

        $this->actingAs($otherUser)->patch(route('loans.approve', $loan->id))->assertForbidden();
        $this->actingAs($otherUser)->patch(route('loans.reject', $loan->id))->assertForbidden();

        $loan->update(['status' => 'approved']);
        $this->actingAs($otherUser)->patch(route('loans.share', $loan->id))->assertForbidden();
    }

    public function test_non_participant_cannot_cancel_or_return_loan(): void
    {
        $lender = User::factory()->create(['is_lender' => true]);
        $borrower = User::factory()->create(['is_borrower' => true]);
        $otherUser = User::factory()->create();
        $item = $this->makeBookItem($lender->id, 'checked_out');

        $loan = Loan::query()->create([
            'book_item_id' => $item->id,
            'lender_id' => $lender->id,
            'borrower_id' => $borrower->id,
            'status' => 'borrowed',
            'requested_at' => now()->subDay(),
            'borrowed_at' => now()->subHours(12),
        ]);

        $this->actingAs($otherUser)->patch(route('loans.cancel', $loan->id))->assertForbidden();
        $this->actingAs($otherUser)->patch(route('loans.return', $loan->id))->assertForbidden();
    }

    private function makeBookItem(int $lenderId, string $status): BookItem
    {
        $book = Book::query()->create([
            'title' => 'Auth Guard Test '.fake()->unique()->lexify('????'),
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

