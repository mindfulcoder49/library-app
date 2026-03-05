<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CsvImportAndVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_dry_run_parses_rows_but_does_not_persist_data(): void
    {
        $user = User::factory()->create([
            'is_lender' => true,
            'is_borrower' => true,
            'employee_id' => 'EMP-100',
        ]);

        $csv = <<<CSV
ISBN-Emp,ISBN,Title,Author,Language,Book Type,Category 1,Category 2,Category 3,Description,Lender Comments,Lender ID,Office Locations,Status
1234567890-451776,1234567890,"The \"\"Quoted\"\" Title, Vol 1","Doe, Jane",English,Hard Copy,Nonfiction,Leadership,Inclusion,"A description, with commas",Comment,451776,"OCB, CCB",available
CSV;

        $file = UploadedFile::fake()->createWithContent('books.csv', $csv);

        $this->actingAs($user)
            ->from(route('books.create'))
            ->post(route('books.import-csv'), [
                'csv_file' => $file,
                'dry_run' => 1,
            ])
            ->assertRedirect(route('books.create'))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('books', 0);
        $this->assertDatabaseCount('book_items', 0);
    }

    public function test_non_admin_csv_import_ignores_lender_id_and_assigns_to_uploader(): void
    {
        $uploader = User::factory()->create([
            'is_lender' => true,
            'is_borrower' => true,
            'employee_id' => 'EMP-UPLOADER',
        ]);
        User::factory()->create([
            'employee_id' => '451776',
            'is_lender' => true,
            'is_borrower' => true,
        ]);

        $csv = <<<CSV
ISBN-Emp,ISBN,Title,Author,Language,Book Type,Category 1,Category 2,Category 3,Description,Lender Comments,Lender ID,Office Locations,Status
1234567890-451776,1234567890,Uploader Assignment Test,"Doe, Jane",English,Hard Copy,Nonfiction,Leadership,Inclusion,Description,Comment,451776,"OCB, CCB",available
CSV;

        $file = UploadedFile::fake()->createWithContent('books.csv', $csv);

        $this->actingAs($uploader)
            ->from(route('books.create'))
            ->post(route('books.import-csv'), [
                'csv_file' => $file,
            ])
            ->assertRedirect(route('books.create'))
            ->assertSessionHas('success');

        $item = BookItem::query()->first();
        $this->assertNotNull($item);
        $this->assertSame($uploader->id, $item->lender_id);
    }

    public function test_admin_csv_import_can_apply_lender_id(): void
    {
        $admin = User::factory()->create([
            'is_administrator' => true,
            'is_site_owner' => true,
            'is_lender' => true,
            'is_borrower' => true,
            'employee_id' => 'EMP-ADMIN',
        ]);
        $targetLender = User::factory()->create([
            'employee_id' => '451776',
            'is_lender' => true,
            'is_borrower' => true,
        ]);

        $csv = <<<CSV
ISBN-Emp,ISBN,Title,Author,Language,Book Type,Category 1,Category 2,Category 3,Description,Lender Comments,Lender ID,Office Locations,Status
1234567890-451776,1234567890,Admin Assignment Test,"Doe, Jane",English,Hard Copy,Nonfiction,Leadership,Inclusion,Description,Comment,451776,"OCB, CCB",available
CSV;

        $file = UploadedFile::fake()->createWithContent('books.csv', $csv);

        $this->actingAs($admin)
            ->from(route('books.create'))
            ->post(route('books.import-csv'), [
                'csv_file' => $file,
            ])
            ->assertRedirect(route('books.create'))
            ->assertSessionHas('success');

        $item = BookItem::query()->first();
        $this->assertNotNull($item);
        $this->assertSame($targetLender->id, $item->lender_id);
    }

    public function test_csv_import_row_missing_title_returns_row_error_feedback(): void
    {
        $user = User::factory()->create([
            'is_lender' => true,
            'is_borrower' => true,
            'employee_id' => 'EMP-ERR',
        ]);

        $csv = <<<CSV
ISBN-Emp,ISBN,Title,Author,Language,Book Type,Category 1,Category 2,Category 3,Description,Lender Comments,Lender ID,Office Locations,Status
1234567890-451776,1234567890,,"Doe, Jane",English,Hard Copy,Nonfiction,Leadership,Inclusion,Description,Comment,451776,"OCB, CCB",available
CSV;

        $file = UploadedFile::fake()->createWithContent('books.csv', $csv);

        $this->actingAs($user)
            ->from(route('books.create'))
            ->post(route('books.import-csv'), [
                'csv_file' => $file,
            ])
            ->assertRedirect(route('books.create'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('books', 0);
        $this->assertDatabaseCount('book_items', 0);
    }

    public function test_verify_bulk_updates_only_pending_items_in_selected_ids(): void
    {
        $admin = User::factory()->create([
            'is_administrator' => true,
            'is_site_owner' => true,
        ]);
        $lender = User::factory()->create(['is_lender' => true]);

        $pending = $this->makeBookItem($lender->id, 'pending_verification');
        $available = $this->makeBookItem($lender->id, 'available');
        $removed = $this->makeBookItem($lender->id, 'removed');

        $this->actingAs($admin)
            ->post(route('books.verify-bulk'), [
                'book_item_ids' => [$pending->id, $available->id, $removed->id],
            ])
            ->assertSessionHas('success');

        $this->assertSame('available', $pending->fresh()->status);
        $this->assertNotNull($pending->fresh()->verified_at);
        $this->assertSame('available', $available->fresh()->status);
        $this->assertSame('removed', $removed->fresh()->status);
    }

    public function test_verify_bulk_returns_warning_when_no_selected_items_are_pending(): void
    {
        $admin = User::factory()->create([
            'is_administrator' => true,
            'is_site_owner' => true,
        ]);
        $lender = User::factory()->create(['is_lender' => true]);

        $available = $this->makeBookItem($lender->id, 'available');

        $this->actingAs($admin)
            ->post(route('books.verify-bulk'), [
                'book_item_ids' => [$available->id],
            ])
            ->assertSessionHas('warning');
    }

    private function makeBookItem(int $lenderId, string $status): BookItem
    {
        $book = Book::query()->create([
            'title' => 'CSV Verification Test '.fake()->unique()->lexify('????'),
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

