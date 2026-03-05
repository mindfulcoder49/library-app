<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookItem;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookStatusIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_move_checked_out_item_to_pending_when_active_loan_exists(): void
    {
        $admin = User::factory()->create([
            'is_administrator' => true,
            'is_site_owner' => true,
        ]);
        $lender = User::factory()->create(['is_lender' => true]);
        $borrower = User::factory()->create(['is_borrower' => true]);

        $item = $this->makeBookItem($lender->id, 'checked_out');
        Loan::query()->create([
            'book_item_id' => $item->id,
            'lender_id' => $lender->id,
            'borrower_id' => $borrower->id,
            'status' => 'borrowed',
            'requested_at' => now()->subDay(),
            'borrowed_at' => now()->subHours(6),
        ]);

        $this->actingAs($admin)
            ->patch(route('books.mark-pending', $item->id))
            ->assertStatus(422);

        $this->assertSame('checked_out', $item->fresh()->status);
    }

    public function test_owner_cannot_remove_item_with_active_borrowed_loan(): void
    {
        $lender = User::factory()->create(['is_lender' => true]);
        $borrower = User::factory()->create(['is_borrower' => true]);

        $item = $this->makeBookItem($lender->id, 'checked_out');
        Loan::query()->create([
            'book_item_id' => $item->id,
            'lender_id' => $lender->id,
            'borrower_id' => $borrower->id,
            'status' => 'borrowed',
            'requested_at' => now()->subDay(),
            'borrowed_at' => now()->subHours(6),
        ]);

        $this->actingAs($lender)
            ->patch(route('books.remove', $item->id))
            ->assertStatus(422);

        $this->assertSame('checked_out', $item->fresh()->status);
    }

    private function makeBookItem(int $lenderId, string $status): BookItem
    {
        $book = Book::query()->create([
            'title' => 'Integrity Test '.fake()->unique()->lexify('????'),
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

